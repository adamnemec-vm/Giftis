<?php

namespace App\Http\Controllers;

use App\Models\GiftContribution;
use App\Models\GiftItem;
use App\Models\Wishlist;
use App\Notifications\GiftItemReserved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GiftContributionController extends Controller
{
    public function store(Request $request, string $share_code, GiftItem $item)
    {
        $wishlist = Wishlist::where('share_code', $share_code)->firstOrFail();

        if ($item->wishlist_id !== $wishlist->id || $wishlist->isSuspended()) {
            abort(404);
        }

        if (! $item->is_group_gift) {
            return redirect()->back()->with('error', 'Tento dárek nelze spolufinancovat.');
        }

        if (auth()->check() && auth()->id() === $wishlist->user_id) {
            return redirect()->back()->with('error', 'Nemůžete přispívat na dárky ve svém vlastním seznamu.');
        }

        if ($wishlist->is_public) {
            return redirect()->back()->with('error', 'Tento seznam je veřejný a slouží pouze jako inspirace — rezervace ani příspěvky na dárky zde nejsou povolené.');
        }

        $contributorName = auth()->check() ? auth()->user()->name : null;
        $userId = auth()->check() ? auth()->id() : null;

        $rules = [
            'amount' => ['required', 'numeric', 'min:1'],
        ];
        if (! auth()->check()) {
            $rules['contributor_name'] = ['required', 'string', 'max:100'];
        }
        $validated = $request->validate($rules);

        if (! auth()->check()) {
            $contributorName = $validated['contributor_name'];
        }

        $guestToken = session()->get('giftis_guest_token', Str::uuid()->toString());
        session()->put('giftis_guest_token', $guestToken);

        $result = DB::transaction(function () use ($item, $contributorName, $userId, $guestToken, $validated) {
            $locked = GiftItem::whereKey($item->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'available') {
                return ['error' => 'Tento dárek je už kompletně vybraný.'];
            }

            $remaining = $locked->remaining_amount;
            if ((float) $validated['amount'] > $remaining) {
                return ['error' => sprintf('Do plné částky zbývá jen %s Kč, zadejte prosím nižší příspěvek.', number_format($remaining, 0, ',', ' '))];
            }

            $locked->contributions()->create([
                'contributor_name' => $contributorName,
                'contributor_user_id' => $userId,
                'amount' => $validated['amount'],
                'contribution_token' => $userId ? null : $guestToken,
            ]);

            $locked->refresh();

            $completed = false;
            if ($locked->contributed_total >= (float) $locked->price) {
                $locked->update([
                    'status' => 'reserved',
                    'reserved_by_name' => 'Skupina přátel',
                    'reserved_at' => now(),
                ]);
                $completed = true;
            }

            return ['item' => $locked, 'completed' => $completed];
        });

        if (isset($result['error'])) {
            return redirect()->back()->with('error', $result['error']);
        }

        if ($result['completed']) {
            $wishlist->user->notify(new GiftItemReserved($result['item']));
        }

        $response = redirect()->back()->with('success', '🎁 Váš příspěvek byl zaznamenán! Vlastník seznamu neuvidí vaše jméno, zůstane to jako překvapení.');

        if (! auth()->check()) {
            $response->with('manage_link', route('public.wishlists.restore-session', [$share_code, $guestToken]));
        }

        return $response;
    }

    public function destroy(Request $request, string $share_code, GiftItem $item, GiftContribution $contribution)
    {
        $wishlist = Wishlist::where('share_code', $share_code)->firstOrFail();

        if ($item->wishlist_id !== $wishlist->id || $contribution->gift_item_id !== $item->id || $wishlist->isSuspended()) {
            abort(404);
        }

        if ($wishlist->is_public) {
            return redirect()->back()->with('error', 'Tento seznam je veřejný a slouží pouze jako inspirace — rezervace ani příspěvky na dárky zde nejsou povolené.');
        }

        $guestToken = session()->get('giftis_guest_token');
        $isMyContribution = (auth()->check() && $contribution->contributor_user_id === auth()->id())
            || ($guestToken && $contribution->contribution_token === $guestToken);

        if (! $isMyContribution) {
            return redirect()->back()->with('error', 'Nemáte oprávnění zrušit tento příspěvek.');
        }

        if ($item->status !== 'available') {
            return redirect()->back()->with('error', 'Dárek je už kompletně vybraný, příspěvek nelze zrušit.');
        }

        $contribution->delete();

        return redirect()->back()->with('success', 'Váš příspěvek byl zrušen.');
    }
}
