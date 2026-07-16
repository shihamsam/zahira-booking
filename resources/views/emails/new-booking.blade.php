@component('mail::message')
# New booking received

A new booking has been submitted and is waiting for payment confirmation.

| | |
|---|---|
| **Reference** | {{ $booking->reference_no }} |
| **Facility** | {{ $booking->resource->name }} |
| **Name** | {{ $booking->full_name }} |
| **NIC** | {{ $booking->nic ?? '—' }} |
| **Mobile** | {{ $booking->mobile_number }} |
| **Purpose** | {{ $booking->purpose }} |
| **Dates** | {{ $booking->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', ') }} |
| **Amount due** | Rs. {{ number_format($booking->total_amount, 2) }} |

The booker has uploaded their payment receipt with this booking. Please review it and confirm or reject the booking.

@component('mail::button', ['url' => url('/admin/bookings/'.$booking->id)])
Review booking
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
