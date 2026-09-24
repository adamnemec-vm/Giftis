<?php

namespace Tests\Feature;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_an_item_to_their_wishlist(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('gift-items.store', $wishlist), [
            'title' => 'Kávovar',
            'priority' => 'high',
        ]);

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertDatabaseHas('gift_items', [
            'wishlist_id' => $wishlist->id,
            'title' => 'Kávovar',
            'status' => 'available',
        ]);
    }

    public function test_adding_an_item_with_an_invalid_url_surfaces_errors_under_the_add_item_bag(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();

        $response = $this->actingAs($user)->from(route('wishlists.show', $wishlist))->post(route('gift-items.store', $wishlist), [
            'title' => 'Kávovar',
            'priority' => 'high',
            'url' => 'alza.cz', // chybí schéma (https://) — běžná uživatelská chyba
        ]);

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $response->assertSessionHasErrors('url', null, 'addItem');
        $this->assertDatabaseMissing('gift_items', ['wishlist_id' => $wishlist->id]);
    }

    public function test_other_users_cannot_add_items_to_someone_elses_wishlist(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->post(route('gift-items.store', $wishlist), [
            'title' => 'Podvržený dárek',
            'priority' => 'low',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('gift_items', ['title' => 'Podvržený dárek']);
    }

    public function test_owner_can_update_their_item(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();
        $item = GiftItem::factory()->for($wishlist)->create(['title' => 'Original']);

        $response = $this->actingAs($user)->put(route('gift-items.update', $item), [
            'title' => 'Upravený název',
            'priority' => 'medium',
        ]);

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertSame('Upravený název', $item->refresh()->title);
    }

    public function test_other_users_cannot_update_someone_elses_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create(['title' => 'Original']);

        $response = $this->actingAs($intruder)->put(route('gift-items.update', $item), [
            'title' => 'Hacked',
            'priority' => 'medium',
        ]);

        $response->assertForbidden();
        $this->assertSame('Original', $item->refresh()->title);
    }

    public function test_other_users_cannot_delete_someone_elses_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->actingAs($intruder)->delete(route('gift-items.destroy', $item));

        $response->assertForbidden();
        $this->assertDatabaseHas('gift_items', ['id' => $item->id]);
    }

    public function test_owner_can_delete_their_item(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->actingAs($user)->delete(route('gift-items.destroy', $item));

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertDatabaseMissing('gift_items', ['id' => $item->id]);
    }

    public function test_owner_can_manually_mark_their_item_as_reserved(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();
        $item = GiftItem::factory()->for($wishlist)->create(['status' => 'available']);

        $response = $this->actingAs($user)->post(route('gift-items.mark-reserved', $item));

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $item->refresh();
        $this->assertSame('reserved', $item->status);
        $this->assertNotNull($item->reserved_at);
    }

    public function test_other_users_cannot_mark_someone_elses_item_as_reserved(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create(['status' => 'available']);

        $response = $this->actingAs($intruder)->post(route('gift-items.mark-reserved', $item));

        $response->assertForbidden();
        $this->assertSame('available', $item->refresh()->status);
    }

    public function test_owner_can_manually_clear_a_reservation(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'status' => 'reserved',
            'reserved_by_name' => 'Teta Alena',
            'reserved_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('gift-items.clear-reservation', $item));

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $item->refresh();
        $this->assertSame('available', $item->status);
        $this->assertNull($item->reserved_by_name);
        $this->assertNull($item->reserved_at);
    }

    public function test_clearing_reservation_on_a_group_gift_also_deletes_its_contributions(): void
    {
        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 500,
            'status' => 'reserved',
        ]);
        $item->contributions()->create(['contributor_name' => 'Host', 'amount' => 500]);

        $this->actingAs($user)->post(route('gift-items.clear-reservation', $item));

        $this->assertSame(0, $item->contributions()->count());
        $this->assertSame('available', $item->refresh()->status);
    }

    public function test_other_users_cannot_clear_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create(['status' => 'reserved']);

        $response = $this->actingAs($intruder)->post(route('gift-items.clear-reservation', $item));

        $response->assertForbidden();
        $this->assertSame('reserved', $item->refresh()->status);
    }
}
