<?php

namespace App\Http\Controllers;

use App\Models\GiftItem;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class GiftItemCommentController extends Controller
{
    public function store(Request $request, string $share_code, GiftItem $item)
    {
        $wishlist = Wishlist::where('share_code', $share_code)->firstOrFail();

        if ($item->wishlist_id !== $wishlist->id || $wishlist->isSuspended()) {
            abort(404);
        }

        if (! $item->is_group_gift) {
            return redirect()->back()->with('error', 'Diskuze je dostupná jen u skupinových dárků.');
        }

        if (auth()->check() && auth()->id() === $wishlist->user_id) {
            abort(403);
        }

        $userId = auth()->check() ? auth()->id() : null;
        $guestToken = session()->get('giftis_guest_token');

        if (! $item->isContributor($userId, $guestToken)) {
            return redirect()->back()->with('error', 'Do diskuze mohou psát jen lidé, kteří na tento dárek přispěli.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $authorName = auth()->check()
            ? auth()->user()->name
            : $item->contributions()->where('contribution_token', $guestToken)->latest()->value('contributor_name') ?? 'Anonymní přispěvatel';

        $item->comments()->create([
            'author_user_id' => $userId,
            'author_name' => $authorName,
            'commenter_token' => $userId ? null : $guestToken,
            'body' => $validated['body'],
        ]);

        return redirect()->back()->with('success', 'Zpráva byla přidána do diskuze.');
    }
}
