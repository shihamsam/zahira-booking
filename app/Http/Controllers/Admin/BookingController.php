<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'resource_id', 'search', 'from', 'to']);

        $bookings = Booking::query()
            ->with(['resource', 'dates'])
            ->status($filters['status'] ?? null)
            ->when($filters['resource_id'] ?? null, fn ($q, $id) => $q->where('resource_id', $id))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%");
                });
            })
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Bookings/Index', [
            'bookings'  => $bookings,
            'resources' => Resource::orderBy('name')->get(['id', 'name']),
            'filters'   => $filters,
        ]);
    }

    public function show(Booking $booking)
    {
        $booking->load(['resource', 'dates', 'confirmedBy', 'cancelledBy', 'rejectedBy']);

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => $booking,
        ]);
    }

    public function uploadReceipt(Request $request, Booking $booking)
    {
        $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($booking->receipt_path) {
            Storage::disk('public')->delete($booking->receipt_path);
        }

        $path = $request->file('receipt')->store('receipts', 'public');
        $booking->update(['receipt_path' => $path]);

        return back()->with('success', 'Receipt updated.');
    }

    public function confirm(Request $request, Booking $booking)
    {
        if (! in_array($booking->status, ['pending'], true)) {
            return back()->with('error', 'Only pending bookings can be confirmed.');
        }

        if (! $booking->receipt_path) {
            return back()->with('error', 'No receipt is attached to this booking.');
        }

        $booking->update([
            'status'       => 'confirmed',
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Booking confirmed.');
    }

    public function cancel(Request $request, Booking $booking)
    {
        if (! $booking->isCancellable()) {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $booking->update([
            'status'              => 'cancelled',
            'cancelled_by'        => $request->user()->id,
            'cancelled_at'        => now(),
            'cancellation_reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Booking cancelled. Its dates are now free again.');
    }

    public function reject(Request $request, Booking $booking)
    {
        if ($booking->status !== 'pending') {
            return back()->with('error', 'Only pending bookings can be rejected.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $booking->update([
            'status'           => 'rejected',
            'rejected_by'      => $request->user()->id,
            'rejected_at'      => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        return back()->with('success', 'Booking rejected. Its dates are now free again.');
    }
}
