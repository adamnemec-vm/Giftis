<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4" x-data="{}">
            <div>
                <h1 class="text-3xl font-bold text-[#6B1D2F]">
                    Vítejte zpět, {{ auth()->user()->name }}! 🎁
                </h1>
                <p class="text-gray-600 text-sm mt-1">
                    Spravujte své seznamy přání k Vánocům, narozeninám nebo sledujte přání svých blízkých.
                </p>
            </div>

            <button @click="$dispatch('open-modal', 'create-wishlist-modal')"
                class="inline-flex items-center gap-2 bg-[#6B1D2F] hover:bg-[#541523] text-white font-semibold px-6 py-3 rounded-2xl shadow-md transition transform active:scale-95">
                <span>➕</span>
                <span>Vytvořit nový wishlist</span>
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12" x-data="{ copyLink(url) { navigator.clipboard.writeText(url); alert('Odkaz na wishlist byl zkopírován do schránky!'); } }">
        
        <!-- Sekce 1: Moje Wishlisty -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-[#6B1D2F] flex items-center gap-2">
                    <span>📜</span> Moje Wishlisty ({{ $myWishlists->count() }})
                </h2>
            </div>

            @if($myWishlists->isEmpty())
                <div class="bg-white rounded-3xl p-12 text-center border border-[#F0E8DD] shadow-sm">
                    <div class="text-6xl mb-4">🎁</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Zatím nemáte žádný seznam přání</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">Vytvořte si svůj první wishlist pro Vánoce nebo narozeniny a sdílejte ho s rodinou a přáteli!</p>
                    <button @click="$dispatch('open-modal', 'create-wishlist-modal')" class="bg-[#6B1D2F] text-white px-6 py-3 rounded-2xl font-medium hover:bg-[#541523] transition">
                        Vytvořit první seznam
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($myWishlists as $wishlist)
                        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm hover:shadow-md transition duration-200 overflow-hidden flex flex-col justify-between">
                            <!-- Card Header -->
                            <div class="bg-gradient-to-r from-[#6B1D2F] to-[#8C273F] p-6 text-white relative">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-flex items-center gap-1 bg-[#D4AF37] text-gray-900 font-semibold px-3 py-1 rounded-full text-xs shadow-sm">
                                        <span>{{ $wishlist->occasion_icon }}</span>
                                        <span>{{ $wishlist->occasion_label }}</span>
                                    </span>
                                    
                                    @if($wishlist->event_date)
                                        <span class="text-xs text-white/80 flex items-center gap-1">
                                            📅 {{ $wishlist->event_date->format('d. m. Y') }}
                                        </span>
                                    @endif
                                </div>

                                <h3 class="text-xl font-bold font-serif leading-tight">
                                    <a href="{{ route('wishlists.show', $wishlist) }}" class="hover:text-[#D4AF37] transition">
                                        {{ $wishlist->title }}
                                    </a>
                                </h3>
                                @if($wishlist->description)
                                    <p class="text-white/80 text-xs mt-1 line-clamp-1">{{ $wishlist->description }}</p>
                                @endif
                            </div>

                            <!-- Card Body: Progress Bar & Items Preview -->
                            <div class="p-6 space-y-5 flex-grow">
                                <!-- Progress Bar -->
                                <div>
                                    <div class="flex justify-between items-center text-xs font-semibold mb-1">
                                        <span class="text-gray-600">Stav rezervací</span>
                                        <span class="text-[#6B1D2F] font-bold">{{ $wishlist->progress_percentage }}% rezervováno</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden p-0.5 border border-gray-200">
                                        <div class="bg-[#D4AF37] h-full rounded-full transition-all duration-500 shadow-inner" style="width: {{ $wishlist->progress_percentage }}%"></div>
                                    </div>
                                    <div class="text-[#6B1D2F] text-xs mt-1 text-right font-medium">
                                        {{ $wishlist->reserved_count }} z {{ $wishlist->total_count }} dárků zabráno
                                    </div>
                                </div>

                                <!-- Items Preview -->
                                <div class="border-t border-gray-100 pt-4">
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Položky v seznamu</div>
                                    @if($wishlist->items->isEmpty())
                                        <p class="text-xs text-gray-400 italic">Zatím žádné přidané položky.</p>
                                    @else
                                        <div class="space-y-2">
                                            @foreach($wishlist->items->take(3) as $item)
                                                <div class="flex items-center justify-between text-xs bg-[#FAF7F2] p-2 rounded-xl border border-gray-100">
                                                    <div class="flex items-center gap-2 truncate">
                                                        <span>🎁</span>
                                                        <span class="font-medium text-gray-800 truncate">{{ $item->title }}</span>
                                                    </div>
                                                    @if($item->isReserved())
                                                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                                                            Rezervováno
                                                        </span>
                                                    @else
                                                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0">
                                                            Volné
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($wishlist->items->count() > 3)
                                                <p class="text-[11px] text-gray-400 text-center font-medium pt-1">
                                                    + dalších {{ $wishlist->items->count() - 3 }} dárků...
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Footer: Actions -->
                            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-2">
                                <button @click="copyLink('{{ route('public.wishlists.show', $wishlist->share_code) }}')" 
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 bg-amber-50 hover:bg-amber-100 text-[#6B1D2F] border border-[#D4AF37]/40 font-medium text-xs py-2 px-3 rounded-xl transition">
                                    <span>🔗</span>
                                    <span>Kopírovat odkaz</span>
                                </button>
                                
                                <a href="{{ route('wishlists.show', $wishlist) }}"
                                   class="flex-1 inline-flex items-center justify-center gap-1 bg-[#6B1D2F] hover:bg-[#541523] text-white font-medium text-xs py-2 px-3 rounded-xl transition">
                                    <span>👁️ Otevřít</span>
                                </a>

                                <form method="POST" action="{{ route('wishlists.destroy', $wishlist) }}" onsubmit="return confirm('Opravdu trvale smazat tento seznam přání a všechny jeho položky? Tuto akci nelze vrátit zpět.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Smazat seznam"
                                            class="inline-flex items-center justify-center bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-medium text-xs py-2 px-3 rounded-xl transition">
                                        <span>🗑️</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Sekce 2: Seznamy ve skupinách -->
        @if($groupWishlists->isNotEmpty())
            <div class="border-t border-[#F0E8DD] pt-10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-[#6B1D2F] flex items-center gap-2">
                            <span>👨‍👩‍👧‍👦</span> Seznamy ve skupinách
                        </h2>
                        <p class="text-gray-500 text-xs mt-0.5">Seznamy členů vašich skupin, kteří vám je zpřístupnili.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($groupWishlists as $groupWishlist)
                        <div class="bg-white rounded-3xl border border-[#F0E8DD] p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 font-medium px-2.5 py-0.5 rounded-full text-xs">
                                        <span>{{ $groupWishlist->occasion_icon }}</span>
                                        <span>{{ $groupWishlist->occasion_label }}</span>
                                    </span>
                                    <span class="text-xs text-gray-400">Autor: {{ $groupWishlist->user->name }}</span>
                                </div>

                                <h3 class="text-lg font-bold text-gray-900 font-serif mb-1">
                                    {{ $groupWishlist->title }}
                                </h3>
                                @if($groupWishlist->description)
                                    <p class="text-gray-500 text-xs line-clamp-2 mb-2">{{ $groupWishlist->description }}</p>
                                @endif

                                <div class="flex flex-wrap gap-1.5 mb-4">
                                    @foreach($groupWishlist->groups as $sharedGroup)
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-[#6B1D2F] border border-[#D4AF37]/40 px-2 py-0.5 rounded-full text-[10px] font-medium">
                                            👨‍👩‍👧‍👦 {{ $sharedGroup->name }}
                                        </span>
                                    @endforeach
                                </div>

                                <div class="mt-4">
                                    <div class="flex justify-between items-center text-xs text-gray-600 mb-1">
                                        <span>Rezervováno dárků</span>
                                        <span class="font-bold text-[#6B1D2F]">{{ $groupWishlist->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="bg-[#D4AF37] h-full rounded-full" style="width: {{ $groupWishlist->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <a href="{{ route('public.wishlists.show', $groupWishlist->share_code) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 bg-amber-100 hover:bg-amber-200 text-[#6B1D2F] font-semibold text-xs py-2.5 px-4 rounded-xl transition">
                                    <span>🎁 Zobrazit seznam a vybrat dárek</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Sekce 3: Veřejné wishlisty od jiných lidí -->
        @if($publicWishlists->isNotEmpty())
            <div class="border-t border-[#F0E8DD] pt-10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-[#6B1D2F] flex items-center gap-2">
                            <span>👥</span> Veřejné seznamy přání od ostatních
                        </h2>
                        <p class="text-gray-500 text-xs mt-0.5">Podívejte se, co si přejí ostatní uživatelé a vyberte jim dárek!</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($publicWishlists as $publicList)
                        <div class="bg-white rounded-3xl border border-[#F0E8DD] p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 font-medium px-2.5 py-0.5 rounded-full text-xs">
                                        <span>{{ $publicList->occasion_icon }}</span>
                                        <span>{{ $publicList->occasion_label }}</span>
                                    </span>
                                    <span class="text-xs text-gray-400">Autor: {{ $publicList->user->name }}</span>
                                </div>

                                <h3 class="text-lg font-bold text-gray-900 font-serif mb-1">
                                    {{ $publicList->title }}
                                </h3>
                                @if($publicList->description)
                                    <p class="text-gray-500 text-xs line-clamp-2 mb-4">{{ $publicList->description }}</p>
                                @endif

                                <div class="mt-4">
                                    <div class="flex justify-between items-center text-xs text-gray-600 mb-1">
                                        <span>Rezervováno dárků</span>
                                        <span class="font-bold text-[#6B1D2F]">{{ $publicList->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="bg-[#D4AF37] h-full rounded-full" style="width: {{ $publicList->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <a href="{{ route('public.wishlists.show', $publicList->share_code) }}" 
                                   class="w-full inline-flex items-center justify-center gap-2 bg-amber-100 hover:bg-amber-200 text-[#6B1D2F] font-semibold text-xs py-2.5 px-4 rounded-xl transition">
                                    <span>🎁 Zobrazit seznam a vybrat dárek</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    <!-- Modal pro vytvoření nového wishlistu -->
    <x-modal name="create-wishlist-modal" focusable>
        <form method="POST" action="{{ route('wishlists.store') }}" class="p-6">
            @csrf
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                    <span>✨</span> Vytvořit nový seznam přání
                </h2>
                <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            @if ($errors->createWishlist->any())
                <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs">
                    <div class="font-bold mb-1">⚠️ Formulář se nepodařilo uložit:</div>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->createWishlist->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="title" value="Název seznamu *" class="font-medium text-gray-700" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. Moje Vánoční přání 2026 nebo Narozeninový wishlist" value="{{ old('title') }}" required />
                    <x-input-error :messages="$errors->createWishlist->get('title')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="occasion" value="Příležitost *" class="font-medium text-gray-700" />
                    <select id="occasion" name="occasion" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F] text-gray-800">
                        <option value="christmas" @selected(old('occasion') === 'christmas')>🎄 Vánoce</option>
                        <option value="birthday" @selected(old('occasion') === 'birthday')>🎂 Narozeniny</option>
                        <option value="wedding" @selected(old('occasion') === 'wedding')>💍 Svatba</option>
                        <option value="anniversary" @selected(old('occasion') === 'anniversary')>🥂 Výročí</option>
                        <option value="other" @selected(old('occasion') === 'other')>🎁 Ostatní příležitost</option>
                    </select>
                    <x-input-error :messages="$errors->createWishlist->get('occasion')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="event_date" value="Datum události (volitelné)" class="font-medium text-gray-700" />
                    <x-text-input id="event_date" name="event_date" type="date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" value="{{ old('event_date') }}" />
                    <x-input-error :messages="$errors->createWishlist->get('event_date')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="description" value="Popis / Poznámka (volitelné)" class="font-medium text-gray-700" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="Napište krátký vzkaz pro lidi, kterým budete seznam sdílet...">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->createWishlist->get('description')" class="mt-1" />
                </div>

                <div class="flex items-start gap-2 pt-2">
                    <input type="checkbox" id="is_public" name="is_public" value="1" @checked(old('is_public', false)) class="rounded text-[#6B1D2F] focus:ring-[#6B1D2F] border-gray-300 mt-0.5" />
                    <label for="is_public" class="text-xs text-gray-600 font-medium">
                        Zobrazit seznam veřejně v přehledu ostatním uživatelům (bude sloužit jen jako inspirace)
                        <span class="block text-[11px] text-gray-400 font-normal mt-0.5">Pozor: u veřejného seznamu nejde rezervovat žádný dárek. Pokud chcete, aby lidé mohli dárky rezervovat, nechte tuto volbu nezaškrtnutou a odkaz na seznam jim pošlete přímo.</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                    Zrušit
                </x-secondary-button>
                <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                    Vytvořit wishlist
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    @if ($errors->createWishlist->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'create-wishlist-modal' }));
            });
        </script>
    @endif
</x-app-layout>
