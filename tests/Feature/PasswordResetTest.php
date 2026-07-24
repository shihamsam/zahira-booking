<?php

namespace Tests\Feature;

use App\Mail\AdminPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    // ── Forgot password ──────────────────────────────────────────────────────

    public function test_forgot_password_page_loads(): void
    {
        $this->get('/admin/forgot-password')->assertOk();
    }

    public function test_authenticated_admin_is_redirected_from_forgot_password_page(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
             ->get('/admin/forgot-password')
             ->assertRedirect('/admin/dashboard');
    }

    public function test_submitting_forgot_password_sends_reset_email(): void
    {
        Mail::fake();
        $admin = $this->adminUser();

        $this->post('/admin/forgot-password', ['email' => $admin->email])
             ->assertRedirect()
             ->assertSessionHas('success');

        Mail::assertQueued(AdminPasswordReset::class, fn ($mail) =>
            $mail->user->id === $admin->id && $mail->hasTo($admin->email)
        );
    }

    public function test_forgot_password_requires_email(): void
    {
        $this->post('/admin/forgot-password', [])
             ->assertSessionHasErrors('email');
    }

    public function test_forgot_password_with_unknown_email_fails_validation(): void
    {
        Mail::fake();

        $this->post('/admin/forgot-password', ['email' => 'nobody@zahirags.lk'])
             ->assertSessionHasErrors('email');

        Mail::assertNothingQueued();
    }

    // ── Reset password ───────────────────────────────────────────────────────

    public function test_reset_password_page_loads_with_token(): void
    {
        $admin = $this->adminUser();
        $token = Password::createToken($admin);

        $this->get("/admin/reset-password/{$token}?email={$admin->email}")->assertOk();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $admin = $this->adminUser();
        $token = Password::createToken($admin);

        $this->post('/admin/reset-password', [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
             ->assertRedirect('/admin/login')
             ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $admin = $this->adminUser();

        $this->post('/admin/reset-password', [
            'token'                 => 'not-a-real-token',
            'email'                 => $admin->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_reset_password_requires_matching_confirmation(): void
    {
        $admin = $this->adminUser();
        $token = Password::createToken($admin);

        $this->post('/admin/reset-password', [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'does-not-match',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_password_enforces_minimum_length(): void
    {
        $admin = $this->adminUser();
        $token = Password::createToken($admin);

        $this->post('/admin/reset-password', [
            'token'                 => $token,
            'email'                 => $admin->email,
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
