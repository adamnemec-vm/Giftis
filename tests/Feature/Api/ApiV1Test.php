<?php

namespace Tests\Feature\Api;

use App\Models\GiftItem;
use App\Models\Group;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_and_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'secret-pass']);

        $this->postJson('/api/v1/auth/login', ['email' => 'a@example.com', 'password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->postJson('/api/v1/auth/login', ['email' => 'a@example.com', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_creates_an_account_and_returns_a_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Nový Uživatel',
            'email' => 'novy@example.com',
            'password' => 'Heslo12345!',
            'password_confirmation' => 'Heslo12345!',
        ])->assertCreated()->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', ['email' => 'novy@example.com']);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_user_can_create_a_wishlist_and_add_an_item_with_a_placeholder_icon(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $wishlistId = $this->postJson('/api/v1/wishlists', [
            'title' => 'Vánoce',
            'occasion' => 'christmas',
        ])->assertCreated()->json('wishlist.id');

        $this->postJson("/api/v1/wishlists/{$wishlistId}/items", [
            'title' => 'Sluchátka',
            'priority' => 'high',
            'price' => 4990,
            'icon' => 'electronics',
        ])->assertCreated()->assertJsonPath('item.icon', 'electronics');

        $this->getJson("/api/v1/wishlists/{$wishlistId}")
            ->assertOk()
            ->assertJsonPath('is_owner', true)
            ->assertJsonCount(1, 'items');
    }

    public function test_a_photo_can_be_uploaded_with_a_new_item_and_replaced_via_method_spoofing(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->post("/api/v1/wishlists/{$wishlist->id}/items", [
            'title' => 'Fotka',
            'priority' => 'medium',
            'image' => \Illuminate\Http\UploadedFile::fake()->image('a.jpg', 300, 300),
        ], ['Accept' => 'application/json'])->assertCreated();

        $response->assertJsonPath('item.icon', null);
        $this->assertNotNull($response->json('item.image_url'));
        $item = GiftItem::find($response->json('item.id'));
        Storage::disk('public')->assertExists($item->image_path);
        $oldPath = $item->image_path;

        // Úprava bez nové fotky fotku zachová.
        $this->putJson("/api/v1/items/{$item->id}", ['title' => 'Fotka 2', 'priority' => 'low'])->assertOk();
        $this->assertSame($oldPath, $item->refresh()->image_path);

        // Nová fotka přes multipart (_method=PUT) nahradí starou.
        $this->post("/api/v1/items/{$item->id}", [
            '_method' => 'PUT',
            'title' => 'Fotka 3',
            'priority' => 'low',
            'image' => \Illuminate\Http\UploadedFile::fake()->image('b.png', 300, 300),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNotSame($oldPath, $item->refresh()->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_an_oversized_or_non_image_upload_is_rejected(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();
        $wishlist = Wishlist::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $this->post("/api/v1/wishlists/{$wishlist->id}/items", [
            'title' => 'Špatný soubor',
            'priority' => 'medium',
            'image' => \Illuminate\Http\UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_validation_errors_come_back_as_json_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/wishlists', ['title' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'occasion']);
    }

    public function test_other_users_cannot_see_or_delete_a_private_wishlist_via_the_owner_endpoints(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/wishlists/{$wishlist->id}")->assertForbidden();
        $this->deleteJson("/api/v1/wishlists/{$wishlist->id}")->assertForbidden();
    }

    public function test_friend_can_reserve_and_unreserve_on_a_private_shared_wishlist(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();
        $friend = User::factory()->create();
        Sanctum::actingAs($friend);

        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$item->id}/reserve")
            ->assertOk()
            ->assertJsonPath('item.status', 'reserved')
            ->assertJsonPath('item.is_my_reservation', true);

        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$item->id}/unreserve")
            ->assertOk()
            ->assertJsonPath('item.status', 'available');
    }

    public function test_reserving_is_blocked_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create();

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$item->id}/reserve")->assertStatus(422);
        $this->assertSame('available', $item->refresh()->status);
    }

    public function test_a_reserved_item_cannot_be_reserved_again(): void
    {
        $wishlist = Wishlist::factory()->create();
        $item = GiftItem::factory()->for($wishlist)->reserved()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$item->id}/reserve")->assertStatus(409);
    }

    public function test_contributing_exposes_suggested_buyer_and_discussion_only_to_contributors(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $item = GiftItem::factory()->for($wishlist)->create(['is_group_gift' => true, 'price' => 1000]);
        $contributor = User::factory()->create(['name' => 'Přispěvatel']);
        $stranger = User::factory()->create();

        Sanctum::actingAs($contributor);
        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$item->id}/contributions", ['amount' => 300])
            ->assertCreated()
            ->assertJsonPath('item.my_contribution_total', 300)
            ->assertJsonPath('item.coordination.suggested_buyer.is_me', true);

        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$item->id}/comments", ['body' => 'Koupím to já.'])
            ->assertCreated()
            ->assertJsonPath('item.coordination.comments.0.body', 'Koupím to já.');

        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/shared/{$wishlist->share_code}")
            ->assertOk()
            ->assertJsonPath('items.0.coordination', null);
        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$item->id}/comments", ['body' => 'Čumím'])
            ->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson("/api/v1/shared/{$wishlist->share_code}")
            ->assertOk()
            ->assertJsonPath('items.0.coordination', null);
    }

    public function test_groups_invite_accept_and_share_flow(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'clen@example.com']);

        Sanctum::actingAs($owner);
        $groupId = $this->postJson('/api/v1/groups', ['name' => 'Rodina'])->assertCreated()->json('group.id');
        $this->postJson("/api/v1/groups/{$groupId}/invite", ['email' => 'clen@example.com'])->assertCreated();

        Sanctum::actingAs($member);
        $this->getJson('/api/v1/groups')->assertOk()->assertJsonCount(1, 'invitations');
        $this->postJson("/api/v1/groups/{$groupId}/accept")->assertOk();

        $wishlist = Wishlist::factory()->for($member)->create(['title' => 'Sdílený']);
        $this->putJson("/api/v1/wishlists/{$wishlist->id}/groups", ['group_ids' => [$groupId]])->assertOk();

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonCount(1, 'group_wishlists');
        $this->getJson("/api/v1/groups/{$groupId}")->assertOk()->assertJsonCount(1, 'wishlists');
    }

    public function test_my_reservations_lists_reservations_and_contributions(): void
    {
        $wishlist = Wishlist::factory()->create();
        $reserved = GiftItem::factory()->for($wishlist)->create();
        $group = GiftItem::factory()->for($wishlist)->create(['is_group_gift' => true, 'price' => 500]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$reserved->id}/reserve")->assertOk();
        $this->postJson("/api/v1/shared/{$wishlist->share_code}/items/{$group->id}/contributions", ['amount' => 100])->assertCreated();

        $this->getJson('/api/v1/reservations')
            ->assertOk()
            ->assertJsonCount(1, 'reservations')
            ->assertJsonCount(1, 'contributions');
    }
}
