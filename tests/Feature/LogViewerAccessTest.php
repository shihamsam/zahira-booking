<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class LogViewerAccessTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/log-viewer')->assertRedirect('/admin/login');
    }

    public function test_regular_admin_is_forbidden(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/log-viewer')->assertForbidden();
    }

    public function test_super_admin_can_access_log_viewer(): void
    {
        $super = $this->superAdminUser();

        $this->actingAs($super)->get('/log-viewer')->assertOk();
    }
}
