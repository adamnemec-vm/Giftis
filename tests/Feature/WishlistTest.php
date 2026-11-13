<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_wishlist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/wishlists', [
            'title' => 'Vánoční přání',
            'occasion' => 'christmas',
        ]);

        $wishlist = Wishlist::first();

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertSame($user->id, $wishlist->user_id);
        $this->assertNotEmpty($wishlist->share_code);
    }

    public function test_creating_a_wishlist_with_invalid_data_surfaces_errors_under_the_create_wishlist_bag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('dashboard'))->post('/wishlists', [
            'title' => '',
            'occasion' => 'christmas',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasErrors('title', null, 'createWishlist');
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id]);
    }

    public function test_the_wishlist_schema_has_no_public_flag_anymore(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('wishlists', 'is_public'));
    }

    public function test_owner_can_view_their_own_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('wishlists.show', $wishlist));

        $response->assertOk();
    }

    public function test_other_users_cannot_view_someone_elses_wishlist(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->get(route('wishlists.show', $wishlist));

        $response->assertForbidden();
    }

    public function test_owner_can_update_their_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('wishlists.update', $wishlist), [
            'title' => 'Nový název',
            'occasion' => 'wedding',
        ]);

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertSame('Nový název', $wishlist->refresh()->title);
    }

    public function test_other_users_cannot_update_someone_elses_wishlist(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create(['title' => 'Original']);

        $response = $this->actingAs($intruder)->put(route('wishlists.update', $wishlist), [
            'title' => 'Hacked',
            'occasion' => 'wedding',
        ]);

        $response->assertForbidden();
        $this->assertSame('Original', $wishlist->refresh()->title);
    }

    public function test_other_users_cannot_delete_someone_elses_wishlist(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete(route('wishlists.destroy', $wishlist));

        $response->assertForbidden();
        $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id]);
    }

    public function test_owner_can_delete_their_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('wishlists.destroy', $wishlist));

        $response->assertRedirect(route('wishlists.index'));
        $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
    }

    public function test_owner_can_view_the_qr_code_for_their_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('wishlists.qr-code', $wishlist));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_other_users_cannot_view_the_qr_code_for_someone_elses_wishlist(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->get(route('wishlists.qr-code', $wishlist));

        $response->assertForbidden();
    }
}
