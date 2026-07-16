@component('mail::message')
# Pending bookings due within 2 days

The following {{ $bookings->count() === 1 ? 'booking requires' : 'bookings require' }} your attention.
Each has a booking date within the next 2 days and payment has not yet been confirmed.
Please confirm or cancel each one as soon as possible.

@foreach ($bookings as $booking)
---

**{{ $booking->reference_no }}** &mdash; {{ $booking->resource->name }}

| | |
|---|---|
| **Name** | {{ $booking->full_name }} |
| **Mobile** | {{ $booking->mobile_number }} |
| **Date(s)** | {{ $booking->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', ') }} |
| **Amount** | Rs. {{ number_format($booking->total_amount, 2) }} |

@component('mail::button', ['url' => url('/admin/bookings/' . $booking->id)])
Review booking
@endcomponent

@endforeach

Thanks,<br>
{{ config('app.name') }}
@endcomponent
