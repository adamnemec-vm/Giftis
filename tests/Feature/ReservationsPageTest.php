<?php

namespace Tests\Feature;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_my_reservations_page(): void
    {
        $response = $this->get(route('reservations.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_sees_their_own_reservations_and_contributions(): void
    {
        $buyer = User::factory()->create();
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create(['title' => 'Vánoční seznam']);

        $reservedItem = GiftItem::factory()->for($wishlist)->create([
            'title' => 'Rezervovaná kniha',
            'status' => 'reserved',
            'reserved_by_user_id' => $buyer->id,
            'reserved_by_name' => $buyer->name,
            'reserved_at' => now(),
        ]);

        $groupItem = GiftItem::factory()->for($wishlist)->create([
            'title' => 'Skupinové kolo',
            'is_group_gift' => true,
            'price' => 5000,
        ]);
        $groupItem->contributions()->create([
            'contributor_name' => $buyer->name,
            'contributor_user_id' => $buyer->id,
            'amount' => 1000,
        ]);

        $response = $this->actingAs($buyer)->get(route('reservations.index'));

        $response->assertOk();
        $response->assertSee('Rezervovaná kniha');
        $response->assertSee('Vánoční seznam');
        $response->assertSee('Skupinové kolo');
        $response->assertSee('1 000');
    }

    public function test_user_does_not_see_other_peoples_reservations(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();

        GiftItem::factory()->for($wishlist)->create([
            'title' => 'Cizí rezervace',
            'status' => 'reserved',
            'reserved_by_user_id' => $otherBuyer->id,
            'reserved_at' => now(),
        ]);

        $response = $this->actingAs($buyer)->get(route('reservations.index'));

        $response->assertDontSee('Cizí rezervace');
    }

    public function test_user_can_cancel_a_reservation_from_the_reservations_page(): void
    {
        $buyer = User::factory()->create();
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create([
            'status' => 'reserved',
            'reserved_by_user_id' => $buyer->id,
            'reserved_at' => now(),
        ]);

        $response = $this->actingAs($buyer)->post(route('public.wishlists.unreserve', [$wishlist->share_code, $item]));

        $response->assertSessionHas('success');
        $this->assertSame('available', $item->refresh()->status);
    }
}
