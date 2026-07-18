<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class BlockedDatesTest extends TestCase
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

    // ── Admin management ──────────────────────────────────────────────────────

    public function test_blocked_dates_page_loads(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);

        $this->actingAs($admin)
             ->get('/admin/blocked-dates')
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Admin/BlockedDates/Index')
                 ->has('blockedDates')
                 ->has('resources')
             );
    }

    public function test_admin_can_block_a_single_date(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $date  = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($admin)
             ->post('/admin/blocked-dates', [
                 'dates'  => [$date],
                 'reason' => 'School sports day',
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseHas('blocked_dates', ['date' => $date, 'reason' => 'School sports day']);
    }

    public function test_admin_can_block_multiple_dates_at_once(): void
    {
        $admin  = $this->adminUser(['email' => 'admin2@test.com']);
        $dates  = [
            now()->addDays(10)->format('Y-m-d'),
            now()->addDays(11)->format('Y-m-d'),
            now()->addDays(12)->format('Y-m-d'),
        ];

        $this->actingAs($admin)
             ->post('/admin/blocked-dates', ['dates' => $dates])
             ->assertSessionHas('success', '3 dates blocked.');

        $this->assertDatabaseCount('blocked_dates', 3);
    }

    public function test_admin_can_block_a_date_for_specific_facility(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $date  = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($admin)->post('/admin/blocked-dates', [
            'dates'       => [$date],
            'resource_id' => $this->resource->id,
            'reason'      => 'Facility maintenance',
        ]);

        $this->assertDatabaseHas('blocked_dates', [
            'date'        => $date,
            'resource_id' => $this->resource->id,
        ]);
    }

    public function test_admin_can_remove_a_blocked_date(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $block = BlockedDate::create([
            'date'       => now()->addDays(10)->format('Y-m-d'),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
             ->delete("/admin/blocked-dates/{$block->id}")
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseMissing('blocked_dates', ['id' => $block->id]);
    }

    // ── Booking prevention ────────────────────────────────────────────────────

    public function test_globally_blocked_date_prevents_booking(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $date  = now()->addDays(7)->format('Y-m-d');

        // Block globally (no resource_id)
        BlockedDate::create(['date' => $date, 'created_by' => $admin->id]);

        $response = $this->postDaytimeBooking($this->resource, ['dates' => [$date]]);

        $response->assertSessionHasErrors('dates');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_facility_specific_block_prevents_booking_for_that_facility(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $date  = now()->addDays(7)->format('Y-m-d');

        // Block only for Zahira Green
        BlockedDate::create([
            'date'        => $date,
            'resource_id' => $this->resource->id,
            'created_by'  => $admin->id,
        ]);

        $response = $this->postDaytimeBooking($this->resource, ['dates' => [$date]]);

        $response->assertSessionHasErrors('dates');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_facility_specific_block_does_not_affect_other_facilities(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $hall  = $this->azwarHall();
        $date  = now()->addDays(7)->format('Y-m-d');

        // Block only for Zahira Green — Azwar Hall should still be bookable
        BlockedDate::create([
            'date'        => $date,
            'resource_id' => $this->resource->id,
            'created_by'  => $admin->id,
        ]);

        $response = $this->post("/facilities/{$hall->slug}/bookings", [
            'full_name'     => 'Hall Booker',
            'mobile_number' => '0771234567',
            'nic'           => '199012345678',
            'purpose'       => 'Event',
            'slot_type'     => 'full_day',
            'dates'         => [$date],
        ]);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_blocked_dates_show_as_unavailable_in_api(): void
    {
        $admin = $this->adminUser(['email' => 'admin2@test.com']);
        $date  = now()->addDays(7)->format('Y-m-d');

        BlockedDate::create(['date' => $date, 'created_by' => $admin->id]);

        $response = $this->getJson(
            "/facilities/{$this->resource->slug}/availability?from={$date}&to={$date}&slot_type=daytime"
        );

        $response->assertOk();
        $response->assertJsonPath('unavailableDates.0', $date);
    }
}
