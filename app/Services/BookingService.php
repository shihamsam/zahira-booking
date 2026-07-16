<?php

namespace App\Services;

use App\Exceptions\DatesUnavailableException;
use App\Mail\NewBookingReceived;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingService
{
    /**
     * Dates currently held by an active (pending/confirmed) booking for a resource,
     * within the given date range. Used to grey out the public calendar.
     *
     * @return array<int, string>
     */
    public function unavailableDates(Resource $resource, string $from, string $to): array
    {
        return BookingDate::query()
            ->where('resource_id', $resource->id)
            ->whereBetween('date', [$from, $to])
            ->whereHas('booking', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values()
            ->all();
    }

    /**
     * Create a booking for the given resource and dates, atomically re-checking
     * availability to prevent double-booking under concurrent requests.
     *
     * @param array<int, string> $dates Y-m-d formatted dates
     */
    public function createBooking(Resource $resource, array $dates, string $fullName, string $mobileNumber, string $purpose): Booking
    {
        $dates = collect($dates)->unique()->sort()->values()->all();

        return DB::transaction(function () use ($resource, $dates, $fullName, $mobileNumber, $purpose) {
            // Lock any existing rows for these dates on this resource so concurrent
            // requests for the same date must wait for this transaction to finish.
            $taken = BookingDate::query()
                ->where('resource_id', $resource->id)
                ->whereIn('date', $dates)
                ->whereHas('booking', fn ($q) => $q->where('status', '!=', 'cancelled')->lockForUpdate())
                ->lockForUpdate()
                ->pluck('date')
                ->map(fn ($d) => $d->format('Y-m-d'))
                ->all();

            if (! empty($taken)) {
                throw new DatesUnavailableException($taken);
            }

            $unitPrice = (float) $resource->price_per_day;
            $totalAmount = $unitPrice * count($dates);

            $booking = Booking::create([
                'reference_no' => $this->generateReferenceNo($resource),
                'resource_id' => $resource->id,
                'full_name' => $fullName,
                'mobile_number' => $mobileNumber,
                'purpose' => $purpose,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            foreach ($dates as $date) {
                $booking->dates()->create([
                    'resource_id' => $resource->id,
                    'date' => $date,
                    'unit_price' => $unitPrice,
                ]);
            }

            $this->notifyAdmins($booking->fresh(['resource', 'dates']));

            return $booking;
        });
    }

    protected function generateReferenceNo(Resource $resource): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $resource->name), 0, 3)) ?: 'GRD';
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
