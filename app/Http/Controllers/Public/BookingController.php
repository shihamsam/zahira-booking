<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\DatesUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Resource;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService)
    {
    }

    public function show(Resource $resource)
    {
        abort_unless($resource->is_active, 404);

        $from = now()->startOfDay()->format('Y-m-d');
        $to = now()->addMonths(config('booking.booking_window_months', 3))->format('Y-m-d');

        return Inertia::render('Public/ResourceShow', [
            'resource' => $resource,
            'unavailableDates' => $this->bookingService->unavailableDates($resource, $from, $to),
            'bookingWindow' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function availability(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json([
            'unavailableDates' => $this->bookingService->unavailableDates(
                $resource, $validated['from'], $validated['to']
            ),
        ]);
    }

    public function store(StoreBookingRequest $request, Resource $resource)
    {
        abort_unless($resource->is_active, 404);

        $validated = $request->validated();

        try {
            $booking = $this->bookingService->createBooking(
                $resource,
                $validated['dates'],
                $validated['full_name'],
                $validated['mobile_number'],
                $validated['purpose'],
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
            'bank' => config('booking.bank'),
            'whatsappNumber' => config('booking.whatsapp_number'),
        ]);
    }
}
