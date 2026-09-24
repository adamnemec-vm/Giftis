<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWishlistRequest;
use App\Http\Requests\UpdateWishlistRequest;
use App\Models\Wishlist;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $myWishlists = $user->wishlists()->with('items')->latest()->get();

        // Ostatní veřejné wishlisty od jiných uživatelů pro inspiraci/přehled
        $publicWishlists = Wishlist::with(['user', 'items'])
            ->where('user_id', '!=', $user->id)
            ->where('is_public', true)
            ->whereNull('suspended_at')
            ->latest()
            ->take(6)
            ->get();

        // Seznamy ostatních členů skupin, které zpřístupnili konkrétně nám
        $myGroupIds = $user->groups()->pluck('groups.id');
        $groupWishlists = Wishlist::with(['user', 'items', 'groups' => function ($query) use ($myGroupIds) {
            $query->whereIn('groups.id', $myGroupIds);
        }])
            ->whereHas('groups', fn ($query) => $query->whereIn('groups.id', $myGroupIds))
            ->where('user_id', '!=', $user->id)
            ->whereNull('suspended_at')
            ->latest()
            ->get();

        return view('wishlists.index', compact('myWishlists', 'publicWishlists', 'groupWishlists'));
    }

    public function store(StoreWishlistRequest $request)
    {
        $validated = $request->validated();

        $shareCode = Str::lower(Str::random(10));
        while (Wishlist::where('share_code', $shareCode)->exists()) {
            $shareCode = Str::lower(Str::random(10));
        }

        $wishlist = auth()->user()->wishlists()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'occasion' => $validated['occasion'],
            'event_date' => $validated['event_date'] ?? null,
            'share_code' => $shareCode,
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()->route('wishlists.show', $wishlist)
            ->with('success', 'Seznam přání byl úspěšně vytvořen!');
    }

    public function show(Wishlist $wishlist)
    {
        $this->authorize('view', $wishlist);

        $wishlist->load('items');

        $isOwner = auth()->id() === $wishlist->user_id;
        $myGroups = $isOwner ? auth()->user()->groups()->get() : collect();
        $wishlistGroupIds = $isOwner ? $wishlist->groups()->pluck('groups.id')->all() : [];

        return view('wishlists.show', compact('wishlist', 'isOwner', 'myGroups', 'wishlistGroupIds'));
    }

    public function update(UpdateWishlistRequest $request, Wishlist $wishlist)
    {
        $this->authorize('update', $wishlist);

        $validated = $request->validated();

        $wishlist->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'occasion' => $validated['occasion'],
            'event_date' => $validated['event_date'] ?? null,
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()->route('wishlists.show', $wishlist)
            ->with('success', 'Seznam byl úspěšně aktualizován.');
    }

    public function destroy(Wishlist $wishlist)
    {
        $this->authorize('delete', $wishlist);

        $isOwnList = auth()->id() === $wishlist->user_id;
        $ownerId = $wishlist->user_id;

        $wishlist->delete();

        if ($isOwnList) {
            return redirect()->route('wishlists.index')
                ->with('success', 'Seznam byl smazán.');
        }

        return redirect()->route('admin.users.show', $ownerId)
            ->with('success', 'Seznam byl jako administrátor trvale smazán.');
    }

    public function qrCode(Wishlist $wishlist)
    {
        $this->authorize('view', $wishlist);

        $result = (new Builder)->build(
            writer: new SvgWriter,
            data: route('public.wishlists.show', $wishlist->share_code),
            size: 320,
            margin: 10,
            foregroundColor: new Color(107, 29, 47),
            backgroundColor: new Color(255, 255, 255),
        );

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType())
            ->header('Content-Disposition', 'inline; filename="giftis-'.$wishlist->share_code.'.svg"');
    }
}
