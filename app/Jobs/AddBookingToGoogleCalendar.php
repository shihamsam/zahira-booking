<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddBookingToGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Booking $booking)
    {
    }

    public function handle(GoogleCalendarService $calendarService): void
    {
        $this->booking->loadMissing(['resource', 'dates']);

        $eventId = $calendarService->addEvent($this->booking);

        if ($eventId) {
            $this->booking->update(['google_event_id' => $eventId]);
        }
    }
}
