<?php

namespace App\Console\Commands;

use App\Jobs\RemoveBookingFromGoogleCalendar;
use App\Mail\BookingCancelled;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AutoCancelOverduePendingBookings extends Command
{
    protected $signature   = 'bookings:auto-cancel-overdue';
    protected $description = 'Cancel pending bookings whose payment deadline has passed, freeing their dates.';

    public function handle(): int
    {
        $hours = config('booking.payment_deadline_hours');

        if ($hours <= 0) {
            $this->info('Auto-cancellation is disabled (BOOKING_PAYMENT_DEADLINE_HOURS is 0).');
            return self::SUCCESS;
        }

        $cutoff = now()->subHours($hours);

        $bookings = Booking::with(['resource', 'dates'])
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No overdue pending bookings found.');
            return self::SUCCESS;
        }

        foreach ($bookings as $booking) {
            $booking->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => "Automatically cancelled — payment not confirmed within {$hours} hour(s) of booking.",
            ]);

            if ($booking->email) {
                Mail::to($booking->email)->send(new BookingCancelled($booking));
            }

            if ($booking->google_event_id) {
                RemoveBookingFromGoogleCalendar::dispatch($booking->google_event_id);
            }
        }

        $this->info("Auto-cancelled {$bookings->count()} overdue pending booking(s).");

        return self::SUCCESS;
    }
}
