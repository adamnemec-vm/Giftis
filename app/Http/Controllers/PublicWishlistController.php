<?php

namespace App\Http\Controllers;

use App\Models\GiftContribution;
use App\Models\GiftItem;
use App\Models\Wishlist;
use App\Notifications\GiftItemReserved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicWishlistController extends Controller
{
    public function show(Request $request, string $share_code)
    {
        $wishlist = Wishlist::with(['user', 'items.contributions.contributor', 'items.comments'])->where('share_code', $share_code)->firstOrFail();

        if ($wishlist->isSuspended()) {
            abort(404);
        }

        $isOwner = auth()->check() && auth()->id() === $wishlist->user_id;

        // Vytvořit nebo načíst unikátní guest token v session pro anonymní rezervace hostů
        if (! session()->has('giftis_guest_token')) {
            session()->put('giftis_guest_token', Str::uuid()->toString());
        }
        $guestToken = session()->get('giftis_guest_token');

        return view('wishlists.public', compact('wishlist', 'isOwner', 'guestToken'));
    }

    public function reserve(Request $request, string $share_code, GiftItem $item)
    {
        $wishlist = Wishlist::where('share_code', $share_code)->firstOrFail();

        if ($item->wishlist_id !== $wishlist->id || $wishlist->isSuspended()) {
            abort(404);
        }

        if (auth()->check() && auth()->id() === $wishlist->user_id) {
            return redirect()->back()->with('error', 'Nemůžete si rezervovat dárky ve svém vlastním seznamu.');
        }

        if ($wishlist->is_public) {
            return redirect()->back()->with('error', 'Tento seznam je veřejný a slouží pouze jako inspirace — rezervace dárků zde nejsou povolené.');
        }

        $reservedByName = 'Anonymní přítel';
        $userId = null;

        if (auth()->check()) {
            $reservedByName = auth()->user()->name;
            $userId = auth()->id();
        } else {
            $validated = $request->validate([
                'buyer_name' => 'required|string|max:100',
            ]);
            $reservedByName = $validated['buyer_name'];
        }

        $guestToken = session()->get('giftis_guest_token', Str::uuid()->toString());
        session()->put('giftis_guest_token', $guestToken);

        // Zamknutí řádku v transakci zabraňuje tomu, aby si dva hosté rezervovali
        // stejný dárek souběžně (check-then-act race condition).
        $reservedItem = DB::transaction(function () use ($item, $reservedByName, $userId, $guestToken) {
            $locked = GiftItem::whereKey($item->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'available') {
                return null;
            }

            $locked->update([
                'status' => 'reserved',
                'reserved_by_name' => $reservedByName,
                'reserved_by_user_id' => $userId,
                'reservation_token' => $guestToken,
                'reserved_at' => now(),
            ]);

            return $locked;
        });

        if (! $reservedItem) {
            return redirect()->back()->with('error', 'Tento dárek je již rezervovaný někomu jinému.');
        }

        $wishlist->user->notify(new GiftItemReserved($reservedItem));

        $response = redirect()->back()->with('success', '🎁 Dárek byl označen jako vybraný/koupený! Vlastník seznamu neuvidí vaše jméno, zůstane to jako překvapení.');

        if (! auth()->check()) {
            $response->with('manage_link', route('public.wishlists.restore-session', [$share_code, $guestToken]));
        }

        return $response;
    }

    public function restoreSession(Request $request, string $share_code, string $token)
    {
        $wishlist = Wishlist::where('share_code', $share_code)->firstOrFail();

        $hasReservation = GiftItem::where('wishlist_id', $wishlist->id)->where('reservation_token', $token)->exists();
        $hasContribution = GiftContribution::whereHas('giftItem', fn ($query) => $query->where('wishlist_id', $wishlist->id))
            ->where('contribution_token', $token)
            ->exists();

        if (! $hasReservation && ! $hasContribution) {
            abort(404);
        }

        session()->put('giftis_guest_token', $token);

        return redirect()->route('public.wishlists.show', $share_code)
            ->with('success', 'Vaše rezervace byla obnovena v tomto prohlížeči, teď ji můžete i zrušit.');
    }

    public function unreserve(Request $request, string $share_code, GiftItem $item)
    {
        $wishlist = Wishlist::where('share_code', $share_code)->firstOrFail();

        if ($item->wishlist_id !== $wishlist->id || $wishlist->isSuspended()) {
            abort(404);
        }

        if ($wishlist->is_public) {
            return redirect()->back()->with('error', 'Tento seznam je veřejný a slouží pouze jako inspirace — rezervace dárků zde nejsou povolené.');
        }

        $guestToken = session()->get('giftis_guest_token');
        $isMyReservation = false;

        if (auth()->check() && $item->reserved_by_user_id === auth()->id()) {
            $isMyReservation = true;
        } elseif ($guestToken && $item->reservation_token === $guestToken) {
            $isMyReservation = true;
        }

        if (! $isMyReservation) {
            return redirect()->back()->with('error', 'Nemáte oprávnění zrušit rezervaci tohoto dárku.');
        }

        $item->update([
            'status' => 'available',
            'reserved_by_name' => null,
            'reserved_by_user_id' => null,
            'reservation_token' => null,
            'reserved_at' => null,
        ]);

        return redirect()->back()->with('success', 'Rezervace dárku byla zrušena. Dárek je opět k dispozici.');
    }
}
