<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'resource_id',
        'full_name',
        'mobile_number',
        'nic',
        'email',
        'purpose',
        'slot_type',
        'start_time',
        'end_time',
        'hours',
        'chair_count',
        'sound_system_requested',
        'total_amount',
        'status',
        'receipt_path',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'             => 'decimal:2',
            'confirmed_at'             => 'datetime',
            'cancelled_at'             => 'datetime',
            'rejected_at'              => 'datetime',
            'sound_system_requested'   => 'boolean',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(BookingDate::class)->orderBy('date');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeIncomeCounting($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function isCancellable(): bool
    {
        return ! in_array($this->status, ['cancelled', 'rejected']);
    }
}
