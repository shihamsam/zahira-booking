@component('mail::message')
# New booking received

A new booking has been made and is waiting for payment confirmation.

| | |
|---|---|
| **Reference** | {{ $booking->reference_no }} |
| **Ground** | {{ $booking->resource->name }} |
| **Name** | {{ $booking->full_name }} |
| **Mobile** | {{ $booking->mobile_number }} |
| **Purpose** | {{ $booking->purpose }} |
| **Dates** | {{ $booking->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', ') }} |
| **Amount due** | Rs. {{ number_format($booking->total_amount, 2) }} |

The depositor has been asked to send their bank deposit receipt to the WhatsApp number on file. Please check for it and confirm the booking once verified.

@component('mail::button', ['url' => url('/admin/bookings/'.$booking->id)])
View booking
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
