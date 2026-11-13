<?php

namespace App\Support;

use App\Models\GiftItem;
use App\Models\Group;
use App\Models\User;
use App\Models\Wishlist;

class ApiPresenter
{
    public const PLACEHOLDER_ICONS = ['gift', 'book', 'electronics', 'clothing', 'toy', 'jewelry', 'experience', 'home', 'sports', 'beauty'];

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
        ];
    }

    public static function wishlist(Wishlist $wishlist): array
    {
        $items = $wishlist->relationLoaded('items') ? $wishlist->items : collect();

        return [
            'id' => $wishlist->id,
            'title' => $wishlist->title,
            'description' => $wishlist->description,
            'occasion' => $wishlist->occasion,
            'occasion_label' => $wishlist->occasion_label,
            'event_date' => $wishlist->event_date?->format('Y-m-d'),
            'share_code' => $wishlist->share_code,
            'share_url' => route('public.wishlists.show', $wishlist->share_code),
            'items_count' => $items->count(),
            'reserved_count' => $items->where('status', 'reserved')->count(),
            'progress_percentage' => $wishlist->relationLoaded('items') ? $wishlist->progress_percentage : 0,
            'author' => $wishlist->relationLoaded('user') && $wishlist->user
                ? ['id' => $wishlist->user->id, 'name' => $wishlist->user->name]
                : null,
            'groups' => $wishlist->relationLoaded('groups')
                ? $wishlist->groups->map(fn (Group $g) => ['id' => $g->id, 'name' => $g->name])->values()->all()
                : [],
        ];
    }

    /** Položka tak, jak ji vidí majitel seznamu (nikdy neprozradí, kdo rezervoval). */
    public static function ownerItem(GiftItem $item): array
    {
        return self::baseItem($item);
    }

    /** Položka z pohledu přihlášeného přítele na sdíleném seznamu. */
    public static function sharedItem(GiftItem $item, User $viewer, bool $viewerIsOwner): array
    {
        $data = self::baseItem($item);

        $myContributions = $item->contributions
            ->where('contributor_user_id', $viewer->id)
            ->values();

        $data['is_my_reservation'] = $item->reserved_by_user_id === $viewer->id;
        $data['my_contributions'] = $myContributions
            ->map(fn ($c) => ['id' => $c->id, 'amount' => (float) $c->amount])
            ->all();
        $data['my_contribution_total'] = (float) $myContributions->sum('amount');

        $data['coordination'] = null;

        if (! $viewerIsOwner && $item->is_group_gift && $myContributions->isNotEmpty()) {
            $buyer = $item->suggested_buyer;

            $data['coordination'] = [
                'suggested_buyer' => $buyer ? [
                    'name' => $buyer->contributor_user_id ? ($buyer->contributor?->name ?? $buyer->contributor_name) : $buyer->contributor_name,
                    'is_me' => $buyer->contributor_user_id === $viewer->id,
                ] : null,
                'comments' => $item->comments->map(fn ($c) => [
                    'id' => $c->id,
                    'author_name' => $c->author_name,
                    'body' => $c->body,
                    'created_at' => $c->created_at?->toIso8601String(),
                    'is_mine' => $c->author_user_id === $viewer->id,
                ])->values()->all(),
            ];
        }

        return $data;
    }

    private static function baseItem(GiftItem $item): array
    {
        $icon = null;
        $imageUrl = null;

        if ($item->image_path) {
            if (str_starts_with($item->image_path, '/images/placeholders/') || str_contains($item->image_path, '/images/placeholders/')) {
                $name = pathinfo($item->image_path, PATHINFO_FILENAME);
                $icon = in_array($name, self::PLACEHOLDER_ICONS, true) ? $name : 'gift';
            } else {
                $imageUrl = $item->resolved_image_url;
            }
        }

        $contributions = $item->relationLoaded('contributions') ? $item->contributions : collect();

        return [
            'id' => $item->id,
            'wishlist_id' => $item->wishlist_id,
            'title' => $item->title,
            'url' => $item->url,
            'price' => $item->price !== null ? (float) $item->price : null,
            'currency' => $item->currency ?? 'Kč',
            'formatted_price' => $item->formatted_price,
            'description' => $item->description,
            'priority' => $item->priority,
            'status' => $item->status,
            'is_group_gift' => (bool) $item->is_group_gift,
            'icon' => $icon ?? ($imageUrl ? null : 'gift'),
            'image_url' => $imageUrl,
            'contributed_total' => $item->is_group_gift ? (float) $contributions->sum('amount') : 0,
            'contribution_percentage' => $item->is_group_gift && $item->relationLoaded('contributions') ? $item->contribution_percentage : 0,
            'remaining_amount' => $item->is_group_gift && $item->relationLoaded('contributions') ? $item->remaining_amount : 0,
        ];
    }

    public static function group(Group $group, ?User $viewer = null): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'owner_id' => $group->owner_id,
            'owner_name' => $group->relationLoaded('owner') ? $group->owner?->name : null,
            'is_owner' => $viewer ? $viewer->id === $group->owner_id : false,
            'members_count' => $group->accepted_members_count
                ?? ($group->relationLoaded('acceptedMembers') ? $group->acceptedMembers->count() : null),
        ];
    }
}
