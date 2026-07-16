<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $preset] = $this->resolveRange($request);

        $resourceId = $request->input('resource_id');

        $baseQuery = Booking::query()
            ->incomeCounting()
            ->whereBetween('confirmed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($resourceId, fn ($q) => $q->where('resource_id', $resourceId));

        $totalIncome = (float) (clone $baseQuery)->sum('total_amount');
        $totalBookings = (clone $baseQuery)->count();

        $groupFormat = match ($preset) {
            'yearly' => '%Y',
            'quarterly' => '%Y-Q',
            default => '%Y-%m',
        };

        // MySQL doesn't have a native quarter format token for DATE_FORMAT,
        // so quarterly grouping is computed in PHP instead of SQL.
        $rows = (clone $baseQuery)
            ->select('confirmed_at', 'total_amount')
            ->orderBy('confirmed_at')
            ->get();

        $breakdown = $rows->groupBy(function ($row) use ($preset) {
            $date = Carbon::parse($row->confirmed_at);

            return match ($preset) {
                'yearly' => $date->format('Y'),
                'quarterly' => $date->format('Y').' Q'.$date->quarter,
                default => $date->format('Y-m'),
            };
        })->map(function ($group, $label) {
            return [
                'period' => $label,
                'bookings' => $group->count(),
                'income' => (float) $group->sum('total_amount'),
            ];
        })->values();

        $byResource = Booking::query()
            ->incomeCounting()
            ->whereBetween('confirmed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->join('resources', 'resources.id', '=', 'bookings.resource_id')
            ->groupBy('resources.id', 'resources.name')
            ->selectRaw('resources.name as resource_name, count(*) as bookings, sum(bookings.total_amount) as income')
            ->orderByDesc('income')
            ->get();

        return Inertia::render('Admin/Reports/Index', [
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'preset' => $preset,
                'resource_id' => $resourceId,
            ],
            'resources' => Resource::orderBy('name')->get(['id', 'name']),
            'summary' => [
                'total_income' => $totalIncome,
                'total_bookings' => $totalBookings,
            ],
            'breakdown' => $breakdown,
            'byResource' => $byResource,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $resourceId = $request->input('resource_id');

        $bookings = Booking::with('resource')
            ->incomeCounting()
            ->whereBetween('confirmed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($resourceId, fn ($q) => $q->where('resource_id', $resourceId))
            ->orderBy('confirmed_at')
            ->get();

        $filename = 'income-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Ground', 'Full Name', 'Mobile', 'Confirmed At', 'Amount']);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->reference_no,
                    $booking->resource->name,
                    $booking->full_name,
                    $booking->mobile_number,
                    optional($booking->confirmed_at)->format('Y-m-d H:i'),
                    $booking->total_amount,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolveRange(Request $request): array
    {
        $preset = $request->input('preset', 'monthly');

        if ($request->filled('from') && $request->filled('to')) {
            return [
                Carbon::parse($request->input('from')),
                Carbon::parse($request->input('to')),
                $preset,
            ];
        }

        $now = now();

        return match ($preset) {
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), $preset],
            'quarterly' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter(), $preset],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), $preset],
        };
    }
}
