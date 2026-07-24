@component('mail::message')
# Reset your password

We received a request to reset the password for your Zahira admin account ({{ $user->email }}).

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

This password reset link will expire in {{ $expireMinutes }} minutes.

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
