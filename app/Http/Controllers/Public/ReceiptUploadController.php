<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ReceiptUploaded;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReceiptUploadController extends Controller
{
    /**
     * Empty lookup page — user types their reference number here.
     */
    public function show()
    {
        return Inertia::render('Public/ReceiptUpload', [
            'booking' => null,
            'error'   => null,
        ]);
    }

    /**
     * Load a booking by reference number and show the upload form.
     */
    public function booking(string $referenceNo)
    {
        $booking = Booking::with('resource', 'dates')
            ->where('reference_no', strtoupper($referenceNo))
            ->first();

        if (! $booking) {
            return Inertia::render('Public/ReceiptUpload', [
                'booking' => null,
                'error'   => "No booking found with reference number \"{$referenceNo}\". Please check and try again.",
            ]);
        }

        return Inertia::render('Public/ReceiptUpload', [
            'booking' => $booking,
            'error'   => null,
        ]);
    }

    /**
     * Store the uploaded receipt, replace any previous one, and notify admins.
     */
    public function upload(Request $request, string $referenceNo)
    {
        $booking = Booking::with('resource', 'dates')
            ->where('reference_no', strtoupper($referenceNo))
            ->firstOrFail();

        if (in_array($booking->status, ['cancelled', 'rejected'], true)) {
            return back()->withErrors([
                'receipt' => "This booking has been {$booking->status} and cannot accept a receipt.",
            ]);
        }

        $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'receipt.required' => 'Please select a file to upload.',
            'receipt.mimes'    => 'The receipt must be a JPG, PNG or PDF file.',
            'receipt.max'      => 'The file must be under 5 MB.',
        ]);

        if ($booking->receipt_path) {
            Storage::disk('public')->delete($booking->receipt_path);
        }

        $path = $request->file('receipt')->store('receipts', 'public');
        $booking->update(['receipt_path' => $path]);

        $this->notifyAdmins($booking->fresh(['resource', 'dates']));

        return back()->with('success', 'Your receipt has been uploaded successfully. We will verify it and confirm your booking shortly.');
    }

    private function notifyAdmins(Booking $booking): void
    {
        $emails = User::pluck('email')
            ->merge(config('booking.notify_extra_emails', []))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return;
        }

        Mail::to($emails->shift())
            ->cc($emails->all())
            ->send(new ReceiptUploaded($booking));
    }
}
