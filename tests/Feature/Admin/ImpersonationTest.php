<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_impersonate_a_regular_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $user));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($admin->id, session('impersonator_id'));
    }

    public function test_admin_cannot_impersonate_themselves(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $admin));

        $response->assertSessionHas('error');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_impersonate_another_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.impersonate', $otherAdmin));

        $response->assertSessionHas('error');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_regular_user_cannot_start_impersonation(): void
    {
        $user = User::factory()->create();
        $victim = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.users.impersonate', $victim));

        $response->assertForbidden();
    }

    public function test_impersonating_admin_can_stop_and_return_to_their_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.impersonate', $user));

        $response = $this->post(route('admin.stop-impersonating'));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_stop_impersonating_without_active_impersonation_just_redirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.stop-impersonating'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
