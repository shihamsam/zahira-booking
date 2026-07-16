@component('mail::message')
# Booking not approved

Unfortunately, we were unable to approve your booking after reviewing the payment receipt.

| | |
|---|---|
| **Reference** | {{ $booking->reference_no }} |
| **Facility** | {{ $booking->resource->name }} |
| **Date(s)** | {{ $booking->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', ') }} |
| **Reason** | {{ $booking->rejection_reason }} |

If you would like to try again or need assistance, please contact us directly.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
