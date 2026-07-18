<?php

namespace Tests\Feature;

use App\Mail\ReceiptUploaded;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ReceiptUploadTest extends TestCase
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

    // ── Lookup page ───────────────────────────────────────────────────────────

    public function test_upload_receipt_page_loads(): void
    {
        $this->get('/upload-receipt')
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Public/ReceiptUpload')
                 ->where('booking', null)
             );
    }

    public function test_lookup_by_valid_reference_shows_booking(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        $this->get("/upload-receipt/{$booking->reference_no}")
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Public/ReceiptUpload')
                 ->where('booking.reference_no', $booking->reference_no)
                 ->where('error', null)
             );
    }

    public function test_lookup_with_invalid_reference_shows_error(): void
    {
        $this->get('/upload-receipt/NOTEXIST-0000-XXXX')
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Public/ReceiptUpload')
                 ->where('booking', null)
                 ->whereNot('error', null)
             );
    }

    public function test_lookup_is_case_insensitive(): void
    {
        $booking = $this->createPendingBooking($this->resource);
        $lower   = strtolower($booking->reference_no);

        $this->get("/upload-receipt/{$lower}")
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page->where('booking.id', $booking->id));
    }

    // ── File upload ───────────────────────────────────────────────────────────

    public function test_receipt_can_be_uploaded_for_pending_booking(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource);

        $response = $this->post("/upload-receipt/{$booking->reference_no}", [
            'receipt' => $this->fakeReceipt(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertNotNull($booking->receipt_path, 'Receipt path should be stored on the booking.');
        Storage::disk('public')->assertExists($booking->receipt_path);
    }

    public function test_uploading_receipt_notifies_admins(): void
    {
        Mail::fake();
        $this->adminUser(['email' => 'second-admin@test.com']);
        $booking = $this->createPendingBooking($this->resource);

        $this->post("/upload-receipt/{$booking->reference_no}", ['receipt' => $this->fakeReceipt()]);

        Mail::assertQueued(ReceiptUploaded::class, fn ($mail) => $mail->booking->id === $booking->id);
    }

    public function test_receipt_can_be_replaced(): void
    {
        Mail::fake();
        // Booking that already has a receipt
        $booking = $this->createPendingBooking($this->resource, [], ['receipt_path' => 'receipts/old.jpg']);

        $this->post("/upload-receipt/{$booking->reference_no}", ['receipt' => $this->fakePdf()]);

        $booking->refresh();
        $this->assertStringContainsString('.pdf', $booking->receipt_path);
    }

    public function test_upload_rejected_for_cancelled_booking(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource, [], ['status' => 'cancelled']);

        $response = $this->post("/upload-receipt/{$booking->reference_no}", [
            'receipt' => $this->fakeReceipt(),
        ]);

        $response->assertSessionHasErrors('receipt');
        Mail::assertNothingOutgoing();
    }

    public function test_upload_rejected_for_rejected_booking(): void
    {
        Mail::fake();
        $booking = $this->createPendingBooking($this->resource, [], ['status' => 'rejected']);

        $response = $this->post("/upload-receipt/{$booking->reference_no}", [
            'receipt' => $this->fakeReceipt(),
        ]);

        $response->assertSessionHasErrors('receipt');
        Mail::assertNothingOutgoing();
    }

    public function test_upload_validates_file_type(): void
    {
        $booking  = $this->createPendingBooking($this->resource);
        $badFile  = UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream');

        $this->post("/upload-receipt/{$booking->reference_no}", ['receipt' => $badFile])
             ->assertSessionHasErrors('receipt');
    }

    public function test_upload_requires_a_file(): void
    {
        $booking = $this->createPendingBooking($this->resource);

        $this->post("/upload-receipt/{$booking->reference_no}", [])
             ->assertSessionHasErrors('receipt');
    }
}
