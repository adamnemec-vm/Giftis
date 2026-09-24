<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $myGroups = $user->groups()->with('owner')->withCount(['acceptedMembers'])->get();
        $pendingInvitations = $user->pendingGroupInvitations()->with('owner')->get();

        return view('groups.index', compact('myGroups', 'pendingInvitations'));
    }

    public function store(StoreGroupRequest $request)
    {
        $validated = $request->validated();

        $group = auth()->user()->ownedGroups()->create([
            'name' => $validated['name'],
        ]);

        $group->members()->attach(auth()->id(), [
            'status' => 'accepted',
            'invited_by_user_id' => auth()->id(),
        ]);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Skupina byla vytvořena.');
    }

    public function show(Group $group)
    {
        $this->authorize('view', $group);

        $group->load(['owner', 'acceptedMembers', 'pendingMembers']);

        $user = auth()->user();
        $isOwner = $user->id === $group->owner_id;

        $groupWishlists = \App\Models\Wishlist::whereHas('groups', fn ($q) => $q->where('groups.id', $group->id))
            ->with(['user', 'items'])
            ->get();

        return view('groups.show', compact('group', 'isOwner', 'groupWishlists'));
    }

    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);

        $group->delete();

        return redirect()->route('groups.index')
            ->with('success', 'Skupina byla smazána.');
    }

    public function leave(Group $group)
    {
        $user = auth()->user();

        if ($user->id === $group->owner_id) {
            return redirect()->back()->with('error', 'Jako zakladatel skupiny ji nemůžete opustit — pokud ji už nechcete, můžete ji smazat.');
        }

        $group->members()->detach($user->id);

        return redirect()->route('groups.index')->with('success', 'Opustili jste skupinu.');
    }

    public function removeMember(Group $group, User $user)
    {
        $this->authorize('update', $group);

        if ($user->id === $group->owner_id) {
            return redirect()->back()->with('error', 'Zakladatele skupiny nelze odebrat.');
        }

        $group->members()->detach($user->id);

        return redirect()->back()->with('success', 'Člen byl odebrán ze skupiny.');
    }
}
