<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function view(User $user, Group $group): bool
    {
        return $user->id === $group->owner_id
            || $group->members()->where('user_id', $user->id)->wherePivot('status', 'accepted')->exists();
    }

    public function update(User $user, Group $group): bool
    {
        return $user->id === $group->owner_id;
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->id === $group->owner_id;
    }

    public function invite(User $user, Group $group): bool
    {
        return $user->id === $group->owner_id;
    }
}
