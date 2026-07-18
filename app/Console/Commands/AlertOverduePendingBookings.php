<?php

namespace App\Console\Commands;

use App\Mail\PendingBookingAlert;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AlertOverduePendingBookings extends Command
{
    protected $signature   = 'bookings:alert-pending';
    protected $description = 'Email admins about pending bookings whose earliest date is within 2 days.';

    public function handle(): int
    {
        $cutoff = now()->addDays(2)->endOfDay();

        $bookings = Booking::with(['resource', 'dates'])
            ->where('status', 'pending')
            ->whereHas('dates', fn ($q) => $q->where('date', '<=', $cutoff->format('Y-m-d')))
            ->get()
            // Keep only bookings where the earliest date is genuinely within 2 days.
            ->filter(fn ($b) => $b->dates->min('date')?->format('Y-m-d') <= $cutoff->format('Y-m-d'))
            ->values();

        if ($bookings->isEmpty()) {
            $this->info('No at-risk pending bookings found.');
            return self::SUCCESS;
        }

        $emails = User::pluck('email')
            ->merge(config('booking.notify_extra_emails', []))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            $this->warn('No admin emails configured.');
            return self::SUCCESS;
        }

        Mail::to($emails->shift())
            ->cc($emails->all())
            ->send(new PendingBookingAlert($bookings));

        $this->info("Alert sent for {$bookings->count()} pending booking(s).");

        return self::SUCCESS;
    }
}
