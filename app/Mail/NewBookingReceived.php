<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewBookingReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build()
    {
        return $this->subject("New booking: {$this->booking->resource->name} — {$this->booking->reference_no}")
            ->markdown('emails.new-booking', [
                'booking' => $this->booking,
            ]);
    }
}
