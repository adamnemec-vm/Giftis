<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGiftItemRequest;
use App\Http\Requests\UpdateGiftItemRequest;
use App\Models\GiftItem;
use App\Models\Wishlist;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GiftItemApiController extends Controller
{
    public function store(StoreGiftItemRequest $request, Wishlist $wishlist): JsonResponse
    {
        $this->authorize('create', [GiftItem::class, $wishlist]);

        $validated = $request->validated();

        $item = $wishlist->items()->create([
            'title' => $validated['title'],
            'url' => $validated['url'] ?? null,
            'price' => $validated['price'] ?? null,
            'currency' => $validated['currency'] ?? 'Kč',
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'is_group_gift' => $request->boolean('is_group_gift'),
            'image_path' => $this->imagePath($request),
            'status' => 'available',
        ]);

        return response()->json(['item' => ApiPresenter::ownerItem($item->load('contributions'))], 201);
    }

    public function update(UpdateGiftItemRequest $request, GiftItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $validated = $request->validated();

        $newImage = $this->imagePath($request);
        if ($newImage !== null) {
            if ($item->image_path && ! str_starts_with($item->image_path, 'http') && ! str_starts_with($item->image_path, '/images/')) {
                Storage::disk('public')->delete($item->image_path);
            }
            $item->image_path = $newImage;
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

        return response()->json(['item' => ApiPresenter::ownerItem($item->load('contributions'))]);
    }

    public function destroy(GiftItem $item): JsonResponse
    {
        $this->authorize('delete', $item);

        if ($item->image_path && ! str_starts_with($item->image_path, 'http') && ! str_starts_with($item->image_path, '/images/')) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return response()->json(['message' => 'Dárek byl smazán.']);
    }

    public function markReserved(GiftItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $item->update([
            'status' => 'reserved',
            'reserved_by_name' => 'Vlastníkem seznamu',
            'reserved_at' => now(),
        ]);

        return response()->json(['item' => ApiPresenter::ownerItem($item->load('contributions'))]);
    }

    public function clearReservation(GiftItem $item): JsonResponse
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

        return response()->json(['item' => ApiPresenter::ownerItem($item->load('contributions'))]);
    }

    private function imagePath(Request $request): ?string
    {
        $icon = $request->input('icon');
        if (is_string($icon) && in_array($icon, ApiPresenter::PLACEHOLDER_ICONS, true)) {
            return '/images/placeholders/'.$icon.'.svg';
        }

        if ($request->hasFile('image')) {
            return $request->file('image')->store('gifts', 'public');
        }

        if ($request->filled('image_url')) {
            return $request->input('image_url');
        }

        return null;
    }
}
