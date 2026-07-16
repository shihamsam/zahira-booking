@component('mail::message')
# Your booking has been cancelled

We're writing to let you know that your booking has been cancelled.

| | |
|---|---|
| **Reference** | {{ $booking->reference_no }} |
| **Facility** | {{ $booking->resource->name }} |
| **Date(s)** | {{ $booking->dates->pluck('date')->map(fn ($d) => $d->format('d M Y'))->join(', ') }} |
| **Reason** | {{ $booking->cancellation_reason }} |

If you believe this is an error or would like to make a new booking, please contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
