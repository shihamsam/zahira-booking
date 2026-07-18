@component('mail::message')
# Payment receipt uploaded

A payment receipt has been uploaded for the following booking. Please review it and confirm or reject the booking.

| | |
|---|---|
| **Reference** | {{ $booking->reference_no }} |
| **Facility** | {{ $booking->resource->name }} |
| **Name** | {{ $booking->full_name }} |
| **Mobile** | {{ $booking->mobile_number }} |
| **Date(s)** | {{ $booking->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', ') }} |
| **Amount due** | Rs. {{ number_format($booking->total_amount, 2) }} |

@component('mail::button', ['url' => url('/admin/bookings/' . $booking->id)])
Review booking
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
