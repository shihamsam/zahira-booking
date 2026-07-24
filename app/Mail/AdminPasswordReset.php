<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminPasswordReset extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $token)
    {
    }

    public function build()
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $this->user->email,
        ], false));

        return $this
            ->subject('Reset Your Admin Password')
            ->markdown('emails.admin-password-reset', [
                'user' => $this->user,
                'url' => $url,
                'expireMinutes' => config('auth.passwords.users.expire'),
            ]);
    }
}
