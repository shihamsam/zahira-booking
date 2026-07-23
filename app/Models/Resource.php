<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'shortcode',
        'description',
        'location',
        'image_path',
        'price_per_day',
        'pricing_overrides',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_day'     => 'decimal:2',
            'pricing_overrides' => 'array',
            'is_active'         => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingDates(): HasMany
    {
        return $this->hasMany(BookingDate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
