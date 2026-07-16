<?php

namespace App\Jobs;

use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveBookingFromGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $googleEventId)
    {
    }

    public function handle(GoogleCalendarService $calendarService): void
    {
        $calendarService->removeEvent($this->googleEventId);
    }
}
