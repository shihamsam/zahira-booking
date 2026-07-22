<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\DatesUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Resource;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected PricingService $pricingService,
    ) {
    }

    public function show(Resource $resource)
    {
        abort_unless($resource->is_active, 404);

        $from = now()->startOfDay()->format('Y-m-d');
        $to   = now()->addMonths(config('booking.booking_window_months', 3))->format('Y-m-d');

        return Inertia::render('Public/ResourceShow', [
            'resource'               => $resource,
            'slots'                  => $this->pricingService->slots($resource),
            'unavailableDatesBySlot' => $this->bookingService->unavailableDatesBySlot($resource, $from, $to),
            'bookingWindow'          => ['from' => $from, 'to' => $to],
        ]);
    }

    public function booking(Request $request, Resource $resource)
    {
        abort_unless($resource->is_active, 404);

        $from = now()->startOfDay()->format('Y-m-d');
        $to   = now()->addMonths(config('booking.booking_window_months', 3))->format('Y-m-d');

        return Inertia::render('Public/BookingTimeslot', [
            'resource'               => $resource,
            'slots'                  => $this->pricingService->slots($resource),
            'unavailableDatesBySlot' => $this->bookingService->unavailableDatesBySlot($resource, $from, $to),
            'bookingWindow'          => ['from' => $from, 'to' => $to],
            'initialName'            => $request->query('name', ''),
            'initialPhone'           => $request->query('phone', ''),
            'whatsappNumber'         => config('booking.whatsapp_number'),
        ]);
    }

    public function timeslots(Request $request, Resource $resource)
    {
        abort_unless($resource->is_active, 404);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $date = $validated['date'];

        // Read booked night hours directly from the per-hour booking_dates rows.
        $bookedNightHours = BookingDate::query()
            ->where('resource_id', $resource->id)
            ->where('date', $date)
            ->whereIn('slot_type', ['night_4lights', 'night_2lights'])
            ->whereNotNull('slot_hour')
            ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
            ->pluck('slot_hour')
            ->unique()
            ->values()
            ->all();

        return response()->json(['bookedNightHours' => $bookedNightHours]);
    }

    public function availability(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'from'      => ['required', 'date_format:Y-m-d'],
            'to'        => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'slot_type' => ['nullable', 'string'],
        ]);

        return response()->json([
            'unavailableDates' => $this->bookingService->unavailableDates(
                $resource,
                $validated['from'],
                $validated['to'],
                $validated['slot_type'] ?? null,
            ),
        ]);
    }

    public function store(StoreBookingRequest $request, Resource $resource)
    {
        abort_unless($resource->is_active, 404);

        $v = $request->validated();

        try {
            $booking = $this->bookingService->createBooking(
                resource:             $resource,
                dates:                $v['dates'],
                fullName:             $v['full_name'],
                mobileNumber:         $v['mobile_number'],
                nic:                  $v['nic'] ?? null,
                purpose:              $v['purpose'] ?? null,
                slotType:             $v['slot_type'],
                slotHours:            array_map('intval', $v['slot_hours'] ?? []),
                receiptFile:          $request->file('receipt_file'),
                email:                $v['email'] ?? null,
                startTime:            $v['start_time'] ?? null,
                endTime:              $v['end_time'] ?? null,
                hours:                (int) ($v['hours'] ?? 0),
                chairCount:           (int) ($v['chair_count'] ?? 0),
                soundSystemRequested: (bool) ($v['sound_system_requested'] ?? false),
            );
        } catch (DatesUnavailableException $e) {
            return back()
                ->withErrors(['dates' => $e->getMessage()])
                ->withInput();
        }

        return redirect()->route('bookings.confirmation', $booking->reference_no);
    }

    public function confirmation(string $referenceNo)
    {
        $booking = Booking::with('resource', 'dates')
            ->where('reference_no', $referenceNo)
            ->firstOrFail();

        return Inertia::render('Public/BookingConfirmation', [
            'booking'         => $booking,
            'bank'            => config('booking.bank'),
            'whatsappNumber'  => config('booking.whatsapp_number'),
        ]);
    }
}
