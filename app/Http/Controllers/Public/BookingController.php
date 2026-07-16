<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\DatesUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
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
                nic:                  $v['nic'],
                purpose:              $v['purpose'],
                slotType:             $v['slot_type'],
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
            'booking' => $booking,
            'bank'    => config('booking.bank'),
        ]);
    }
}
