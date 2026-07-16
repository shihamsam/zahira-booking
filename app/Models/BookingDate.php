<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDate extends Model
{
    protected $fillable = [
        'booking_id',
        'resource_id',
        'date',
        'slot_type',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'unit_price' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
