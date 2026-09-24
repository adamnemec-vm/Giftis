<x-app-layout>
    <x-slot name="header">
        <h1 class="text-3xl font-bold text-[#6B1D2F] font-serif">🎁 Moje rezervace</h1>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <p class="text-sm text-gray-500">
            Přehled dárků, které jste vybrali nebo na které jste přispěli u cizích seznamů. Majitelé těchto seznamů nevidí, že jste to byli právě vy – zůstává to jejich překvapení.
        </p>

        <!-- Rezervované dárky -->
        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-[#6B1D2F]">Rezervované dárky ({{ $reservations->count() }})</h2>
            </div>
            @if($reservations->isEmpty())
                <p class="px-6 py-6 text-sm text-gray-400">Zatím jste si nic nerezervovali.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($reservations as $item)
                        <div class="flex items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $item->title }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ $item->formatted_price }}
                                    · seznam
                                    <a href="{{ route('public.wishlists.show', $item->wishlist->share_code) }}" class="text-[#6B1D2F] hover:underline">{{ $item->wishlist->title }}</a>
                                    (autor: {{ $item->wishlist->user->name }})
                                </div>
                            </div>
                            <form method="POST" action="{{ route('public.wishlists.unreserve', [$item->wishlist->share_code, $item]) }}" onsubmit="return confirm('Opravdu zrušit tuto rezervaci?');">
                                @csrf
                                <button type="submit" class="text-xs bg-amber-50 hover:bg-amber-100 text-[#6B1D2F] font-semibold px-3 py-2 rounded-xl border border-[#D4AF37]/50 transition shrink-0">
                                    ↩️ Zrušit
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Příspěvky na skupinové dárky -->
        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-[#6B1D2F]">Příspěvky na skupinové dárky ({{ $contributions->count() }})</h2>
            </div>
            @if($contributions->isEmpty())
                <p class="px-6 py-6 text-sm text-gray-400">Zatím jste na žádný skupinový dárek nepřispěli.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($contributions as $contribution)
                        <div class="flex items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $contribution->giftItem->title }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    Přispěli jste {{ number_format($contribution->amount, 0, ',', ' ') }} {{ $contribution->giftItem->currency ?? 'Kč' }}
                                    · seznam
                                    <a href="{{ route('public.wishlists.show', $contribution->giftItem->wishlist->share_code) }}" class="text-[#6B1D2F] hover:underline">{{ $contribution->giftItem->wishlist->title }}</a>
                                    (autor: {{ $contribution->giftItem->wishlist->user->name }})
                                </div>
                            </div>
                            @if($contribution->giftItem->isAvailable())
                                <form method="POST" action="{{ route('gift-contributions.destroy', [$contribution->giftItem->wishlist->share_code, $contribution->giftItem, $contribution]) }}" onsubmit="return confirm('Opravdu zrušit tento příspěvek?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs bg-amber-50 hover:bg-amber-100 text-[#6B1D2F] font-semibold px-3 py-2 rounded-xl border border-[#D4AF37]/50 transition shrink-0">
                                        ↩️ Zrušit
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] text-gray-400 shrink-0">Dárek už je kompletní</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
