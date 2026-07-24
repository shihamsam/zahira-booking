<?php

namespace Tests\Feature\Concerns;

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait CreatesTestData
{
    protected function adminUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'admin@zahirags.lk',
        ], $attrs));
    }

    protected function superAdminUser(array $attrs = []): User
    {
        return $this->adminUser(array_merge([
            'email' => 'superadmin@zahirags.lk',
            'role'  => 'super_admin',
        ], $attrs));
    }

    protected function zahiraGreen(array $attrs = []): Resource
    {
        return Resource::create(array_merge([
            'name'          => 'Zahira Green Ground',
            'slug'          => 'zahira-green-ground',
            'description'   => 'Main school ground.',
            'location'      => 'Zahira College',
            'price_per_day' => 6000,
            'is_active'     => true,
        ], $attrs));
    }

    protected function azwarHall(array $attrs = []): Resource
    {
        return Resource::create(array_merge([
            'name'          => 'Azwar Hall',
            'slug'          => 'azwar-hall',
            'description'   => 'Indoor event hall.',
            'location'      => 'Zahira College',
            'price_per_day' => 10000,
            'is_active'     => true,
        ], $attrs));
    }

    /**
     * POST a valid daytime booking to the given resource. Returns the HTTP response.
     */
    protected function postDaytimeBooking(Resource $resource, array $overrides = [])
    {
        $date = now()->addDays(7)->format('Y-m-d');

        return $this->post("/facilities/{$resource->slug}/bookings", array_merge([
            'full_name'     => 'Test Booker',
            'mobile_number' => '0771234567',
            'nic'           => '199012345678',
            'email'         => 'booker@example.com',
            'purpose'       => 'Cricket practice',
            'slot_type'     => 'daytime',
            'dates'         => [$date],
        ], $overrides));
    }

    /**
     * Create a booking record directly in the DB (bypasses HTTP flow).
     */
    protected function createPendingBooking(Resource $resource, array $dates = [], array $attrs = []): Booking
    {
        if (empty($dates)) {
            $dates = [now()->addDays(7)->format('Y-m-d')];
        }

        $booking = Booking::create(array_merge([
            'reference_no'  => 'TEST-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4)),
            'resource_id'   => $resource->id,
            'full_name'     => 'Test Booker',
            'mobile_number' => '0771234567',
            'nic'           => '199012345678',
            'email'         => 'booker@example.com',
            'purpose'       => 'Cricket practice',
            'slot_type'     => 'daytime',
            'total_amount'  => 6000 * count($dates),
            'status'        => 'pending',
        ], $attrs));

        foreach ($dates as $date) {
            BookingDate::create([
                'booking_id'  => $booking->id,
                'resource_id' => $resource->id,
                'date'        => $date,
                'slot_type'   => $booking->slot_type ?? 'daytime',
                'unit_price'  => 6000,
            ]);
        }

        return $booking->fresh(['resource', 'dates']);
    }

    protected function fakeReceipt(string $name = 'receipt.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name);
    }

    protected function fakePdf(string $name = 'receipt.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, 'application/pdf');
    }
}
