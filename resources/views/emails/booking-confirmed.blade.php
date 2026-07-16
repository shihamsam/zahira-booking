@component('mail::message')
# Your booking is confirmed!

Great news — your payment has been verified and your booking is now confirmed.

| | |
|---|---|
| **Reference** | {{ $booking->reference_no }} |
| **Facility** | {{ $booking->resource->name }} |
| **Name** | {{ $booking->full_name }} |
| **Date(s)** | {{ $booking->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', ') }} |
@if ($booking->slot_type && $booking->slot_type !== 'full_day')
| **Slot** | {{ ucfirst(str_replace('_', ' ', $booking->slot_type)) }} |
@endif
@if ($booking->hours)
| **Duration** | {{ $booking->hours }} hour(s) |
@endif
| **Total paid** | Rs. {{ number_format($booking->total_amount, 2) }} |

Please keep your reference number for your records. If you have any questions, feel free to contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
