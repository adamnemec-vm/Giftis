<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistGroupController extends Controller
{
    public function update(Request $request, Wishlist $wishlist)
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validate([
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer'],
        ]);

        // Only groups the wishlist owner is actually an accepted member of may be selected.
        $allowedGroupIds = auth()->user()->groups()->pluck('groups.id');
        $groupIds = collect($validated['group_ids'] ?? [])->intersect($allowedGroupIds)->all();

        $wishlist->groups()->sync($groupIds);

        return redirect()->route('wishlists.show', $wishlist)
            ->with('success', 'Sdílení seznamu se skupinami bylo aktualizováno.');
    }
}
