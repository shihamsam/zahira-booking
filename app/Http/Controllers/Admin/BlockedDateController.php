<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\BookingDate;
use App\Models\Resource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlockedDateController extends Controller
{
    public function index()
    {
        $blocked = BlockedDate::with(['resource', 'createdBy'])
            ->orderBy('date')
            ->get();

        // Active booking dates grouped by resource_id so the calendar can
        // show them in a distinct colour regardless of which resource is selected.
        $bookedByResource = BookingDate::query()
            ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
            ->get(['resource_id', 'date'])
            ->groupBy('resource_id')
            ->map(fn ($rows) => $rows
                ->pluck('date')
                ->map(fn ($d) => $d->format('Y-m-d'))
                ->unique()
                ->values()
            );

        return Inertia::render('Admin/BlockedDates/Index', [
            'blockedDates'      => $blocked,
            'resources'         => Resource::orderBy('name')->get(['id', 'name']),
            'bookedByResource'  => $bookedByResource,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dates'       => ['required', 'array', 'min:1'],
            'dates.*'     => ['required', 'date_format:Y-m-d'],
            'resource_id' => ['nullable', 'exists:resources,id'],
            'reason'      => ['nullable', 'string', 'max:255'],
        ]);

        $resourceId = $validated['resource_id'] ?? null;

        // Refuse to block dates that already have active (pending/confirmed) bookings.
        $booked = BookingDate::query()
            ->whereIn('date', $validated['dates'])
            ->when($resourceId, fn ($q) => $q->where('resource_id', $resourceId))
            ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        if (! empty($booked)) {
            $scope = $resourceId ? '' : ' (across all facilities)';
            return back()
                ->withErrors(['dates' => 'The following date(s) have active bookings' . $scope . ' and cannot be blocked: ' . implode(', ', $booked)])
                ->withInput();
        }

        foreach ($validated['dates'] as $date) {
            BlockedDate::firstOrCreate(
                [
                    'date'        => $date,
                    'resource_id' => $resourceId,
                ],
                [
                    'reason'     => $validated['reason'] ?? null,
                    'created_by' => $request->user()->id,
                ]
            );
        }

        $count = count($validated['dates']);

        return back()->with('success', $count === 1 ? '1 date blocked.' : "{$count} dates blocked.");
    }

    public function destroy(Request $request, BlockedDate $blockedDate)
    {
        $blockedDate->delete();

        return back()->with('success', 'Block removed.');
    }
}
