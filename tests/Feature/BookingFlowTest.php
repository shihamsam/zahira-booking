<?php

namespace Tests\Feature;

use App\Mail\NewBookingReceived;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    private Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser();           // needed so notifyAdmins() has recipients
        $this->resource = $this->zahiraGreen();
        Storage::fake('public');
    }

    // ── Public pages ──────────────────────────────────────────────────────────

    public function test_home_page_returns_200_and_lists_active_facilities(): void
    {
        $this->azwarHall();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('resources', 2)
            ->where('resources.0.slug', 'zahira-green-ground') // pinned first
        );
    }

    public function test_inactive_facility_is_hidden_from_home(): void
    {
        $this->zahiraGreen(['slug' => 'zahira-green-copy', 'is_active' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->has('resources', 1));
    }

    public function test_facility_page_loads_with_slots_and_availability(): void
    {
        $response = $this->get("/facilities/{$this->resource->slug}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/ResourceShow')
            ->has('resource')
            ->has('slots')
            ->has('unavailableDatesBySlot')
            ->has('bookingWindow')
        );
    }

    public function test_facility_page_returns_404_for_inactive_resource(): void
    {
        $inactive = $this->zahiraGreen(['slug' => 'inactive-ground', 'is_active' => false]);

        $this->get("/facilities/{$inactive->slug}")->assertNotFound();
    }

    public function test_old_grounds_url_redirects_permanently(): void
    {
        $this->get("/grounds/{$this->resource->slug}")
             ->assertRedirectContains("/facilities/{$this->resource->slug}")
             ->assertStatus(301);
    }

    // ── Booking creation ──────────────────────────────────────────────────────

    public function test_booking_can_be_created_without_receipt(): void
    {
        Mail::fake();

        $response = $this->postDaytimeBooking($this->resource);

        $booking = Booking::first();
        $this->assertNotNull($booking, 'Booking was not created.');
        $this->assertEquals('pending', $booking->status);
        $this->assertNull($booking->receipt_path);

        $response->assertRedirect("/bookings/{$booking->reference_no}/confirmation");

        Mail::assertQueued(NewBookingReceived::class, fn ($mail) =>
            $mail->booking->id === $booking->id
        );
    }

    public function test_booking_can_be_created_with_receipt(): void
    {
        Mail::fake();
        $date = now()->addDays(7)->format('Y-m-d');

        $this->postDaytimeBooking($this->resource, ['receipt_file' => $this->fakeReceipt()]);

        $booking = Booking::first();
        $this->assertNotNull($booking->receipt_path, 'Receipt path should be stored.');
        Mail::assertQueued(NewBookingReceived::class);
    }

    public function test_booking_form_validates_required_fields(): void
    {
        $response = $this->post("/facilities/{$this->resource->slug}/bookings", []);

        $response->assertSessionHasErrors(['full_name', 'mobile_number', 'nic', 'slot_type', 'dates']);
    }

    public function test_night_slot_requires_times_and_hours(): void
    {
        $response = $this->post("/facilities/{$this->resource->slug}/bookings", [
            'full_name'     => 'Test',
            'mobile_number' => '0771234567',
            'nic'           => '199012345678',
            'purpose'       => 'Match',
            'slot_type'     => 'night_4lights',
            'dates'         => [now()->addDays(7)->format('Y-m-d')],
        ]);

        $response->assertSessionHasErrors(['start_time', 'end_time', 'hours']);
    }

    public function test_nighttime_booking_calculates_hourly_price(): void
    {
        Mail::fake();
        $date = now()->addDays(7)->format('Y-m-d');

        $this->post("/facilities/{$this->resource->slug}/bookings", [
            'full_name'     => 'Night Booker',
            'mobile_number' => '0771234567',
            'nic'           => '199012345678',
            'purpose'       => 'Night match',
            'slot_type'     => 'night_4lights',
            'start_time'    => '18:30',
            'end_time'      => '22:30',
            'hours'         => 4,
            'dates'         => [$date],
        ]);

        $booking = Booking::first();
        // 4 lights = Rs. 3,500/hr × 4 hrs = Rs. 14,000
        $this->assertEquals('14000.00', $booking->total_amount);
    }

    // ── Double-booking prevention ─────────────────────────────────────────────

    public function test_same_slot_on_same_date_cannot_be_double_booked(): void
    {
        $date = now()->addDays(7)->format('Y-m-d');

        // First booking succeeds
        $this->postDaytimeBooking($this->resource, ['dates' => [$date]]);
        $this->assertDatabaseCount('bookings', 1);

        // Second booking on the same date + slot fails
        $response = $this->postDaytimeBooking($this->resource, [
            'full_name' => 'Second Booker',
            'nic'       => '200012345678',
            'dates'     => [$date],
        ]);

        $response->assertSessionHasErrors('dates');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_different_slots_on_same_date_are_independently_bookable(): void
    {
        Mail::fake();
        $date = now()->addDays(7)->format('Y-m-d');

        // Daytime booking
        $this->postDaytimeBooking($this->resource, ['dates' => [$date]]);

        // Nighttime booking on same date — should succeed
        $response = $this->post("/facilities/{$this->resource->slug}/bookings", [
            'full_name'     => 'Night Booker',
            'mobile_number' => '0779876543',
            'nic'           => '200087654321',
            'purpose'       => 'Night match',
            'slot_type'     => 'night_4lights',
            'start_time'    => '18:30',
            'end_time'      => '22:30',
            'hours'         => 4,
            'dates'         => [$date],
        ]);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_cancelled_booking_frees_its_dates(): void
    {
        Mail::fake();
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $date  = now()->addDays(7)->format('Y-m-d');

        $this->postDaytimeBooking($this->resource, ['dates' => [$date]]);
        $booking = Booking::first();

        // Admin cancels it
        $this->actingAs($admin)
             ->post("/admin/bookings/{$booking->id}/cancel", ['reason' => 'Test']);

        // Now the same date should be bookable again
        $this->postDaytimeBooking($this->resource, ['full_name' => 'New Booker', 'nic' => '200099999999', 'dates' => [$date]]);

        $this->assertDatabaseCount('bookings', 2);
    }

    // ── Booking confirmation page ─────────────────────────────────────────────

    public function test_confirmation_page_loads_for_valid_reference(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        $response = $this->get("/bookings/{$booking->reference_no}/confirmation");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/BookingConfirmation')
            ->where('booking.reference_no', $booking->reference_no)
        );
    }

    public function test_azwar_hall_full_day_booking(): void
    {
        Mail::fake();
        $hall = $this->azwarHall();
        $date = now()->addDays(7)->format('Y-m-d');

        $this->post("/facilities/{$hall->slug}/bookings", [
            'full_name'     => 'Event Organiser',
            'mobile_number' => '0771234567',
            'nic'           => '199012345678',
            'purpose'       => 'Annual dinner',
            'slot_type'     => 'full_day',
            'chair_count'   => 100,
            'dates'         => [$date],
        ]);

        $booking = Booking::first();
        // Hall rent 10,000 + 100 chairs × 10 = 11,000
        $this->assertEquals('11000.00', $booking->total_amount);
        $this->assertEquals(100, $booking->chair_count);
    }
}
