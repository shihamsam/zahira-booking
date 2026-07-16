<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        Resource::firstOrCreate(
            ['slug' => 'zahira-green-ground'],
            [
                'name' => 'Zahira Green Ground',
                'description' => 'Main school ground, suitable for cricket, football and outdoor events.',
                'location' => 'Zahira College',
                'price_per_day' => 5000,
                'is_active' => true,
            ]
        );
    }
}
