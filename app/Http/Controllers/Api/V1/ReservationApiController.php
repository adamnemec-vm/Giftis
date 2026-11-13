<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $reservations = $user->reservations()
            ->with('wishlist.user')
            ->latest('reserved_at')
            ->get();

        $contributions = $user->contributions()
            ->with(['giftItem.wishlist.user', 'giftItem.contributions', 'giftItem.comments'])
            ->latest()
            ->get();

        return response()->json([
            'reservations' => $reservations->map(fn ($item) => [
                'item' => ApiPresenter::ownerItem($item),
                'wishlist' => [
                    'id' => $item->wishlist->id,
                    'title' => $item->wishlist->title,
                    'share_code' => $item->wishlist->share_code,
                    'author_name' => $item->wishlist->user->name,
                ],
            ])->values(),
            'contributions' => $contributions->filter(fn ($c) => $c->giftItem)->map(fn ($c) => [
                'id' => $c->id,
                'amount' => (float) $c->amount,
                'item' => ApiPresenter::sharedItem($c->giftItem, $user, false),
                'wishlist' => [
                    'id' => $c->giftItem->wishlist->id,
                    'title' => $c->giftItem->wishlist->title,
                    'share_code' => $c->giftItem->wishlist->share_code,
                    'author_name' => $c->giftItem->wishlist->user->name,
                ],
            ])->values(),
        ]);
    }
}
