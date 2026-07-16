<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDate;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now   = now();
        $today = $now->format('Y-m-d');

        $stats = [
            'pending_count' => Booking::where('status', 'pending')->count(),
            'confirmed_this_month' => Booking::incomeCounting()
                ->whereMonth('confirmed_at', $now->month)
                ->whereYear('confirmed_at', $now->year)
                ->count(),
            'income_this_month' => (float) Booking::incomeCounting()
                ->whereMonth('confirmed_at', $now->month)
                ->whereYear('confirmed_at', $now->year)
                ->sum('total_amount'),
            'total_bookings' => Booking::count(),
            'today_count' => BookingDate::where('date', $today)
                ->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
                ->distinct('booking_id')
                ->count('booking_id'),
            'today_income' => (float) Booking::incomeCounting()
                ->whereHas('dates', fn ($q) => $q->where('date', $today))
                ->sum('total_amount'),
        ];

        $todayBookings = Booking::with(['resource', 'dates' => fn ($q) => $q->where('date', $today)])
            ->whereHas('dates', fn ($q) => $q->where('date', $today))
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->orderBy('created_at')
            ->get();

        $upcoming = Booking::with(['resource', 'dates' => fn ($q) => $q->where('date', '>=', $today)])
            ->whereHas('dates', fn ($q) => $q->where('date', '>=', $today))
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $recentPending = Booking::with('resource')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats'         => $stats,
            'todayBookings' => $todayBookings,
            'upcoming'      => $upcoming,
            'recentPending' => $recentPending,
        ]);
    }
}
