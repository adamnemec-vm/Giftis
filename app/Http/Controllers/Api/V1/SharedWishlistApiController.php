<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GiftItem;
use App\Models\Wishlist;
use App\Notifications\GiftItemReserved;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SharedWishlistApiController extends Controller
{
    public function show(Request $request, string $shareCode): JsonResponse
    {
        $wishlist = $this->findWishlist($shareCode);
        $wishlist->load(['user', 'items.contributions.contributor', 'items.comments']);

        $user = $request->user();
        $isOwner = $user->id === $wishlist->user_id;

        return response()->json([
            'wishlist' => ApiPresenter::wishlist($wishlist),
            'items' => $wishlist->items->map(fn ($i) => ApiPresenter::sharedItem($i, $user, $isOwner))->values(),
            'is_owner' => $isOwner,
            'can_reserve' => ! $isOwner,
        ]);
    }

    public function reserve(Request $request, string $shareCode, GiftItem $item): JsonResponse
    {
        $wishlist = $this->findWishlist($shareCode);
        $this->assertItemInWishlist($item, $wishlist);
        $user = $request->user();

        if ($user->id === $wishlist->user_id) {
            return $this->error('Nemůžete si rezervovat dárky ve svém vlastním seznamu.');
        }
        $reserved = DB::transaction(function () use ($item, $user) {
            $locked = GiftItem::whereKey($item->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'available') {
                return null;
            }

            $locked->update([
                'status' => 'reserved',
                'reserved_by_name' => $user->name,
                'reserved_by_user_id' => $user->id,
                'reservation_token' => null,
                'reserved_at' => now(),
            ]);

            return $locked;
        });

        if (! $reserved) {
            return $this->error('Tento dárek je již rezervovaný někomu jinému.', 409);
        }

        $wishlist->user->notify(new GiftItemReserved($reserved));

        return $this->itemResponse($reserved, $user, $wishlist);
    }

    public function unreserve(Request $request, string $shareCode, GiftItem $item): JsonResponse
    {
        $wishlist = $this->findWishlist($shareCode);
        $this->assertItemInWishlist($item, $wishlist);
        $user = $request->user();

        if ($item->reserved_by_user_id !== $user->id) {
            return $this->error('Nemáte oprávnění zrušit rezervaci tohoto dárku.', 403);
        }

        $item->update([
            'status' => 'available',
            'reserved_by_name' => null,
            'reserved_by_user_id' => null,
            'reservation_token' => null,
            'reserved_at' => null,
        ]);

        return $this->itemResponse($item, $user, $wishlist);
    }

    public function contribute(Request $request, string $shareCode, GiftItem $item): JsonResponse
    {
        $wishlist = $this->findWishlist($shareCode);
        $this->assertItemInWishlist($item, $wishlist);
        $user = $request->user();

        if (! $item->is_group_gift) {
            return $this->error('Tento dárek nelze spolufinancovat.');
        }
        if ($user->id === $wishlist->user_id) {
            return $this->error('Nemůžete přispívat na dárky ve svém vlastním seznamu.');
        }
        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:1']]);

        $result = DB::transaction(function () use ($item, $user, $validated) {
            $locked = GiftItem::whereKey($item->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'available') {
                return ['error' => 'Tento dárek je už kompletně vybraný.'];
            }

            $remaining = $locked->remaining_amount;
            if ((float) $validated['amount'] > $remaining) {
                return ['error' => sprintf('Do plné částky zbývá jen %s Kč, zadejte prosím nižší příspěvek.', number_format($remaining, 0, ',', ' '))];
            }

            $locked->contributions()->create([
                'contributor_name' => $user->name,
                'contributor_user_id' => $user->id,
                'amount' => $validated['amount'],
                'contribution_token' => null,
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
            return $this->error($result['error'], 409);
        }

        if ($result['completed']) {
            $wishlist->user->notify(new GiftItemReserved($result['item']));
        }

        return $this->itemResponse($result['item'], $user, $wishlist, 201);
    }

    public function removeContribution(Request $request, string $shareCode, GiftItem $item, int $contribution): JsonResponse
    {
        $wishlist = $this->findWishlist($shareCode);
        $this->assertItemInWishlist($item, $wishlist);
        $user = $request->user();

        $row = $item->contributions()->whereKey($contribution)->first();
        abort_if(! $row, 404);

        if ($row->contributor_user_id !== $user->id) {
            return $this->error('Nemáte oprávnění zrušit tento příspěvek.', 403);
        }
        if ($item->status !== 'available') {
            return $this->error('Dárek je už kompletně vybraný, příspěvek nelze zrušit.', 409);
        }

        $row->delete();

        return $this->itemResponse($item, $user, $wishlist);
    }

    public function comment(Request $request, string $shareCode, GiftItem $item): JsonResponse
    {
        $wishlist = $this->findWishlist($shareCode);
        $this->assertItemInWishlist($item, $wishlist);
        $user = $request->user();

        if (! $item->is_group_gift) {
            return $this->error('Diskuze je dostupná jen u skupinových dárků.');
        }
        abort_if($user->id === $wishlist->user_id, 403);

        $item->load('contributions');
        if (! $item->isContributor($user->id, null)) {
            return $this->error('Do diskuze mohou psát jen lidé, kteří na tento dárek přispěli.', 403);
        }

        $validated = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $item->comments()->create([
            'author_user_id' => $user->id,
            'author_name' => $user->name,
            'commenter_token' => null,
            'body' => $validated['body'],
        ]);

        return $this->itemResponse($item, $user, $wishlist, 201);
    }

    private function findWishlist(string $shareCode): Wishlist
    {
        $wishlist = Wishlist::where('share_code', $shareCode)->firstOrFail();
        abort_if($wishlist->isSuspended(), 404);

        return $wishlist;
    }

    private function assertItemInWishlist(GiftItem $item, Wishlist $wishlist): void
    {
        abort_if($item->wishlist_id !== $wishlist->id, 404);
    }

    private function itemResponse(GiftItem $item, $user, Wishlist $wishlist, int $status = 200): JsonResponse
    {
        $item->refresh()->load(['contributions.contributor', 'comments']);

        return response()->json([
            'item' => ApiPresenter::sharedItem($item, $user, $user->id === $wishlist->user_id),
        ], $status);
    }

    private function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
