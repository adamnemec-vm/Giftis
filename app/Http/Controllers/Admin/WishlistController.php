<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    public function suspend(Wishlist $wishlist)
    {
        $wishlist->forceFill(['suspended_at' => now()])->save();

        return redirect()->route('wishlists.show', $wishlist)
            ->with('success', 'Seznam byl pozastaven – veřejný odkaz na něj už nikomu nefunguje.');
    }

    public function unsuspend(Wishlist $wishlist)
    {
        $wishlist->forceFill(['suspended_at' => null])->save();

        return redirect()->route('wishlists.show', $wishlist)
            ->with('success', 'Zveřejnění seznamu bylo obnoveno.');
    }
}
