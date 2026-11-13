<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Http\Requests\UpdateWishlistRequest;
use App\Models\Wishlist;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $mine = $user->wishlists()->with('items')->latest()->get();

        $myGroupIds = $user->groups()->pluck('groups.id');
        $groupWishlists = Wishlist::with(['user', 'items', 'groups' => fn ($q) => $q->whereIn('groups.id', $myGroupIds)])
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $myGroupIds))
            ->where('user_id', '!=', $user->id)
            ->whereNull('suspended_at')
            ->latest()
            ->get();

        return response()->json([
            'my_wishlists' => $mine->map(fn ($w) => ApiPresenter::wishlist($w))->values(),
            'group_wishlists' => $groupWishlists->map(fn ($w) => ApiPresenter::wishlist($w))->values(),
            'pending_invitations_count' => $user->pendingGroupInvitations()->count(),
        ]);
    }

    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $shareCode = Str::lower(Str::random(10));
        while (Wishlist::where('share_code', $shareCode)->exists()) {
            $shareCode = Str::lower(Str::random(10));
        }

        $wishlist = $request->user()->wishlists()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'occasion' => $validated['occasion'],
            'event_date' => $validated['event_date'] ?? null,
            'share_code' => $shareCode,
        ]);

        return response()->json(['wishlist' => ApiPresenter::wishlist($wishlist->load('items'))], 201);
    }

    public function show(Request $request, Wishlist $wishlist): JsonResponse
    {
        $this->authorize('view', $wishlist);

        $wishlist->load(['items.contributions', 'groups']);

        $isOwner = $request->user()->id === $wishlist->user_id;
        $sharedGroupIds = $wishlist->groups->pluck('id')->all();

        $groups = $isOwner
            ? $request->user()->groups()->get()->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'shared' => in_array($g->id, $sharedGroupIds, true),
            ])->values()
            : collect();

        return response()->json([
            'wishlist' => ApiPresenter::wishlist($wishlist),
            'items' => $wishlist->items->map(fn ($i) => ApiPresenter::ownerItem($i))->values(),
            'groups' => $groups,
            'is_owner' => $isOwner,
        ]);
    }

    public function update(UpdateWishlistRequest $request, Wishlist $wishlist): JsonResponse
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validated();

        $wishlist->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'occasion' => $validated['occasion'],
            'event_date' => $validated['event_date'] ?? null,
        ]);

        return response()->json(['wishlist' => ApiPresenter::wishlist($wishlist->load('items'))]);
    }

    public function destroy(Wishlist $wishlist): JsonResponse
    {
        $this->authorize('delete', $wishlist);

        $wishlist->delete();

        return response()->json(['message' => 'Seznam byl smazán.']);
    }

    public function syncGroups(Request $request, Wishlist $wishlist): JsonResponse
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validate([
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer'],
        ]);

        $allowed = $request->user()->groups()->pluck('groups.id');
        $ids = collect($validated['group_ids'] ?? [])->intersect($allowed)->all();

        $wishlist->groups()->sync($ids);

        return response()->json(['message' => 'Sdílení bylo aktualizováno.', 'group_ids' => array_values($ids)]);
    }
}
