<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteGroupMemberRequest;
use App\Models\Group;
use App\Models\User;

class GroupInvitationController extends Controller
{
    public function store(InviteGroupMemberRequest $request, Group $group)
    {
        $this->authorize('invite', $group);

        $email = $request->validated()['email'];

        $invitee = User::where('email', $email)->first();

        if (! $invitee) {
            return redirect()->back()->with('error', 'Uživatel s tímto e-mailem u nás nemá založený účet. Pozvat ho budete moci, až se zaregistruje.');
        }

        if ($invitee->id === auth()->id()) {
            return redirect()->back()->with('error', 'Sami sebe do skupiny pozvat nemůžete.');
        }

        if ($group->members()->where('user_id', $invitee->id)->exists()) {
            return redirect()->back()->with('error', 'Tento uživatel už je členem skupiny nebo má pozvánku čekající na vyřízení.');
        }

        $group->members()->attach($invitee->id, [
            'status' => 'invited',
            'invited_by_user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Pozvánka byla odeslána uživateli '.$invitee->name.'.');
    }

    public function accept(Group $group)
    {
        $user = auth()->user();

        $membership = $group->members()->where('user_id', $user->id)->wherePivot('status', 'invited')->exists();

        if (! $membership) {
            abort(404);
        }

        $group->members()->updateExistingPivot($user->id, ['status' => 'accepted']);

        return redirect()->route('groups.show', $group)->with('success', 'Vstoupili jste do skupiny "'.$group->name.'".');
    }

    public function decline(Group $group)
    {
        $user = auth()->user();

        $membership = $group->members()->where('user_id', $user->id)->wherePivot('status', 'invited')->exists();

        if (! $membership) {
            abort(404);
        }

        $group->members()->detach($user->id);

        return redirect()->route('groups.index')->with('success', 'Pozvánka byla odmítnuta.');
    }
}
