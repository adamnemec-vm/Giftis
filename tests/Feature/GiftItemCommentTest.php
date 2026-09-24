<?php

namespace Tests\Feature;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftItemCommentTest extends TestCase
{
    use RefreshDatabase;

    private function groupGift(User $owner, float $price = 1000): GiftItem
    {
        $wishlist = Wishlist::factory()->for($owner)->create();

        return GiftItem::factory()->for($wishlist)->create([
            'is_group_gift' => true,
            'price' => $price,
        ]);
    }

    public function test_a_contributor_can_post_a_comment(): void
    {
        $owner = User::factory()->create();
        $item = $this->groupGift($owner);
        $contributor = User::factory()->create(['name' => 'Teta Alena']);
        $item->contributions()->create(['contributor_user_id' => $contributor->id, 'contributor_name' => 'Teta Alena', 'amount' => 300]);

        $response = $this->actingAs($contributor)->post(
            route('gift-item-comments.store', [$item->wishlist->share_code, $item]),
            ['body' => 'Já to koupím, pošlete mi to na účet.']
        );

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('gift_item_comments', [
            'gift_item_id' => $item->id,
            'author_user_id' => $contributor->id,
            'author_name' => 'Teta Alena',
            'body' => 'Já to koupím, pošlete mi to na účet.',
        ]);
    }

    public function test_a_guest_contributor_can_post_a_comment_using_their_session_token(): void
    {
        $owner = User::factory()->create();
        $item = $this->groupGift($owner);
        $item->contributions()->create([
            'contributor_name' => 'Kamarád Petr',
            'amount' => 200,
            'contribution_token' => 'guest-token-xyz',
        ]);

        $this->withSession(['giftis_guest_token' => 'guest-token-xyz']);
        $response = $this->post(
            route('gift-item-comments.store', [$item->wishlist->share_code, $item]),
            ['body' => 'Kdo to nakonec koupí?']
        );

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('gift_item_comments', [
            'gift_item_id' => $item->id,
            'author_name' => 'Kamarád Petr',
            'commenter_token' => 'guest-token-xyz',
        ]);
    }

    public function test_a_non_contributor_cannot_post_a_comment(): void
    {
        $owner = User::factory()->create();
        $item = $this->groupGift($owner);
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->post(
            route('gift-item-comments.store', [$item->wishlist->share_code, $item]),
            ['body' => 'Ahoj, chci si přečíst vaši diskuzi.']
        );

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('gift_item_comments', 0);
    }

    public function test_the_wishlist_owner_cannot_post_a_comment_even_if_somehow_flagged_as_a_contributor(): void
    {
        $owner = User::factory()->create();
        $item = $this->groupGift($owner);
        $item->contributions()->create(['contributor_user_id' => $owner->id, 'contributor_name' => $owner->name, 'amount' => 100]);

        $response = $this->actingAs($owner)->post(
            route('gift-item-comments.store', [$item->wishlist->share_code, $item]),
            ['body' => 'Pokus o nahlédnutí do diskuze.']
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('gift_item_comments', 0);
    }

    public function test_comments_are_not_allowed_on_a_non_group_gift(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create(['is_group_gift' => false]);
        $contributor = User::factory()->create();

        $response = $this->actingAs($contributor)->post(
            route('gift-item-comments.store', [$wishlist->share_code, $item]),
            ['body' => 'test']
        );

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('gift_item_comments', 0);
    }

    public function test_owner_does_not_see_any_contributor_names_or_the_discussion_on_the_public_page(): void
    {
        $owner = User::factory()->create();
        $item = $this->groupGift($owner);
        $item->contributions()->create(['contributor_name' => 'Skrytý dárce', 'amount' => 100, 'contribution_token' => 'tok']);
        $item->comments()->create(['author_name' => 'Skrytý dárce', 'commenter_token' => 'tok', 'body' => 'Tajná zpráva mezi přispěvateli']);

        $response = $this->actingAs($owner)->get(route('public.wishlists.show', $item->wishlist->share_code));

        $response->assertOk();
        $response->assertDontSee('Skrytý dárce');
        $response->assertDontSee('Tajná zpráva mezi přispěvateli');
        $response->assertDontSee('Navrhovaný kupující');
    }

    public function test_the_first_contributor_is_shown_as_the_suggested_buyer_to_a_fellow_contributor(): void
    {
        $owner = User::factory()->create();
        $item = $this->groupGift($owner);
        $item->contributions()->create(['contributor_name' => 'První Dárce', 'amount' => 100, 'contribution_token' => 'first-token', 'created_at' => now()->subMinutes(10)]);
        $item->contributions()->create(['contributor_name' => 'Druhý Dárce', 'amount' => 100, 'contribution_token' => 'second-token', 'created_at' => now()]);

        $this->withSession(['giftis_guest_token' => 'second-token']);
        $response = $this->get(route('public.wishlists.show', $item->wishlist->share_code));

        $response->assertOk();
        $response->assertSee('Navrhovaný kupující: První Dárce');
    }
}
