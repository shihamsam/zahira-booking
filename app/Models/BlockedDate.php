<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedDate extends Model
{
    protected $fillable = [
        'resource_id',
        'date',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to dates that block the given resource (resource-specific + global blocks).
     */
    public function scopeForResource($query, int $resourceId)
    {
        return $query->where(function ($q) use ($resourceId) {
            $q->where('resource_id', $resourceId)->orWhereNull('resource_id');
        });
    }
}
