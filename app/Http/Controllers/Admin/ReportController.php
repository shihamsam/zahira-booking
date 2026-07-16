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

        $totalIncome   = (float) (clone $baseQuery)->sum('total_amount');
        $totalBookings = (clone $baseQuery)->count();

        $rows = (clone $baseQuery)
            ->select('confirmed_at', 'total_amount')
            ->orderBy('confirmed_at')
            ->get();

        $breakdown = $rows->groupBy(function ($row) use ($preset) {
            $date = Carbon::parse($row->confirmed_at);

            return match ($preset) {
                'yearly'    => $date->format('Y'),
                'quarterly' => $date->format('Y') . ' Q' . $date->quarter,
                'weekly'    => $date->format('Y') . ' W' . $date->format('W'),
                default     => $date->format('Y-m'),
            };
        })->map(function ($group, $label) {
            return [
                'period'   => $label,
                'bookings' => $group->count(),
                'income'   => (float) $group->sum('total_amount'),
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
                'from'        => $from->format('Y-m-d'),
                'to'          => $to->format('Y-m-d'),
                'preset'      => $preset,
                'resource_id' => $resourceId,
            ],
            'resources'     => Resource::orderBy('name')->get(['id', 'name']),
            'summary'       => [
                'total_income'   => $totalIncome,
                'total_bookings' => $totalBookings,
            ],
            'breakdown'  => $breakdown,
            'byResource' => $byResource,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $resourceId  = $request->input('resource_id');
        $format      = $request->input('format', 'csv');

        $bookings = Booking::with('resource')
            ->incomeCounting()
            ->whereBetween('confirmed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($resourceId, fn ($q) => $q->where('resource_id', $resourceId))
            ->orderBy('confirmed_at')
            ->get();

        $filename = 'income-report-' . $from->format('Ymd') . '-' . $to->format('Ymd');

        if ($format === 'excel') {
            return $this->exportExcel($bookings, $filename);
        }

        return $this->exportCsv($bookings, $filename);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function exportCsv($bookings, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens it correctly.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Reference', 'Facility', 'Full Name', 'Mobile', 'NIC', 'Purpose', 'Slot', 'Dates', 'Confirmed At', 'Amount (LKR)']);

            foreach ($bookings as $b) {
                fputcsv($handle, [
                    $b->reference_no,
                    $b->resource->name,
                    $b->full_name,
                    $b->mobile_number,
                    $b->nic ?? '',
                    $b->purpose,
                    $b->slot_type ?? '',
                    $b->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', '),
                    optional($b->confirmed_at)->format('Y-m-d H:i'),
                    number_format($b->total_amount, 2),
                ]);
            }

            fclose($handle);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportExcel($bookings, string $filename): StreamedResponse
    {
        // SpreadsheetML — opens natively in Excel without any extra PHP package.
        return response()->streamDownload(function () use ($bookings) {
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
                . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
            echo '<Worksheet ss:Name="Income Report"><Table>' . "\n";

            $headers = ['Reference', 'Facility', 'Full Name', 'Mobile', 'NIC', 'Purpose', 'Slot', 'Dates', 'Confirmed At', 'Amount (LKR)'];
            echo '<Row>' . implode('', array_map(fn ($h) => '<Cell><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>', $headers)) . '</Row>' . "\n";

            foreach ($bookings as $b) {
                $cells = [
                    $b->reference_no,
                    $b->resource->name,
                    $b->full_name,
                    $b->mobile_number,
                    $b->nic ?? '',
                    $b->purpose,
                    $b->slot_type ?? '',
                    $b->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', '),
                    optional($b->confirmed_at)->format('Y-m-d H:i'),
                    number_format($b->total_amount, 2),
                ];
                echo '<Row>' . implode('', array_map(fn ($v) => '<Cell><Data ss:Type="String">' . htmlspecialchars((string) $v) . '</Data></Cell>', $cells)) . '</Row>' . "\n";
            }

            echo '</Table></Worksheet></Workbook>';
        }, $filename . '.xls', ['Content-Type' => 'application/vnd.ms-excel']);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
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
            'yearly'    => [$now->copy()->startOfYear(),   $now->copy()->endOfYear(),   $preset],
            'quarterly' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter(), $preset],
            'weekly'    => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY), $preset],
            default     => [$now->copy()->startOfMonth(),  $now->copy()->endOfMonth(),  $preset],
        };
    }
}
