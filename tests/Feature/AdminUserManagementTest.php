<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    // ── Access control ───────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_admin_list(): void
    {
        $this->get('/admin/admins')->assertRedirect('/admin/login');
    }

    public function test_regular_admin_cannot_view_admin_list(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/admin/admins')->assertForbidden();
    }

    public function test_super_admin_can_view_admin_list(): void
    {
        $super = $this->superAdminUser();
        $this->adminUser(['email' => 'other@zahirags.lk']);

        $this->actingAs($super)
             ->get('/admin/admins')
             ->assertOk()
             ->assertInertia(fn (Assert $page) => $page
                 ->component('Admin/Admins/Index')
                 ->has('admins', 2)
             );
    }

    public function test_regular_admin_cannot_create_admin_account(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post('/admin/admins', [
            'name'                  => 'New Admin',
            'email'                 => 'new@zahirags.lk',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new@zahirags.lk']);
    }

    public function test_regular_admin_cannot_delete_admin_account(): void
    {
        $admin  = $this->adminUser();
        $target = $this->adminUser(['email' => 'target@zahirags.lk']);

        $this->actingAs($admin)
             ->delete("/admin/admins/{$target->id}")
             ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    // ── Creating admins ───────────────────────────────────────────────────────

    public function test_super_admin_can_create_admin_account(): void
    {
        $super = $this->superAdminUser();

        $this->actingAs($super)
             ->post('/admin/admins', [
                 'name'                  => 'New Admin',
                 'email'                 => 'new@zahirags.lk',
                 'password'              => 'password123',
                 'password_confirmation' => 'password123',
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $created = User::where('email', 'new@zahirags.lk')->first();
        $this->assertNotNull($created);
        $this->assertSame('admin', $created->role);
        $this->assertFalse($created->isSuperAdmin());
    }

    public function test_create_admin_validates_required_fields(): void
    {
        $super = $this->superAdminUser();

        $this->actingAs($super)
             ->post('/admin/admins', [])
             ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_create_admin_requires_unique_email(): void
    {
        $super = $this->superAdminUser();
        $this->adminUser(['email' => 'taken@zahirags.lk']);

        $this->actingAs($super)
             ->post('/admin/admins', [
                 'name'                  => 'Duplicate',
                 'email'                 => 'taken@zahirags.lk',
                 'password'              => 'password123',
                 'password_confirmation' => 'password123',
             ])
             ->assertSessionHasErrors('email');
    }

    public function test_create_admin_requires_matching_password_confirmation(): void
    {
        $super = $this->superAdminUser();

        $this->actingAs($super)
             ->post('/admin/admins', [
                 'name'                  => 'New Admin',
                 'email'                 => 'new@zahirags.lk',
                 'password'              => 'password123',
                 'password_confirmation' => 'does-not-match',
             ])
             ->assertSessionHasErrors('password');
    }

    // ── Deleting admins ───────────────────────────────────────────────────────

    public function test_super_admin_can_delete_regular_admin(): void
    {
        $super  = $this->superAdminUser();
        $target = $this->adminUser(['email' => 'target@zahirags.lk']);

        $this->actingAs($super)
             ->delete("/admin/admins/{$target->id}")
             ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $super = $this->superAdminUser();
        $this->adminUser(['email' => 'other@zahirags.lk']);

        $this->actingAs($super)
             ->delete("/admin/admins/{$super->id}")
             ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $super->id]);
    }

    public function test_super_admin_account_cannot_be_removed_by_another_super_admin(): void
    {
        $super       = $this->superAdminUser();
        $otherSuper  = $this->superAdminUser(['email' => 'super2@zahirags.lk']);

        $this->actingAs($super)
             ->delete("/admin/admins/{$otherSuper->id}")
             ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $otherSuper->id]);
    }
}
