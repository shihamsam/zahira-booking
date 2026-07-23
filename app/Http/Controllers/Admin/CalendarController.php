<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\BookingDate;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $bookingDates = BookingDate::with(['booking.resource'])
            ->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
            ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
            ->get()
            ->groupBy(fn ($bd) => $bd->date->format('Y-m-d'));

        $blockedDates = BlockedDate::with('resource')
            ->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
            ->get()
            ->groupBy(fn ($bd) => $bd->date->format('Y-m-d'));

        $today = now()->format('Y-m-d');

        $days = collect();
        $cursor = $weekStart->copy();
        while ($cursor <= $weekEnd) {
            $dateKey = $cursor->format('Y-m-d');

            $allSlots = ($bookingDates[$dateKey] ?? collect())->map(function ($bd) {
                return [
                    'id'           => $bd->booking->id,
                    'reference_no' => $bd->booking->reference_no,
                    'full_name'    => $bd->booking->full_name,
                    'status'       => $bd->booking->status,
                    'resource'     => $bd->booking->resource->name,
                    'shortcode'    => $bd->booking->resource->shortcode,
                    'slot_type'    => $bd->slot_type,
                    'slot_hour'    => $bd->slot_hour,   // null for flat slots, 18-29 for night
                ];
            });

            // Flat (whole-day) bookings — deduplicated per booking.
            $dayBookings = $allSlots
                ->whereIn('slot_type', ['daytime', 'full_day'])
                ->unique('id')
                ->values();

            // Night slots — one entry per booked hour (slot_hour is set).
            $nightSlots = $allSlots
                ->whereNotNull('slot_hour')
                ->values();

            $dayBlocks = ($blockedDates[$dateKey] ?? collect())->map(function ($block) {
                return [
                    'id'       => $block->id,
                    'reason'   => $block->reason,
                    'resource' => $block->resource?->name,
                ];
            })->values();

            $days->push([
                'date'         => $dateKey,
                'label'        => $cursor->format('D j'),
                'is_today'     => $dateKey === $today,
                'day_bookings' => $dayBookings,   // daytime / full_day
                'night_slots'  => $nightSlots,    // per-hour night entries
                'blocked'      => $dayBlocks,
            ]);

            $cursor->addDay();
        }

        return Inertia::render('Admin/Calendar/Index', [
            'days'      => $days,
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd'   => $weekEnd->format('Y-m-d'),
            'prevWeek'  => $weekStart->copy()->subWeek()->format('Y-m-d'),
            'nextWeek'  => $weekStart->copy()->addWeek()->format('Y-m-d'),
            'resources' => Resource::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
