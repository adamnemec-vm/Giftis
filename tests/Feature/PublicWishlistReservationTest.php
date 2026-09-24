<?php

namespace Tests\Feature;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;
use App\Notifications\GiftItemReserved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicWishlistReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_shared_wishlist(): void
    {
        $wishlist = Wishlist::factory()->create();
        GiftItem::factory()->for($wishlist)->create();

        $response = $this->get(route('public.wishlists.show', $wishlist->share_code));

        $response->assertOk();
    }

    public function test_guest_can_reserve_an_available_item(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->post(
            route('public.wishlists.reserve', [$wishlist->share_code, $item]),
            ['buyer_name' => 'Anonymní kamarád']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('reserved', $item->status);
        $this->assertSame('Anonymní kamarád', $item->reserved_by_name);
        $this->assertNotNull($item->reservation_token);

        Notification::assertSentTo($owner, GiftItemReserved::class);
    }

    public function test_reserving_an_already_reserved_item_fails_and_does_not_overwrite_it(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->reserved()->create([
            'reserved_by_name' => 'První host',
        ]);

        $response = $this->post(
            route('public.wishlists.reserve', [$wishlist->share_code, $item]),
            ['buyer_name' => 'Druhý host']
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('První host', $item->refresh()->reserved_by_name);
    }

    public function test_owner_cannot_reserve_a_gift_in_their_own_wishlist(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->actingAs($owner)->post(
            route('public.wishlists.reserve', [$wishlist->share_code, $item])
        );

        $response->assertSessionHas('error');
        $this->assertSame('available', $item->refresh()->status);
    }

    public function test_authenticated_user_can_reserve_without_supplying_a_name(): void
    {
        $owner = User::factory()->create();
        $buyer = User::factory()->create(['name' => 'Jana Nováková']);
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->actingAs($buyer)->post(
            route('public.wishlists.reserve', [$wishlist->share_code, $item])
        );

        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('reserved', $item->status);
        $this->assertSame($buyer->id, $item->reserved_by_user_id);
        $this->assertSame('Jana Nováková', $item->reserved_by_name);
    }

    public function test_guest_can_cancel_their_own_reservation(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $this->post(
            route('public.wishlists.reserve', [$wishlist->share_code, $item]),
            ['buyer_name' => 'Host']
        );

        $response = $this->post(route('public.wishlists.unreserve', [$wishlist->share_code, $item]));

        $response->assertSessionHas('success');
        $this->assertSame('available', $item->refresh()->status);
    }

    public function test_a_different_guest_cannot_cancel_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->reserved()->create([
            'reservation_token' => 'some-other-guest-token',
        ]);

        // A fresh, unauthenticated request has no matching guest token in its session.
        $response = $this->post(route('public.wishlists.unreserve', [$wishlist->share_code, $item]));

        $response->assertSessionHas('error');
        $this->assertSame('reserved', $item->refresh()->status);
    }

    public function test_guest_reservation_includes_a_manage_link_to_recover_after_losing_cookies(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->post(
            route('public.wishlists.reserve', [$wishlist->share_code, $item]),
            ['buyer_name' => 'Host']
        );

        $token = $item->refresh()->reservation_token;
        $response->assertSessionHas('manage_link', route('public.wishlists.restore-session', [$wishlist->share_code, $token]));
    }

    public function test_authenticated_reservation_does_not_include_a_manage_link(): void
    {
        $owner = User::factory()->create();
        $buyer = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        $response = $this->actingAs($buyer)->post(
            route('public.wishlists.reserve', [$wishlist->share_code, $item])
        );

        $response->assertSessionMissing('manage_link');
    }

    public function test_guest_can_restore_their_session_via_the_manage_link_after_losing_cookies(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        // Simulates a reservation made in a now-gone browser session.
        $item = GiftItem::factory()->for($wishlist)->reserved()->create([
            'reservation_token' => 'a-token-from-a-lost-session',
        ]);

        // This fresh test request has no cookies/session tied to that reservation.
        $restoreResponse = $this->get(route('public.wishlists.restore-session', [$wishlist->share_code, 'a-token-from-a-lost-session']));

        $restoreResponse->assertRedirect(route('public.wishlists.show', $wishlist->share_code));
        $restoreResponse->assertSessionHas('giftis_guest_token', 'a-token-from-a-lost-session');

        $unreserveResponse = $this->post(route('public.wishlists.unreserve', [$wishlist->share_code, $item]));
        $unreserveResponse->assertSessionHas('success');
        $this->assertSame('available', $item->refresh()->status);
    }

    public function test_restore_session_rejects_an_unknown_token(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        $response = $this->get(route('public.wishlists.restore-session', [$wishlist->share_code, 'made-up-token']));

        $response->assertNotFound();
    }
}
