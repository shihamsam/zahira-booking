<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PendingBookingAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $bookings)
    {
    }

    public function build()
    {
        $count = $this->bookings->count();

        return $this
            ->subject("Action required: {$count} pending booking(s) due within 2 days")
            ->markdown('emails.pending-booking-alert', [
                'bookings' => $this->bookings,
            ]);
    }
}
