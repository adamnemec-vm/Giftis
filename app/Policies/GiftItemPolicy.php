<?php

namespace App\Policies;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;

class GiftItemPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->is_admin ? true : null;
    }

    public function create(User $user, Wishlist $wishlist): bool
    {
        return $user->id === $wishlist->user_id;
    }

    public function update(User $user, GiftItem $giftItem): bool
    {
        return $user->id === $giftItem->wishlist->user_id;
    }

    public function delete(User $user, GiftItem $giftItem): bool
    {
        return $user->id === $giftItem->wishlist->user_id;
    }
}
