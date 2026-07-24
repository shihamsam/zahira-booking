<?php

namespace Tests\Feature;

use App\Jobs\RemoveBookingFromGoogleCalendar;
use App\Mail\BookingCancelled;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class AutoCancelOverdueBookingsCommandTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    private Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser();
        $this->resource = $this->zahiraGreen();
        Storage::fake('public');
        config(['booking.payment_deadline_hours' => 3]);
    }

    private function ageBooking(Booking $booking, int $hoursAgo): void
    {
        Booking::where('id', $booking->id)->update([
            'created_at' => now()->subHours($hoursAgo),
        ]);
    }

    public function test_command_cancels_pending_booking_older_than_deadline(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource);
        $this->ageBooking($booking, 4);

        $this->artisan('bookings:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->cancellation_reason);
    }

    public function test_command_does_not_cancel_pending_booking_within_deadline(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource);
        $this->ageBooking($booking, 1);

        $this->artisan('bookings:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame('pending', $booking->fresh()->status);
        Mail::assertNothingOutgoing();
    }

    public function test_command_does_not_cancel_confirmed_bookings(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource, attrs: ['status' => 'confirmed']);
        $this->ageBooking($booking, 10);

        $this->artisan('bookings:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame('confirmed', $booking->fresh()->status);
        Mail::assertNothingOutgoing();
    }

    public function test_command_does_not_cancel_already_cancelled_bookings(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource, attrs: ['status' => 'cancelled']);
        $this->ageBooking($booking, 10);

        $this->artisan('bookings:auto-cancel-overdue')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }

    public function test_command_sends_cancellation_email_to_booker(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource, attrs: ['email' => 'booker@example.com']);
        $this->ageBooking($booking, 5);

        $this->artisan('bookings:auto-cancel-overdue')->assertSuccessful();

        Mail::assertQueued(BookingCancelled::class, fn ($mail) => $mail->booking->id === $booking->id);
    }

    public function test_command_dispatches_calendar_removal_job_when_google_event_exists(): void
    {
        Mail::fake();
        Queue::fake();
        $booking = $this->createPendingBooking($this->resource, attrs: ['google_event_id' => 'evt_123']);
        $this->ageBooking($booking, 5);

        $this->artisan('bookings:auto-cancel-overdue')->assertSuccessful();

        Queue::assertPushed(RemoveBookingFromGoogleCalendar::class);
    }

    public function test_command_respects_configurable_deadline_hours(): void
    {
        Mail::fake();
        config(['booking.payment_deadline_hours' => 1]);
        $booking = $this->createPendingBooking($this->resource);
        $this->ageBooking($booking, 2);

        $this->artisan('bookings:auto-cancel-overdue')->assertSuccessful();

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_command_is_disabled_when_deadline_hours_is_zero(): void
    {
        Mail::fake();
        config(['booking.payment_deadline_hours' => 0]);
        $booking = $this->createPendingBooking($this->resource);
        $this->ageBooking($booking, 100);

        $this->artisan('bookings:auto-cancel-overdue')
             ->assertSuccessful()
             ->expectsOutputToContain('disabled');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_command_outputs_informational_message_when_none_overdue(): void
    {
        Mail::fake();

        $this->artisan('bookings:auto-cancel-overdue')
             ->assertSuccessful()
             ->expectsOutputToContain('No overdue');

        Mail::assertNothingOutgoing();
    }
}
