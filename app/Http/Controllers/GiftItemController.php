<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGiftItemRequest;
use App\Http\Requests\UpdateGiftItemRequest;
use App\Models\GiftItem;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Storage;

class GiftItemController extends Controller
{
    public function store(StoreGiftItemRequest $request, Wishlist $wishlist)
    {
        $this->authorize('create', [GiftItem::class, $wishlist]);

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('gifts', 'public');
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->input('image_url');
        }

        $wishlist->items()->create([
            'title' => $validated['title'],
            'url' => $validated['url'] ?? null,
            'price' => $validated['price'] ?? null,
            'currency' => $validated['currency'] ?? 'Kč',
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'is_group_gift' => $request->boolean('is_group_gift'),
            'image_path' => $imagePath,
            'status' => 'available',
        ]);

        return redirect()->route('wishlists.show', $wishlist)
            ->with('success', 'Dárek byl přidán do vašeho seznamu!');
    }

    public function update(UpdateGiftItemRequest $request, GiftItem $item)
    {
        $this->authorize('update', $item);

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($item->image_path && ! str_starts_with($item->image_path, 'http')) {
                Storage::disk('public')->delete($item->image_path);
            }
            $item->image_path = $request->file('image')->store('gifts', 'public');
        } elseif ($request->filled('image_url')) {
            $item->image_path = $request->input('image_url');
        }

        $item->update([
            'title' => $validated['title'],
            'url' => $validated['url'] ?? null,
            'price' => $validated['price'] ?? null,
            'currency' => $validated['currency'] ?? 'Kč',
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'is_group_gift' => $request->boolean('is_group_gift'),
        ]);

        return redirect()->route('wishlists.show', $item->wishlist)
            ->with('success', 'Položka dárku byla upravena.');
    }

    public function destroy(GiftItem $item)
    {
        $this->authorize('delete', $item);

        $wishlist = $item->wishlist;

        if ($item->image_path && ! str_starts_with($item->image_path, 'http')) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return redirect()->route('wishlists.show', $wishlist)
            ->with('success', 'Položka byla smazána.');
    }

    public function markReserved(GiftItem $item)
    {
        $this->authorize('update', $item);

        $item->update([
            'status' => 'reserved',
            'reserved_by_name' => 'Vlastníkem seznamu',
            'reserved_at' => now(),
        ]);

        return redirect()->route('wishlists.show', $item->wishlist)
            ->with('success', 'Dárek byl označen jako rezervovaný.');
    }

    public function clearReservation(GiftItem $item)
    {
        $this->authorize('update', $item);

        if ($item->is_group_gift) {
            $item->contributions()->delete();
        }

        $item->update([
            'status' => 'available',
            'reserved_by_name' => null,
            'reserved_by_user_id' => null,
            'reservation_token' => null,
            'reserved_at' => null,
        ]);

        return redirect()->route('wishlists.show', $item->wishlist)
            ->with('success', 'Rezervace dárku byla ručně uvolněna, je opět k dispozici.');
    }
}
