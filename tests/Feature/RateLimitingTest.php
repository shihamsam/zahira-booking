<?php

namespace Tests\Feature;

use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class RateLimitingTest extends TestCase
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

    public function test_booking_store_is_throttled_after_ten_requests_per_minute(): void
    {
        Mail::fake();

        for ($i = 0; $i < 10; $i++) {
            $response = $this->postDaytimeBooking($this->resource, [
                'dates' => [now()->addDays(30 + $i)->format('Y-m-d')],
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        $blocked = $this->postDaytimeBooking($this->resource, [
            'dates' => [now()->addDays(100)->format('Y-m-d')],
        ]);

        $blocked->assertStatus(429);
    }

    public function test_receipt_upload_is_throttled_after_ten_requests_per_minute(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->post("/upload-receipt/{$booking->reference_no}", [
                'receipt' => $this->fakeReceipt(),
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        $blocked = $this->post("/upload-receipt/{$booking->reference_no}", [
            'receipt' => $this->fakeReceipt(),
        ]);

        $blocked->assertStatus(429);
    }

    public function test_admin_login_is_throttled_after_five_attempts_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/admin/login', [
                'email'    => 'nonexistent@zahirags.lk',
                'password' => 'wrong-password',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        $blocked = $this->post('/admin/login', [
            'email'    => 'nonexistent@zahirags.lk',
            'password' => 'wrong-password',
        ]);

        $blocked->assertStatus(429);
    }
}
