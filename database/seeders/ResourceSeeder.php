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
                'name'          => 'Zahira Green Ground',
                'shortcode'     => 'ZGG',
                'description'   => 'Main school ground, suitable for cricket, football and outdoor events.',
                'location'      => 'Zahira College',
                'price_per_day' => 6000,
                'is_active'     => true,
            ]
        );

        Resource::firstOrCreate(
            ['slug' => 'azwar-hall'],
            [
                'name'          => 'Azwar Hall',
                'shortcode'     => 'AZW',
                'description'   => 'Indoor event hall suitable for functions, seminars, and gatherings. Sound system available on request.',
                'location'      => 'Zahira College',
                'price_per_day' => 10000,
                'is_active'     => true,
            ]
        );
    }
}
