<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $other = User::factory()->create(['name' => 'Jana Testovací']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Jana Testovací');
    }

    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Jana Testovací', 'email' => 'jana@example.com']);
        User::factory()->create(['name' => 'Petr Jiný', 'email' => 'petr@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Jana']));

        $response->assertSee('Jana Testovací');
        $response->assertDontSee('Petr Jiný');
    }

    public function test_admin_can_view_user_detail_with_their_wishlists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create(['title' => 'Narozeninový seznam']);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

        $response->assertOk();
        $response->assertSee('Narozeninový seznam');
    }

    public function test_admin_can_update_a_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => 'Original', 'email' => 'original@example.com']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect(route('admin.users.show', $user));
        $this->assertSame('Updated Name', $user->refresh()->name);
        $this->assertSame('updated@example.com', $user->email);
    }

    public function test_admin_can_grant_admin_rights_to_another_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => '1',
        ]);

        $this->assertTrue($user->refresh()->is_admin);
    }

    public function test_last_admin_cannot_remove_their_own_admin_rights(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($admin->refresh()->is_admin);
    }

    public function test_admin_can_remove_their_own_admin_rights_if_another_admin_exists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
        ]);

        $this->assertFalse($admin->refresh()->is_admin);
    }

    public function test_admin_can_delete_a_user_and_their_wishlists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_reset_a_users_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $originalHash = $user->password;

        $response = $this->actingAs($admin)->post(route('admin.users.reset-password', $user));

        $response->assertRedirect(route('admin.users.show', $user));
        $response->assertSessionHas('generated_password');
        $this->assertNotSame($originalHash, $user->refresh()->password);
    }

    public function test_resetting_password_invalidates_existing_sessions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode('test'),
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->post(route('admin.users.reset-password', $user));

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_admin_policy_bypass_allows_editing_someone_elses_wishlist(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create(['title' => 'Original']);

        $response = $this->actingAs($admin)->put(route('wishlists.update', $wishlist), [
            'title' => 'Admin Edited',
            'occasion' => 'birthday',
        ]);

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertSame('Admin Edited', $wishlist->refresh()->title);
    }

    public function test_admin_policy_bypass_allows_viewing_someone_elses_wishlist(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->actingAs($admin)->get(route('wishlists.show', $wishlist));

        $response->assertOk();
    }

    public function test_admin_can_manually_verify_a_users_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.verify-email', $user));

        $response->assertRedirect(route('admin.users.show', $user));
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_regular_user_cannot_verify_someone_elses_email(): void
    {
        $user = User::factory()->create();
        $victim = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('admin.users.verify-email', $victim));

        $response->assertForbidden();
        $this->assertNull($victim->refresh()->email_verified_at);
    }
}
