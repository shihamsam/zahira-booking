<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();

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
        ];

        $upcoming = Booking::with(['resource', 'dates' => fn ($q) => $q->where('date', '>=', $now->format('Y-m-d'))])
            ->whereHas('dates', fn ($q) => $q->where('date', '>=', $now->format('Y-m-d')))
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $recentPending = Booking::with('resource')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming,
            'recentPending' => $recentPending,
        ]);
    }
}
