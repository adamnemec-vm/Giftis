<?php

namespace Tests\Feature;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;
use App\Notifications\GiftItemReserved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GiftContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_contribute_to_a_group_gift(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 1000,
        ]);

        $response = $this->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 300, 'contributor_name' => 'Teta Alena']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('available', $item->status);
        $this->assertSame(300.0, $item->contributed_total);
        $this->assertSame(30, $item->contribution_percentage);
    }

    public function test_guest_contribution_includes_a_manage_link_to_recover_after_losing_cookies(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 1000,
        ]);

        $response = $this->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 300, 'contributor_name' => 'Teta Alena']
        );

        $token = $item->contributions()->first()->contribution_token;
        $response->assertSessionHas('manage_link', route('public.wishlists.restore-session', [$wishlist->share_code, $token]));
    }

    public function test_contributing_the_full_amount_marks_item_reserved_and_notifies_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 500,
        ]);

        $this->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 500, 'contributor_name' => 'Strýček Petr']
        );

        $item->refresh();
        $this->assertSame('reserved', $item->status);
        $this->assertNotNull($item->reserved_at);
        Notification::assertSentTo($owner, GiftItemReserved::class);
    }

    public function test_contribution_exceeding_remaining_amount_is_rejected(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 500,
        ]);

        $response = $this->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 600, 'contributor_name' => 'Host']
        );

        $response->assertSessionHas('error');
        $this->assertSame(0.0, $item->refresh()->contributed_total);
    }

    public function test_cannot_contribute_to_a_non_group_gift_item(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => false,
            'price' => 500,
        ]);

        $response = $this->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 100, 'contributor_name' => 'Host']
        );

        $response->assertSessionHas('error');
        $this->assertSame(0.0, $item->refresh()->contributed_total);
    }

    public function test_owner_cannot_contribute_to_their_own_group_gift(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 500,
        ]);

        $response = $this->actingAs($owner)->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 100]
        );

        $response->assertSessionHas('error');
        $this->assertSame(0.0, $item->refresh()->contributed_total);
    }

    public function test_guest_can_cancel_their_own_contribution(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 1000,
        ]);

        $this->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 300, 'contributor_name' => 'Host']
        );

        $contribution = $item->contributions()->first();

        $response = $this->delete(route('gift-contributions.destroy', [$wishlist->share_code, $item, $contribution]));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('gift_contributions', ['id' => $contribution->id]);
    }

    public function test_a_different_guest_cannot_cancel_someone_elses_contribution(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 1000,
        ]);
        $contribution = $item->contributions()->create([
            'contributor_name' => 'Host',
            'amount' => 300,
            'contribution_token' => 'someone-elses-token',
        ]);

        $response = $this->delete(route('gift-contributions.destroy', [$wishlist->share_code, $item, $contribution]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('gift_contributions', ['id' => $contribution->id]);
    }

    public function test_cannot_cancel_a_contribution_once_the_gift_is_fully_funded(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'status' => 'reserved',
            'price' => 300,
        ]);
        $contribution = $item->contributions()->create([
            'contributor_name' => 'Host',
            'amount' => 300,
            'contribution_token' => 'guest-token-abc',
        ]);

        $this->withSession(['giftis_guest_token' => 'guest-token-abc']);
        $response = $this->delete(route('gift-contributions.destroy', [$wishlist->share_code, $item, $contribution]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('gift_contributions', ['id' => $contribution->id]);
    }

    public function test_cannot_contribute_to_a_group_gift_on_a_public_wishlist(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->public()->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => 1000,
        ]);

        $response = $this->post(
            route('gift-contributions.store', [$wishlist->share_code, $item]),
            ['amount' => 300, 'contributor_name' => 'Teta Alena']
        );

        $response->assertSessionHas('error');
        $this->assertSame(0.0, $item->refresh()->contributed_total);
    }
}
