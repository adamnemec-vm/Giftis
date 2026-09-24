<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_group_and_becomes_its_accepted_member(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Naše rodina',
        ]);

        $group = Group::first();

        $response->assertRedirect(route('groups.show', $group));
        $this->assertSame($user->id, $group->owner_id);
        $this->assertTrue($group->acceptedMembers->contains($user));
    }

    public function test_owner_can_invite_an_existing_user_by_email(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'teta@example.com']);
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);

        $response = $this->actingAs($owner)->post(route('groups.invite', $group), [
            'email' => 'teta@example.com',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $invitee->id,
            'status' => 'invited',
        ]);
    }

    public function test_inviting_an_email_without_an_account_fails_gracefully(): void
    {
        $owner = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);

        $response = $this->actingAs($owner)->post(route('groups.invite', $group), [
            'email' => 'neexistuje@example.com',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('group_members', 1);
    }

    public function test_non_owner_cannot_invite_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'x@example.com']);
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach([$owner->id => ['status' => 'accepted'], $member->id => ['status' => 'accepted']]);

        $response = $this->actingAs($member)->post(route('groups.invite', $group), [
            'email' => 'x@example.com',
        ]);

        $response->assertForbidden();
    }

    public function test_invited_user_can_accept_an_invitation(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);
        $group->members()->attach($invitee->id, ['status' => 'invited', 'invited_by_user_id' => $owner->id]);

        $response = $this->actingAs($invitee)->post(route('groups.invitations.accept', $group));

        $response->assertRedirect(route('groups.show', $group));
        $this->assertSame('accepted', $group->members()->where('user_id', $invitee->id)->first()->pivot->status);
    }

    public function test_invited_user_can_decline_an_invitation(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);
        $group->members()->attach($invitee->id, ['status' => 'invited', 'invited_by_user_id' => $owner->id]);

        $response = $this->actingAs($invitee)->post(route('groups.invitations.decline', $group));

        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id, 'user_id' => $invitee->id]);
    }

    public function test_non_member_cannot_view_a_group(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);

        $response = $this->actingAs($stranger)->get(route('groups.show', $group));

        $response->assertForbidden();
    }

    public function test_pending_invitee_cannot_view_the_group_yet(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);
        $group->members()->attach($invitee->id, ['status' => 'invited']);

        $response = $this->actingAs($invitee)->get(route('groups.show', $group));

        $response->assertForbidden();
    }

    public function test_member_can_leave_a_group(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach([$owner->id => ['status' => 'accepted'], $member->id => ['status' => 'accepted']]);

        $response = $this->actingAs($member)->post(route('groups.leave', $group));

        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id, 'user_id' => $member->id]);
    }

    public function test_owner_cannot_leave_their_own_group(): void
    {
        $owner = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);

        $response = $this->actingAs($owner)->post(route('groups.leave', $group));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('group_members', ['group_id' => $group->id, 'user_id' => $owner->id]);
    }

    public function test_owner_can_remove_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach([$owner->id => ['status' => 'accepted'], $member->id => ['status' => 'accepted']]);

        $response = $this->actingAs($owner)->delete(route('groups.members.remove', [$group, $member]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('group_members', ['group_id' => $group->id, 'user_id' => $member->id]);
    }

    public function test_owner_can_delete_the_group(): void
    {
        $owner = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);

        $response = $this->actingAs($owner)->delete(route('groups.destroy', $group));

        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    public function test_owner_can_share_a_wishlist_with_their_groups(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach($owner->id, ['status' => 'accepted']);

        $response = $this->actingAs($owner)->put(route('wishlists.groups.update', $wishlist), [
            'group_ids' => [$group->id],
        ]);

        $response->assertRedirect(route('wishlists.show', $wishlist));
        $this->assertTrue($wishlist->groups()->where('groups.id', $group->id)->exists());
    }

    public function test_wishlist_can_only_be_shared_with_groups_the_owner_belongs_to(): void
    {
        $owner = User::factory()->create();
        $wishlist = Wishlist::factory()->for($owner)->create();
        $otherOwner = User::factory()->create();
        $foreignGroup = Group::factory()->for($otherOwner, 'owner')->create();
        $foreignGroup->members()->attach($otherOwner->id, ['status' => 'accepted']);

        $this->actingAs($owner)->put(route('wishlists.groups.update', $wishlist), [
            'group_ids' => [$foreignGroup->id],
        ]);

        $this->assertFalse($wishlist->groups()->where('groups.id', $foreignGroup->id)->exists());
    }

    public function test_group_members_see_each_others_shared_wishlists_but_not_unshared_ones(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->for($owner, 'owner')->create();
        $group->members()->attach([$owner->id => ['status' => 'accepted'], $member->id => ['status' => 'accepted']]);

        $sharedWishlist = Wishlist::factory()->for($member)->create(['title' => 'Sdílený seznam']);
        $sharedWishlist->groups()->attach($group->id);

        $unsharedWishlist = Wishlist::factory()->for($member)->create(['title' => 'Neviditelný seznam']);

        $response = $this->actingAs($owner)->get(route('groups.show', $group));

        $response->assertSee('Sdílený seznam');
        $response->assertDontSee('Neviditelný seznam');
    }
}
