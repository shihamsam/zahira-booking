<?php

namespace Tests\Feature;

use App\Jobs\AddBookingToGoogleCalendar;
use App\Jobs\RemoveBookingFromGoogleCalendar;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Mail\BookingRejected;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class AdminBookingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    private User $admin;
    private Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin    = $this->adminUser();
        $this->resource = $this->zahiraGreen();
        Storage::fake('public');
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_admin_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_can_login_with_correct_credentials(): void
    {
        $response = $this->post('/admin/login', [
            'email'    => $this->admin->email,
            'password' => 'password', // UserFactory default
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $this->post('/admin/login', [
            'email'    => $this->admin->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unauthenticated_user_is_redirected_from_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_authenticated_admin_visiting_login_redirects_to_dashboard(): void
    {
        $this->actingAs($this->admin)
             ->get('/admin/login')
             ->assertRedirect('/admin/dashboard');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function test_admin_dashboard_loads(): void
    {
        $this->actingAs($this->admin)
             ->get('/admin/dashboard')
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Admin/Dashboard')
                 ->has('stats')
                 ->has('todayBookings')
                 ->has('recentPending')
             );
    }

    // ── Booking list ──────────────────────────────────────────────────────────

    public function test_admin_can_list_bookings(): void
    {
        $this->createPendingBooking($this->resource);

        $this->actingAs($this->admin)
             ->get('/admin/bookings')
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Admin/Bookings/Index')
                 ->has('bookings.data', 1)
             );
    }

    public function test_admin_can_filter_bookings_by_status(): void
    {
        $this->createPendingBooking($this->resource);
        $this->createPendingBooking($this->resource, [], ['status' => 'confirmed']);

        $this->actingAs($this->admin)
             ->get('/admin/bookings?status=pending')
             ->assertInertia(fn (Assert $page) => $page->has('bookings.data', 1));
    }

    public function test_admin_can_view_booking_detail(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        $this->actingAs($this->admin)
             ->get("/admin/bookings/{$booking->id}")
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Admin/Bookings/Show')
                 ->where('booking.id', $booking->id)
             );
    }

    // ── Receipt upload (admin side) ───────────────────────────────────────────

    public function test_admin_can_upload_receipt(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/receipt", [
                 'receipt' => $this->fakeReceipt(),
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $booking->refresh();
        $this->assertNotNull($booking->receipt_path);
    }

    public function test_admin_receipt_upload_validates_file_type(): void
    {
        $booking = $this->createPendingBooking($this->resource);
        $badFile = UploadedFile::fake()->create('doc.txt', 10, 'text/plain');

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/receipt", ['receipt' => $badFile])
             ->assertSessionHasErrors('receipt');
    }

    // ── Confirm ───────────────────────────────────────────────────────────────

    public function test_admin_can_confirm_booking_with_receipt(): void
    {
        Mail::fake();
        Queue::fake();

        $booking = $this->createPendingBooking($this->resource, [], ['receipt_path' => 'receipts/test.jpg']);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/confirm")
             ->assertRedirect()
             ->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals($this->admin->id, $booking->confirmed_by);
        $this->assertNotNull($booking->confirmed_at);
    }

    public function test_confirming_booking_sends_email_to_booker(): void
    {
        Mail::fake();
        Queue::fake();

        $booking = $this->createPendingBooking($this->resource, [], [
            'receipt_path' => 'receipts/test.jpg',
            'email'        => 'booker@example.com',
        ]);

        $this->actingAs($this->admin)->post("/admin/bookings/{$booking->id}/confirm");

        Mail::assertQueued(BookingConfirmed::class, fn ($mail) =>
            $mail->booking->id === $booking->id &&
            $mail->hasTo('booker@example.com')
        );
    }

    public function test_confirming_booking_dispatches_google_calendar_job(): void
    {
        Mail::fake();
        Queue::fake();

        $booking = $this->createPendingBooking($this->resource, [], ['receipt_path' => 'receipts/test.jpg']);

        $this->actingAs($this->admin)->post("/admin/bookings/{$booking->id}/confirm");

        Queue::assertPushed(AddBookingToGoogleCalendar::class, fn ($job) =>
            $job->booking->id === $booking->id
        );
    }

    public function test_confirmation_fails_without_receipt(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/confirm")
             ->assertSessionHas('error');

        $booking->refresh();
        $this->assertEquals('pending', $booking->status);
    }

    public function test_confirmed_booking_cannot_be_confirmed_again(): void
    {
        $booking = $this->createPendingBooking($this->resource, [], [
            'status'       => 'confirmed',
            'receipt_path' => 'receipts/test.jpg',
        ]);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/confirm")
             ->assertSessionHas('error');
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function test_admin_can_cancel_booking(): void
    {
        Mail::fake();
        Queue::fake();

        $booking = $this->createPendingBooking($this->resource);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Payment not received'])
             ->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
        $this->assertEquals('Payment not received', $booking->cancellation_reason);
        $this->assertEquals($this->admin->id, $booking->cancelled_by);
    }

    public function test_cancelling_booking_sends_email_to_booker(): void
    {
        Mail::fake();

        $booking = $this->createPendingBooking($this->resource, [], ['email' => 'booker@example.com']);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => 'No payment']);

        Mail::assertQueued(BookingCancelled::class, fn ($mail) =>
            $mail->booking->id === $booking->id &&
            $mail->hasTo('booker@example.com')
        );
    }

    public function test_cancelling_booking_dispatches_google_calendar_removal(): void
    {
        Mail::fake();
        Queue::fake();

        $booking = $this->createPendingBooking($this->resource, [], ['google_event_id' => 'google-event-abc']);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Test']);

        Queue::assertPushed(RemoveBookingFromGoogleCalendar::class, fn ($job) =>
            $job->googleEventId === 'google-event-abc'
        );
    }

    public function test_cancel_requires_a_reason(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => ''])
             ->assertSessionHasErrors('reason');
    }

    public function test_already_cancelled_booking_cannot_be_cancelled_again(): void
    {
        $booking = $this->createPendingBooking($this->resource, [], ['status' => 'cancelled']);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Again'])
             ->assertSessionHas('error');
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function test_admin_can_reject_pending_booking(): void
    {
        Mail::fake();
        Queue::fake();

        $booking = $this->createPendingBooking($this->resource);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/reject", ['reason' => 'Receipt unclear'])
             ->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('rejected', $booking->status);
        $this->assertEquals('Receipt unclear', $booking->rejection_reason);
    }

    public function test_rejecting_booking_sends_email_to_booker(): void
    {
        Mail::fake();

        $booking = $this->createPendingBooking($this->resource, [], ['email' => 'booker@example.com']);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/reject", ['reason' => 'Invalid receipt']);

        Mail::assertQueued(BookingRejected::class, fn ($mail) =>
            $mail->booking->id === $booking->id &&
            $mail->hasTo('booker@example.com')
        );
    }

    public function test_only_pending_bookings_can_be_rejected(): void
    {
        $confirmed = $this->createPendingBooking($this->resource, [], ['status' => 'confirmed']);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$confirmed->id}/reject", ['reason' => 'Test'])
             ->assertSessionHas('error');
    }

    public function test_no_email_sent_when_booker_has_no_email(): void
    {
        Mail::fake();

        $booking = $this->createPendingBooking($this->resource, [], ['email' => null]);

        $this->actingAs($this->admin)
             ->post("/admin/bookings/{$booking->id}/reject", ['reason' => 'No email test']);

        Mail::assertNotQueued(BookingRejected::class);
    }
}
