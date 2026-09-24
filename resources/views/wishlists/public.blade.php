<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $wishlist->title }} - Giftis</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:600,700|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        h1, h2, h3, .font-serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-[#FAF7F2] text-gray-800 min-h-screen flex flex-col antialiased">

    <!-- Top Navigation Bar -->
    <header class="bg-[#6B1D2F] text-white shadow-md py-4">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="text-2xl">🎁</span>
                <span class="font-serif font-bold text-2xl tracking-wide text-white">Gift<span class="text-[#D4AF37]">is</span></span>
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="text-xs bg-[#541523] hover:bg-[#43101B] px-4 py-2 rounded-full font-medium transition">
                    👤 Můj účet
                </a>
            @else
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}" class="text-xs font-semibold text-white/90 hover:text-white transition">Přihlásit se</a>
                    <a href="{{ route('register') }}" class="text-xs bg-[#D4AF37] hover:bg-amber-400 text-gray-900 px-4 py-2 rounded-full font-bold transition">Vytvořit účet</a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 w-full" x-data="{ activeItem: null }">

        <!-- Flash zprávy -->
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="text-2xl">🎁</span>
                <div class="font-medium text-sm">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-6 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="text-2xl">⚠️</span>
                <div class="font-medium text-sm">{{ session('error') }}</div>
            </div>
        @endif

        @if ($wishlist->is_public)
            <div class="bg-sky-50 border border-sky-200 text-sky-900 px-6 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="text-2xl">💡</span>
                <div class="font-medium text-sm">Tento seznam je veřejný a slouží jen jako inspirace — dárky si zde nelze rezervovat. Pokud chcete autorovi něco koupit, domluvte se s ním přímo.</div>
            </div>
        @endif

        @if (session('manage_link'))
            <div class="bg-amber-50 border border-amber-300 text-amber-900 px-6 py-4 rounded-2xl shadow-sm text-sm space-y-2"
                 x-data="{ copyLink() { navigator.clipboard.writeText('{{ session('manage_link') }}'); alert('Odkaz zkopírován do schránky!'); } }">
                <div class="font-bold flex items-center gap-1">
                    <span>💾</span> Uložte si tento odkaz!
                </div>
                <p>
                    Nejste přihlášeni, takže rezervaci spravuje jen tenhle prohlížeč. Pokud si vymažete cookies nebo budete chtít rezervaci upravit z jiného zařízení, budete tento odkaz potřebovat:
                </p>
                <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                    <code class="flex-1 bg-white border border-amber-200 rounded-xl px-3 py-2 text-xs break-all select-all">{{ session('manage_link') }}</code>
                    <button @click="copyLink()" type="button" class="shrink-0 bg-amber-100 hover:bg-amber-200 text-amber-900 font-semibold px-4 py-2 rounded-xl text-xs transition">
                        📋 Kopírovat
                    </button>
                </div>
                <p class="text-xs">Tip: pošlete si ho e-mailem nebo zprávou sami sobě, ať ho neztratíte.</p>
            </div>
        @endif

        <!-- Header Card Wishlistu -->
        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-[#6B1D2F] via-[#8C273F] to-[#6B1D2F] p-8 text-white relative">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
                    <span class="inline-flex items-center gap-1.5 bg-[#D4AF37] text-gray-900 font-bold px-3 py-1 rounded-full text-xs shadow-sm">
                        <span>{{ $wishlist->occasion_icon }}</span>
                        <span>{{ $wishlist->occasion_label }}</span>
                    </span>

                    <span class="text-xs text-white/90 bg-white/10 backdrop-blur px-4 py-1.5 rounded-full border border-white/20">
                        👤 Autor: <strong class="text-white">{{ $wishlist->user->name }}</strong>
                    </span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-bold font-serif leading-tight">
                    {{ $wishlist->title }}
                </h1>

                @if($wishlist->description)
                    <p class="text-white/80 text-sm mt-2 max-w-2xl leading-relaxed">
                        {{ $wishlist->description }}
                    </p>
                @endif
            </div>

            <!-- Dashboard status & progress -->
            <div class="p-6 md:p-8 bg-[#FAF7F2] border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="space-y-1">
                    @if($wishlist->is_public)
                        <div class="text-xs uppercase tracking-wider font-semibold text-gray-500">Veřejný seznam přání</div>
                        <p class="text-xs text-gray-600 max-w-xl">
                            Tento seznam je veřejně viditelný a slouží pouze jako inspirace — dárky si zde <strong>nelze rezervovat</strong>. Pokud chcete autorovi něco koupit, domluvte se s ním přímo.
                        </p>
                    @else
                        <div class="text-xs uppercase tracking-wider font-semibold text-gray-500">Jak funguje rezervace dárku?</div>
                        <p class="text-xs text-gray-600 max-w-xl">
                            Vyberte si ze seznamu dárek, který chcete autorovi koupit, a klikněte na <strong>"Chci koupit / Rezervovat"</strong>. Položka se uzamkne, aby ji nenakupoval nikdo další. Autor uvidí jen to, že dárek dostane, ale <strong>neuvidí Vaše jméno</strong>! 🤫
                        </p>
                    @endif
                </div>

                <!-- Progress bar -->
                <div class="w-full md:w-72 shrink-0 bg-white p-4 rounded-2xl border border-gray-200">
                    <div class="flex justify-between items-center text-xs font-bold text-[#6B1D2F] mb-1.5">
                        <span>Stav rezervovaných dárků</span>
                        <span>{{ $wishlist->progress_percentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden p-0.5 border border-gray-200">
                        <div class="bg-[#D4AF37] h-full rounded-full transition-all duration-500" style="width: {{ $wishlist->progress_percentage }}%"></div>
                    </div>
                    <div class="text-[11px] text-gray-500 text-center mt-2 font-medium">
                        {{ $wishlist->reserved_count }} z {{ $wishlist->total_count }} dárků již někdo vybral
                    </div>
                </div>
            </div>
        </div>

        <!-- Seznam položek -->
        <div>
            <h2 class="text-2xl font-bold text-[#6B1D2F] mb-6 flex items-center gap-2">
                <span>🎁</span> Dárky v seznamu ({{ $wishlist->items->count() }})
            </h2>

            @if($wishlist->items->isEmpty())
                <div class="bg-white rounded-3xl p-12 text-center border border-[#F0E8DD] shadow-sm">
                    <div class="text-5xl mb-3">📦</div>
                    <h3 class="text-lg font-bold text-gray-800">Tento seznam je zatím prázdný</h3>
                    <p class="text-gray-500 text-xs mt-1">Autor zatím nepřidal žádné položky dárků.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($wishlist->items as $item)
                        @php
                            $isMyReservation = false;
                            if (auth()->check() && $item->reserved_by_user_id === auth()->id()) {
                                $isMyReservation = true;
                            } elseif ($guestToken && $item->reservation_token === $guestToken) {
                                $isMyReservation = true;
                            }

                            $myContributions = $item->is_group_gift
                                ? $item->contributions->filter(function ($c) use ($guestToken) {
                                    return (auth()->check() && $c->contributor_user_id === auth()->id())
                                        || ($guestToken && $c->contribution_token === $guestToken);
                                })
                                : collect();
                        @endphp

                        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                            
                            <!-- Obrázek dárku -->
                            <div class="relative h-52 bg-gray-100 flex items-center justify-center overflow-hidden border-b border-gray-100">
                                @if($item->image_path)
                                    <img src="{{ $item->resolved_image_url }}"
                                         alt="{{ $item->title }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="text-center text-gray-400 p-4">
                                        <span class="text-5xl block mb-2">🎁</span>
                                        <span class="text-xs font-medium">Obrázek není k dispozici</span>
                                    </div>
                                @endif

                                <!-- Priority badge -->
                                @if($item->priority === 'high')
                                    <div class="absolute top-3 left-3 bg-rose-500 text-white font-bold text-[10px] uppercase px-2.5 py-1 rounded-full shadow-md">
                                        🔥 Velké přání
                                    </div>
                                @elseif($item->is_group_gift)
                                    <div class="absolute top-3 left-3 bg-white/90 text-[#6B1D2F] font-bold text-[10px] uppercase px-2.5 py-1 rounded-full shadow-md">
                                        👥 Skupinový dárek
                                    </div>
                                @endif

                                <!-- Status Badge -->
                                <div class="absolute top-3 right-3">
                                    @if($item->isAvailable())
                                        <span class="bg-emerald-500 text-white font-bold text-xs px-3 py-1 rounded-full shadow-md flex items-center gap-1">
                                            <span>✨</span> Volné k rezervaci
                                        </span>
                                    @elseif($isMyReservation)
                                        <span class="bg-[#D4AF37] text-gray-900 font-bold text-xs px-3 py-1 rounded-full shadow-md flex items-center gap-1">
                                            <span>✅</span> Vybrali jste vy
                                        </span>
                                    @else
                                        <span class="bg-gray-700 text-white font-bold text-xs px-3 py-1 rounded-full shadow-md flex items-center gap-1">
                                            <span>🔒</span> Rezervováno
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Detail dárku -->
                            <div class="p-6 space-y-3 flex-grow">
                                <h3 class="text-lg font-bold text-gray-900 font-serif leading-snug">
                                    {{ $item->title }}
                                </h3>

                                @if($item->formatted_price)
                                    <div class="text-2xl font-extrabold text-[#6B1D2F]">
                                        {{ $item->formatted_price }}
                                    </div>
                                @endif

                                @if($item->description)
                                    <p class="text-gray-600 text-xs leading-relaxed">
                                        {{ $item->description }}
                                    </p>
                                @endif

                                @if($item->url)
                                    <div class="pt-2">
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs text-amber-700 hover:text-amber-900 font-semibold underline">
                                            <span>🌐 Koupit v e-shopu</span> ↗
                                        </a>
                                    </div>
                                @endif

                                @if($item->is_group_gift && $item->price)
                                    <div class="pt-2">
                                        <div class="flex justify-between items-center text-[11px] font-bold text-[#6B1D2F] mb-1">
                                            <span>Vybráno na dárek</span>
                                            <span>{{ $item->contribution_percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="bg-[#D4AF37] h-full rounded-full transition-all duration-500" style="width: {{ $item->contribution_percentage }}%"></div>
                                        </div>
                                        @if($item->isAvailable())
                                            <div class="text-[11px] text-gray-500 mt-1">
                                                Zbývá {{ number_format($item->remaining_amount, 0, ',', ' ') }} {{ $item->currency ?? 'Kč' }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Tlačítka rezervace / spolufinancování -->
                            <div class="bg-[#FAF7F2] p-5 border-t border-gray-100 space-y-2">
                                @if($isOwner)
                                    <div class="text-center text-xs text-gray-400 font-medium py-1">
                                        Váš vlastní dárek (Náhled pro přátele)
                                    </div>
                                @elseif($wishlist->is_public)
                                    <div class="text-center text-xs text-gray-400 font-medium py-1">
                                        💡 Veřejný seznam — jen pro inspiraci, rezervace nejsou povolené.
                                    </div>
                                @elseif($item->is_group_gift)
                                    @if($item->isAvailable())
                                        <button @click="activeItem = {{ json_encode($item) }}; $dispatch('open-modal', 'contribute-gift-modal')"
                                                class="w-full inline-flex items-center justify-center gap-2 bg-[#6B1D2F] hover:bg-[#541523] text-white font-bold text-xs py-3 px-4 rounded-xl shadow-md transition transform active:scale-95">
                                            <span>💝</span>
                                            <span>Přispět na dárek</span>
                                        </button>

                                        @if($myContributions->isNotEmpty())
                                            <div class="text-[11px] text-gray-500 text-center font-medium">
                                                Přispěli jste {{ number_format($myContributions->sum('amount'), 0, ',', ' ') }} {{ $item->currency ?? 'Kč' }}
                                            </div>
                                            @foreach($myContributions as $contribution)
                                                <form method="POST" action="{{ route('gift-contributions.destroy', ['share_code' => $wishlist->share_code, 'item' => $item, 'contribution' => $contribution]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-full inline-flex items-center justify-center gap-2 bg-amber-100 hover:bg-amber-200 text-[#6B1D2F] font-bold text-xs py-2 px-4 rounded-xl border border-[#D4AF37]/50 transition">
                                                        <span>↩️</span>
                                                        <span>Zrušit příspěvek {{ number_format($contribution->amount, 0, ',', ' ') }} {{ $item->currency ?? 'Kč' }}</span>
                                                    </button>
                                                </form>
                                            @endforeach
                                        @endif
                                    @else
                                        <button disabled
                                                class="w-full inline-flex items-center justify-center gap-2 bg-gray-200 text-gray-500 font-bold text-xs py-3 px-4 rounded-xl cursor-not-allowed">
                                            <span>🔒</span>
                                            <span>Přátelé už na dárek vybrali celou částku</span>
                                        </button>
                                    @endif

                                    @if($myContributions->isNotEmpty())
                                        @php
                                            $suggestedBuyer = $item->suggested_buyer;
                                            $isSuggestedBuyerMe = $suggestedBuyer && (
                                                (auth()->check() && $suggestedBuyer->contributor_user_id === auth()->id())
                                                || ($guestToken && $suggestedBuyer->contribution_token === $guestToken)
                                            );
                                        @endphp
                                        <div class="mt-3 pt-3 border-t border-gray-200 space-y-2" x-data="{ showThread: false }">
                                            @if($suggestedBuyer)
                                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-900">
                                                    <div class="font-bold">🛒 Navrhovaný kupující: {{ $isSuggestedBuyerMe ? 'Vy' : ($suggestedBuyer->contributor_user_id ? $suggestedBuyer->contributor->name : $suggestedBuyer->contributor_name) }}</div>
                                                    <p class="mt-1">Domluvte se s ostatními přispěvateli v diskuzi níže, kdo dárek reálně koupí a jak si mezi sebou vyrovnáte peníze.</p>
                                                </div>
                                            @endif

                                            <button type="button" @click="showThread = ! showThread" class="w-full text-center text-xs font-semibold text-[#6B1D2F] underline py-1">
                                                💬 Diskuze mezi přispěvateli ({{ $item->comments->count() }})
                                            </button>

                                            <div x-show="showThread" class="space-y-2">
                                                @forelse($item->comments as $comment)
                                                    <div class="bg-white border border-gray-200 rounded-xl p-2.5 text-xs">
                                                        <div class="font-semibold text-gray-700">{{ $comment->author_name }}</div>
                                                        <div class="text-gray-600 break-words">{{ $comment->body }}</div>
                                                        <div class="text-[10px] text-gray-400 mt-1">{{ $comment->created_at->diffForHumans() }}</div>
                                                    </div>
                                                @empty
                                                    <p class="text-[11px] text-gray-400 text-center">Zatím žádné zprávy. Napište první!</p>
                                                @endforelse

                                                <form method="POST" action="{{ route('gift-item-comments.store', ['share_code' => $wishlist->share_code, 'item' => $item]) }}" class="flex gap-2 pt-1">
                                                    @csrf
                                                    <input type="text" name="body" maxlength="1000" required placeholder="Napsat zprávu…" class="flex-1 rounded-xl border-gray-300 text-xs focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" />
                                                    <button type="submit" class="shrink-0 bg-[#6B1D2F] hover:bg-[#541523] text-white text-xs font-semibold px-3 rounded-xl transition">Odeslat</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                @elseif($item->isAvailable())
                                    <button @click="activeItem = {{ json_encode($item) }}; $dispatch('open-modal', 'reserve-gift-modal')"
                                            class="w-full inline-flex items-center justify-center gap-2 bg-[#6B1D2F] hover:bg-[#541523] text-white font-bold text-xs py-3 px-4 rounded-xl shadow-md transition transform active:scale-95">
                                        <span>🎁</span>
                                        <span>Chci koupit / Rezervovat</span>
                                    </button>
                                @elseif($isMyReservation)
                                    <form method="POST" action="{{ route('public.wishlists.unreserve', ['share_code' => $wishlist->share_code, 'item' => $item]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="w-full inline-flex items-center justify-center gap-2 bg-amber-100 hover:bg-amber-200 text-[#6B1D2F] font-bold text-xs py-3 px-4 rounded-xl border border-[#D4AF37]/50 transition">
                                            <span>↩️</span>
                                            <span>Zrušit moji rezervaci</span>
                                        </button>
                                    </form>
                                @else
                                    <button disabled
                                            class="w-full inline-flex items-center justify-center gap-2 bg-gray-200 text-gray-500 font-bold text-xs py-3 px-4 rounded-xl cursor-not-allowed">
                                        <span>🔒</span>
                                        <span>Již rezervováno někomu jinému</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Modal pro potvrzení rezervace -->
        <x-modal name="reserve-gift-modal" focusable>
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                        <span>🎁</span> Rezervace dárku
                    </h2>
                    <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4" x-show="activeItem">
                    <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-gray-200 mb-6 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-[#6B1D2F] flex items-center justify-center text-2xl font-bold shrink-0">
                            🎁
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900" x-text="activeItem?.title"></h4>
                            <div class="text-xs text-[#6B1D2F] font-semibold" x-text="activeItem?.formatted_price || 'Cena neuvedena'"></div>
                        </div>
                    </div>

                    <form method="POST" :action="`/s/{{ $wishlist->share_code }}/items/${activeItem?.id}/reserve`" class="space-y-4">
                        @csrf

                        @guest
                            <div>
                                <x-input-label for="buyer_name" value="Vaše jméno nebo přezdívka *" class="font-medium text-gray-700" />
                                <x-text-input id="buyer_name" name="buyer_name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. Teta Alena nebo Kamarád Petr" required />
                                <p class="text-[11px] text-gray-400 mt-1">Jméno slouží pouze pro vás, abyste poznali svou rezervaci. Autor seznamu ho neuvidí!</p>
                            </div>
                        @else
                            <div class="text-xs text-gray-600 bg-amber-50 p-3 rounded-xl border border-amber-200">
                                Jste přihlášeni jako: <strong>{{ auth()->user()->name }}</strong>
                            </div>
                        @endguest

                        <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200 text-xs text-amber-900 space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <span>🕵️‍♀️</span> Zůstane to jako překvapení!
                            </div>
                            <p>
                                Autor seznamu (<strong>{{ $wishlist->user->name }}</strong>) uvidí pouze to, že dárek byl rezervován. Vaše jméno neuvidí!
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                                Zrušit
                            </x-secondary-button>
                            <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                                Potvrdit rezervaci dárku
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </x-modal>

        <!-- Modal pro příspěvek na skupinový dárek -->
        <x-modal name="contribute-gift-modal" focusable>
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                        <span>💝</span> Přispět na dárek
                    </h2>
                    <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4" x-show="activeItem">
                    <div class="bg-[#FAF7F2] p-4 rounded-2xl border border-gray-200 mb-6 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-[#6B1D2F] flex items-center justify-center text-2xl font-bold shrink-0">
                            💝
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900" x-text="activeItem?.title"></h4>
                            <div class="text-xs text-[#6B1D2F] font-semibold" x-text="activeItem?.formatted_price || 'Cena neuvedena'"></div>
                        </div>
                    </div>

                    <form method="POST" :action="`/s/{{ $wishlist->share_code }}/items/${activeItem?.id}/contributions`" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="amount" value="Kolik chcete přispět (Kč) *" class="font-medium text-gray-700" />
                            <x-text-input id="amount" name="amount" type="number" step="1" min="1" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. 500" required />
                        </div>

                        @guest
                            <div>
                                <x-input-label for="contributor_name" value="Vaše jméno nebo přezdívka *" class="font-medium text-gray-700" />
                                <x-text-input id="contributor_name" name="contributor_name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. Teta Alena nebo Kamarád Petr" required />
                                <p class="text-[11px] text-gray-400 mt-1">Jméno slouží pouze pro vás, abyste poznali svůj příspěvek. Autor seznamu ho neuvidí!</p>
                            </div>
                        @else
                            <div class="text-xs text-gray-600 bg-amber-50 p-3 rounded-xl border border-amber-200">
                                Jste přihlášeni jako: <strong>{{ auth()->user()->name }}</strong>
                            </div>
                        @endguest

                        <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200 text-xs text-amber-900 space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <span>🕵️‍♀️</span> Zůstane to jako překvapení!
                            </div>
                            <p>
                                Autor seznamu (<strong>{{ $wishlist->user->name }}</strong>) uvidí jen vybranou částku, ne kdo a kolik přispěl.
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                                Zrušit
                            </x-secondary-button>
                            <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                                Potvrdit příspěvek
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </x-modal>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-[#F0E8DD] py-8 text-center text-sm text-gray-500 mt-12">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">🎁</span>
                <span class="font-serif font-bold text-[#6B1D2F]">Gift<span class="text-[#D4AF37]">is</span></span>
                <span>&copy; {{ date('Y') }} — Pomocník s nákupem dárků.</span>
            </div>
            <div class="text-xs text-gray-400 flex gap-4">
                <a href="{{ route('privacy') }}" class="hover:text-[#6B1D2F]">Ochrana údajů</a>
                <a href="{{ route('terms') }}" class="hover:text-[#6B1D2F]">Podmínky</a>
            </div>
        </div>
    </footer>

</body>
</html>
