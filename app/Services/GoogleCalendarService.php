<?php

namespace App\Services;

use App\Models\Booking;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private ?Calendar $calendar = null;

    /**
     * Build and cache the Google Calendar service client.
     * Returns null if credentials are not configured, so the rest of the
     * app can continue safely without Google Calendar set up.
     */
    private function client(): ?Calendar
    {
        if ($this->calendar) {
            return $this->calendar;
        }

        $credentialsPath = config('services.google_calendar.credentials_path');
        $calendarId      = config('services.google_calendar.calendar_id');

        if (! $credentialsPath || ! $calendarId || ! file_exists($credentialsPath)) {
            return null;
        }

        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Calendar::CALENDAR_EVENTS);
        $client->fetchAccessTokenWithAssertion();

        $this->calendar = new Calendar($client);

        return $this->calendar;
    }

    /**
     * Create a Google Calendar event for a confirmed booking.
     * Returns the Google event ID to store on the booking, or null if
     * the service is not configured or the API call fails.
     */
    public function addEvent(Booking $booking): ?string
    {
        $cal = $this->client();
        if (! $cal) {
            return null;
        }

        try {
            $event  = $this->buildEvent($booking);
            $result = $cal->events->insert(
                config('services.google_calendar.calendar_id'),
                $event
            );

            return $result->getId();
        } catch (\Throwable $e) {
            Log::error('GoogleCalendarService: failed to add event', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete a Google Calendar event when a booking is cancelled or rejected.
     * Silently skips if the service is not configured or the event is missing.
     */
    public function removeEvent(string $googleEventId): void
    {
        $cal = $this->client();
        if (! $cal) {
            return;
        }

        try {
            $cal->events->delete(
                config('services.google_calendar.calendar_id'),
                $googleEventId
            );
        } catch (\Throwable $e) {
            Log::warning('GoogleCalendarService: failed to remove event', [
                'google_event_id' => $googleEventId,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function buildEvent(Booking $booking): Event
    {
        $timezone  = config('services.google_calendar.timezone', 'Asia/Colombo');
        $dates     = $booking->dates->pluck('date');
        $firstDate = $dates->first();
        $lastDate  = $dates->last();

        $isNightSlot = in_array($booking->slot_type, ['night_4lights', 'night_2lights'], true);

        if ($isNightSlot && $booking->start_time && $booking->end_time) {
            $startStr = $firstDate->format('Y-m-d') . 'T' . $booking->start_time . ':00';

            // Overnight sessions end on the next calendar day.
            $endDate  = $booking->end_time < $booking->start_time
                ? $lastDate->copy()->addDay()
                : $lastDate->copy();
            $endStr   = $endDate->format('Y-m-d') . 'T' . $booking->end_time . ':00';

            $start = new EventDateTime(['dateTime' => $startStr, 'timeZone' => $timezone]);
            $end   = new EventDateTime(['dateTime' => $endStr,   'timeZone' => $timezone]);
        } else {
            // All-day event. Google Calendar end date is exclusive so add one day.
            $start = new EventDateTime(['date' => $firstDate->format('Y-m-d')]);
            $end   = new EventDateTime(['date' => $lastDate->copy()->addDay()->format('Y-m-d')]);
        }

        $slotLabel = match ($booking->slot_type) {
            'daytime'       => 'Daytime',
            'night_4lights' => 'Night — 4 Lights',
            'night_2lights' => 'Night — 2 Lights',
            default         => null,
        };

        $summary = $booking->resource->name . ' — ' . $booking->reference_no;
        if ($slotLabel) {
            $summary .= ' (' . $slotLabel . ')';
        }

        $descriptionLines = array_filter([
            'Booked by : ' . $booking->full_name,
            'Mobile    : ' . $booking->mobile_number,
            'NIC       : ' . $booking->nic,
            'Purpose   : ' . $booking->purpose,
            'Date(s)   : ' . $dates->map(fn ($d) => $d->format('d M Y'))->join(', '),
            $booking->hours       ? 'Duration  : ' . $booking->hours . ' hour(s)' : null,
            $booking->chair_count ? 'Chairs    : ' . $booking->chair_count          : null,
            $booking->sound_system_requested ? 'Sound     : requested'               : null,
            'Amount    : Rs. ' . number_format($booking->total_amount, 2),
        ]);

        return new Event([
            'summary'     => $summary,
            'description' => implode("\n", $descriptionLines),
            'start'       => $start,
            'end'         => $end,
            'colorId'     => '2', // Sage — matches the site's green palette
        ]);
    }
}
