<?php

namespace Tests\Feature\Admin;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_suspend_a_wishlist(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->actingAs($admin)->post(route('admin.wishlists.suspend', $wishlist));

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertTrue($wishlist->refresh()->isSuspended());
    }

    public function test_regular_user_cannot_suspend_a_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('admin.wishlists.suspend', $wishlist));

        $response->assertForbidden();
        $this->assertFalse($wishlist->refresh()->isSuspended());
    }

    public function test_suspended_wishlist_is_not_publicly_accessible(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create(['suspended_at' => now()]);

        $response = $this->get(route('public.wishlists.show', $wishlist->share_code));

        $response->assertNotFound();
    }

    public function test_cannot_reserve_an_item_on_a_suspended_wishlist(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create(['suspended_at' => now()]);
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->post(route('public.wishlists.reserve', [$wishlist->share_code, $item]), [
            'buyer_name' => 'Host',
        ]);

        $response->assertNotFound();
        $this->assertSame('available', $item->refresh()->status);
    }

    public function test_suspended_wishlist_is_excluded_from_the_group_feed(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        $group = \App\Models\Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach([$owner->id => ['status' => 'accepted'], $viewer->id => ['status' => 'accepted']]);

        $suspended = Wishlist::factory()->for($owner)->create(['title' => 'Suspended List', 'suspended_at' => now()]);
        $visible = Wishlist::factory()->for($owner)->create(['title' => 'Visible List']);
        $suspended->groups()->attach($group->id);
        $visible->groups()->attach($group->id);

        $response = $this->actingAs($viewer)->get(route('dashboard'));

        $response->assertDontSee('Suspended List');
        $response->assertSee('Visible List');
    }

    public function test_admin_can_unsuspend_a_wishlist(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create(['suspended_at' => now()]);

        $response = $this->actingAs($admin)->post(route('admin.wishlists.unsuspend', $wishlist));

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertFalse($wishlist->refresh()->isSuspended());
    }

    public function test_admin_can_delete_someone_elses_wishlist(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->actingAs($admin)->delete(route('wishlists.destroy', $wishlist));

        $response->assertRedirect(route('admin.users.show', $owner));
        $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
    }
}
