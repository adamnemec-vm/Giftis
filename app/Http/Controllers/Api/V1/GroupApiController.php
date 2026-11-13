<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Models\Wishlist;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $groups = $user->groups()->with(['owner', 'acceptedMembers'])->withCount('acceptedMembers')->get();
        $invitations = $user->pendingGroupInvitations()->with('owner')->get();

        return response()->json([
            'groups' => $groups->map(fn ($g) => ApiPresenter::group($g, $user) + [
                'members_preview' => $g->acceptedMembers->take(4)->map(fn ($m) => $m->name)->values(),
            ])->values(),
            'invitations' => $invitations->map(fn ($g) => ApiPresenter::group($g, $user))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $group = $request->user()->ownedGroups()->create(['name' => $validated['name']]);
        $group->members()->attach($request->user()->id, [
            'status' => 'accepted',
            'invited_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['group' => ApiPresenter::group($group->load('owner'), $request->user())], 201);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        $this->authorize('view', $group);

        $user = $request->user();
        $group->load(['owner', 'acceptedMembers', 'pendingMembers']);

        $wishlists = Wishlist::whereHas('groups', fn ($q) => $q->where('groups.id', $group->id))
            ->with(['user', 'items'])
            ->get();

        return response()->json([
            'group' => ApiPresenter::group($group, $user),
            'members' => $group->acceptedMembers->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'is_owner' => $m->id === $group->owner_id,
            ])->values(),
            'pending_members' => $group->pendingMembers->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
            ])->values(),
            'wishlists' => $wishlists->map(fn ($w) => ApiPresenter::wishlist($w) + [
                'is_mine' => $w->user_id === $user->id,
            ])->values(),
        ]);
    }

    public function destroy(Group $group): JsonResponse
    {
        $this->authorize('delete', $group);

        $group->delete();

        return response()->json(['message' => 'Skupina byla smazána.']);
    }

    public function invite(Request $request, Group $group): JsonResponse
    {
        $this->authorize('invite', $group);

        $email = $request->validate(['email' => ['required', 'string', 'email', 'max:255']])['email'];
        $invitee = User::where('email', $email)->first();

        if (! $invitee) {
            return response()->json(['message' => 'Uživatel s tímto e-mailem u nás nemá založený účet. Pozvat ho budete moci, až se zaregistruje.'], 422);
        }
        if ($invitee->id === $request->user()->id) {
            return response()->json(['message' => 'Sami sebe do skupiny pozvat nemůžete.'], 422);
        }
        if ($group->members()->where('user_id', $invitee->id)->exists()) {
            return response()->json(['message' => 'Tento uživatel už je členem skupiny nebo má pozvánku čekající na vyřízení.'], 422);
        }

        $group->members()->attach($invitee->id, [
            'status' => 'invited',
            'invited_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Pozvánka byla odeslána uživateli '.$invitee->name.'.'], 201);
    }

    public function accept(Request $request, Group $group): JsonResponse
    {
        $this->requirePendingInvitation($request, $group);

        $group->members()->updateExistingPivot($request->user()->id, ['status' => 'accepted']);

        return response()->json(['message' => 'Vstoupili jste do skupiny.']);
    }

    public function decline(Request $request, Group $group): JsonResponse
    {
        $this->requirePendingInvitation($request, $group);

        $group->members()->detach($request->user()->id);

        return response()->json(['message' => 'Pozvánka byla odmítnuta.']);
    }

    public function leave(Request $request, Group $group): JsonResponse
    {
        if ($request->user()->id === $group->owner_id) {
            return response()->json(['message' => 'Jako zakladatel skupiny ji nemůžete opustit — můžete ji smazat.'], 422);
        }

        $group->members()->detach($request->user()->id);

        return response()->json(['message' => 'Opustili jste skupinu.']);
    }

    public function removeMember(Request $request, Group $group, User $user): JsonResponse
    {
        $this->authorize('update', $group);

        if ($user->id === $group->owner_id) {
            return response()->json(['message' => 'Zakladatele skupiny nelze odebrat.'], 422);
        }

        $group->members()->detach($user->id);

        return response()->json(['message' => 'Člen byl odebrán.']);
    }

    private function requirePendingInvitation(Request $request, Group $group): void
    {
        $pending = $group->members()
            ->where('user_id', $request->user()->id)
            ->wherePivot('status', 'invited')
            ->exists();

        abort_if(! $pending, 404);
    }
}
