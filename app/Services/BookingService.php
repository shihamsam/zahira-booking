<?php

namespace App\Services;

use App\Exceptions\DatesUnavailableException;
use App\Mail\NewBookingReceived;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(protected PricingService $pricing)
    {
    }

    /**
     * Dates already held by active (pending/confirmed) bookings for a specific
     * slot type on a resource, within the given date range.
     *
     * @return array<int, string>
     */
    public function unavailableDates(Resource $resource, string $from, string $to, ?string $slotType = null): array
    {
        return BookingDate::query()
            ->where('resource_id', $resource->id)
            ->whereBetween('date', [$from, $to])
            ->when($slotType, fn ($q) => $q->where('slot_type', $slotType))
            ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Return unavailable dates for every slot type defined for the resource,
     * keyed by slot_type string. Used to populate the public calendar on page load.
     *
     * @return array<string, array<int, string>>
     */
    public function unavailableDatesBySlot(Resource $resource, string $from, string $to): array
    {
        $slots = array_keys($this->pricing->slots($resource));

        if (empty($slots)) {
            return ['full_day' => $this->unavailableDates($resource, $from, $to)];
        }

        $result = [];
        foreach ($slots as $slot) {
            $result[$slot] = $this->unavailableDates($resource, $from, $to, $slot);
        }

        return $result;
    }

    /**
     * Create a booking atomically, re-checking slot-aware availability to
     * prevent double-booking under concurrent requests.
     *
     * @param array<int, string> $dates Y-m-d formatted dates
     */
    public function createBooking(
        Resource $resource,
        array $dates,
        string $fullName,
        string $mobileNumber,
        string $nic,
        string $purpose,
        string $slotType,
        UploadedFile $receiptFile,
        ?string $email = null,
        ?string $startTime = null,
        ?string $endTime = null,
        int $hours = 0,
        int $chairCount = 0,
        bool $soundSystemRequested = false,
    ): Booking {
        $dates = collect($dates)->unique()->sort()->values()->all();

        return DB::transaction(function () use (
            $resource, $dates, $fullName, $mobileNumber, $nic, $purpose,
            $slotType, $receiptFile, $email, $startTime, $endTime, $hours,
            $chairCount, $soundSystemRequested,
        ) {
            // Lock existing slot rows so concurrent requests for the same
            // resource + date + slot must wait.
            $taken = BookingDate::query()
                ->where('resource_id', $resource->id)
                ->whereIn('date', $dates)
                ->where('slot_type', $slotType)
                ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected'])->lockForUpdate())
                ->lockForUpdate()
                ->pluck('date')
                ->map(fn ($d) => $d->format('Y-m-d'))
                ->all();

            if (! empty($taken)) {
                throw new DatesUnavailableException($taken);
            }

            $unitPrice   = $this->pricing->unitPrice($resource, $slotType, $hours);
            $totalAmount = $this->pricing->totalAmount($resource, $slotType, $dates, $hours, $chairCount);

            // Store receipt now — user uploaded it at submission time.
            $receiptPath = $receiptFile->store('receipts', 'public');

            $booking = Booking::create([
                'reference_no'           => $this->generateReferenceNo($resource),
                'resource_id'            => $resource->id,
                'full_name'              => $fullName,
                'mobile_number'          => $mobileNumber,
                'nic'                    => $nic,
                'email'                  => $email,
                'purpose'                => $purpose,
                'slot_type'              => $slotType,
                'start_time'             => $startTime,
                'end_time'               => $endTime,
                'hours'                  => $hours ?: null,
                'chair_count'            => $chairCount ?: null,
                'sound_system_requested' => $soundSystemRequested,
                'total_amount'           => $totalAmount,
                'status'                 => 'pending',
                'receipt_path'           => $receiptPath,
            ]);

            foreach ($dates as $date) {
                $booking->dates()->create([
                    'resource_id' => $resource->id,
                    'date'        => $date,
                    'slot_type'   => $slotType,
                    'unit_price'  => $unitPrice,
                ]);
            }

            $this->notifyAdmins($booking->fresh(['resource', 'dates']));

            return $booking;
        });
    }

    protected function generateReferenceNo(Resource $resource): string
    {
        $prefix   = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $resource->name), 0, 3)) ?: 'GRD';
        $datePart = now()->format('Ymd');

        do {
            $candidate = sprintf('%s-%s-%s', $prefix, $datePart, Str::upper(Str::random(4)));
        } while (Booking::where('reference_no', $candidate)->exists());

        return $candidate;
    }

    protected function notifyAdmins(Booking $booking): void
    {
        $emails = User::query()->pluck('email')
            ->merge(config('booking.notify_extra_emails', []))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return;
        }

        Mail::to($emails->shift())
            ->cc($emails->all())
            ->send(new NewBookingReceived($booking));
    }
}
