<?php

namespace Tests\Feature;

use App\Mail\PendingBookingAlert;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class PendingAlertCommandTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    private Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser();
        $this->resource = $this->zahiraGreen();
        Storage::fake('public');
    }

    public function test_command_sends_alert_for_booking_due_in_two_days(): void
    {
        Mail::fake();
        $date = now()->addDays(2)->format('Y-m-d');
        $this->createPendingBooking($this->resource, [$date]);

        $this->artisan('bookings:alert-pending')->assertSuccessful();

        Mail::assertQueued(PendingBookingAlert::class, fn ($mail) =>
            $mail->bookings->count() === 1
        );
    }

    public function test_command_sends_alert_for_booking_due_tomorrow(): void
    {
        Mail::fake();
        $date = now()->addDay()->format('Y-m-d');
        $this->createPendingBooking($this->resource, [$date]);

        $this->artisan('bookings:alert-pending')->assertSuccessful();

        Mail::assertQueued(PendingBookingAlert::class);
    }

    public function test_command_sends_alert_for_booking_due_today(): void
    {
        Mail::fake();
        $this->createPendingBooking($this->resource, [now()->format('Y-m-d')]);

        $this->artisan('bookings:alert-pending')->assertSuccessful();

        Mail::assertQueued(PendingBookingAlert::class);
    }

    public function test_command_does_not_alert_for_booking_due_in_three_days(): void
    {
        Mail::fake();
        $date = now()->addDays(3)->format('Y-m-d');
        $this->createPendingBooking($this->resource, [$date]);

        $this->artisan('bookings:alert-pending')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }

    public function test_command_does_not_alert_for_confirmed_bookings(): void
    {
        Mail::fake();
        $date = now()->addDay()->format('Y-m-d');
        $this->createPendingBooking($this->resource, [$date], ['status' => 'confirmed']);

        $this->artisan('bookings:alert-pending')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }

    public function test_command_does_not_alert_for_cancelled_bookings(): void
    {
        Mail::fake();
        $this->createPendingBooking($this->resource, [now()->addDay()->format('Y-m-d')], ['status' => 'cancelled']);

        $this->artisan('bookings:alert-pending')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }

    public function test_command_includes_all_at_risk_bookings_in_single_email(): void
    {
        Mail::fake();
        $this->createPendingBooking($this->resource, [now()->format('Y-m-d')]);
        $this->createPendingBooking($this->resource, [now()->addDay()->format('Y-m-d')]);
        $this->createPendingBooking($this->resource, [now()->addDays(2)->format('Y-m-d')]);

        $this->artisan('bookings:alert-pending')->assertSuccessful();

        // All three at-risk bookings are included in a single alert email
        Mail::assertQueued(PendingBookingAlert::class, fn ($mail) =>
            $mail->bookings->count() === 3
        );
        Mail::assertQueuedCount(1);
    }

    public function test_command_outputs_informational_message_when_no_at_risk_bookings(): void
    {
        Mail::fake();

        $this->artisan('bookings:alert-pending')
             ->assertSuccessful()
             ->expectsOutputToContain('No at-risk');

        Mail::assertNothingOutgoing();
    }
}
