@php
    $giftPlaceholders = [
        ['file' => 'gift.svg', 'label' => 'Obecný dárek'],
        ['file' => 'book.svg', 'label' => 'Kniha'],
        ['file' => 'electronics.svg', 'label' => 'Elektronika'],
        ['file' => 'clothing.svg', 'label' => 'Oblečení'],
        ['file' => 'toy.svg', 'label' => 'Hračka'],
        ['file' => 'jewelry.svg', 'label' => 'Šperky'],
        ['file' => 'experience.svg', 'label' => 'Zážitek'],
        ['file' => 'home.svg', 'label' => 'Domácnost'],
        ['file' => 'sports.svg', 'label' => 'Sport'],
        ['file' => 'beauty.svg', 'label' => 'Kosmetika'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="w-10 h-10 rounded-2xl bg-white border border-[#F0E8DD] flex items-center justify-center text-gray-600 hover:bg-gray-50 transition shadow-sm">
                    ←
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="bg-[#D4AF37] text-gray-900 font-bold px-3 py-0.5 rounded-full text-xs">
                            {{ $wishlist->occasion_icon }} {{ $wishlist->occasion_label }}
                        </span>
                        @if($wishlist->event_date)
                            <span class="text-xs text-gray-500">📅 {{ $wishlist->event_date->format('d. m. Y') }}</span>
                        @endif
                    </div>
                    <h1 class="text-3xl font-bold text-[#6B1D2F] font-serif mt-1">
                        {{ $wishlist->title }}
                    </h1>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 w-full md:flex md:w-auto md:items-center md:gap-3" x-data="{ copyLink(url) { navigator.clipboard.writeText(url); alert('Odkaz pro sdílení s přáteli byl zkopírován!'); } }">
                <button @click="copyLink('{{ route('public.wishlists.show', $wishlist->share_code) }}')"
                        class="inline-flex items-center justify-center gap-1.5 bg-amber-50 hover:bg-amber-100 border border-[#D4AF37] text-[#6B1D2F] font-semibold px-3 py-2.5 rounded-2xl text-xs transition shadow-sm text-center">
                    <span>🔗</span>
                    <span class="hidden sm:inline">Kopírovat odkaz pro sdílení</span>
                    <span class="sm:hidden">Kopírovat odkaz</span>
                </button>

                <button @click="$dispatch('open-modal', 'qr-code-modal')"
                        class="inline-flex items-center justify-center gap-1.5 bg-amber-50 hover:bg-amber-100 border border-[#D4AF37] text-[#6B1D2F] font-semibold px-3 py-2.5 rounded-2xl text-xs transition shadow-sm">
                    <span>📱</span>
                    <span>QR kód</span>
                </button>

                <button @click="$dispatch('open-modal', 'edit-wishlist-modal')"
                        class="inline-flex items-center justify-center gap-1.5 bg-amber-50 hover:bg-amber-100 border border-[#D4AF37] text-[#6B1D2F] font-semibold px-3 py-2.5 rounded-2xl text-xs transition shadow-sm">
                    <span>✏️</span>
                    <span>Upravit seznam</span>
                </button>

                <button @click="$dispatch('open-modal', 'add-item-modal')"
                        class="inline-flex items-center justify-center gap-1.5 bg-[#6B1D2F] hover:bg-[#541523] text-white font-semibold px-3 py-2.5 rounded-2xl text-xs shadow-md transition">
                    <span>➕</span>
                    <span>Přidat dárek</span>
                </button>

                @if(auth()->user()->is_admin)
                    @if($wishlist->isSuspended())
                        <form method="POST" action="{{ route('admin.wishlists.unsuspend', $wishlist) }}">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-800 font-semibold px-3 py-2.5 rounded-2xl text-xs transition">
                                <span>✅</span>
                                <span>Obnovit zveřejnění</span>
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.wishlists.suspend', $wishlist) }}" onsubmit="return confirm('Opravdu pozastavit veřejný přístup k tomuto seznamu?');">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 font-semibold px-3 py-2.5 rounded-2xl text-xs transition">
                                <span>🚫</span>
                                <span>Pozastavit</span>
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="{ editItem: { id: null, title: '', url: '', price: null, currency: 'Kč', description: '', priority: 'medium', is_group_gift: false } }">

        @if($wishlist->isSuspended())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-6 py-4 rounded-2xl flex items-center gap-3 shadow-sm text-sm">
                <span class="text-xl">🚫</span>
                <div>Tento seznam pozastavil administrátor – veřejný sdílený odkaz teď nikomu nefunguje.</div>
            </div>
        @endif

        <!-- Přehledový banner wishlistu -->
        <div class="bg-white rounded-3xl p-6 md:p-8 border border-[#F0E8DD] shadow-sm flex flex-col md:flex-row items-stretch justify-between gap-6">
            <div class="flex-1 space-y-3">
                <h2 class="text-lg font-bold text-[#6B1D2F]">O tomto seznamu</h2>
                <p class="text-gray-600 text-sm leading-relaxed">
                    {{ $wishlist->description ?? 'Žádný doplňující popis nebyl zadán.' }}
                </p>

                <!-- Info o anonymitě -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-amber-900">
                    <span class="text-lg">🕵️‍♂️</span>
                    <div>
                        <span class="font-bold">Režim překvapení je aktivní:</span> Pokud někdo z vašich přátel vybere a koupí dárek z tohoto seznamu, uvidíte pouze příznak <span class="font-bold">"Rezervováno"</span>. Jméno dárce před vámi zůstává skryto až do rozbalení!
                    </div>
                </div>
            </div>

            <!-- Ukazatel pokroku -->
            <div class="w-full md:w-80 bg-[#FAF7F2] p-6 rounded-2xl border border-[#F0E8DD] flex flex-col justify-center">
                <div class="flex justify-between items-center text-xs font-bold text-[#6B1D2F] mb-2">
                    <span>Splnění seznamu</span>
                    <span>{{ $wishlist->progress_percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden p-0.5">
                    <div class="bg-[#D4AF37] h-full rounded-full transition-all duration-500 shadow-inner" style="width: {{ $wishlist->progress_percentage }}%"></div>
                </div>
                <div class="text-xs text-gray-500 text-center mt-3 font-medium">
                    {{ $wishlist->reserved_count }} z {{ $wishlist->total_count }} dárků označených jako vybrané
                </div>
            </div>
        </div>

        <!-- Mřížka dárků -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-[#6B1D2F] flex items-center gap-2">
                    <span>🎁</span> Položky v seznamu ({{ $wishlist->items->count() }})
                </h2>
            </div>

            @if($wishlist->items->isEmpty())
                <div class="bg-white rounded-3xl p-12 text-center border border-[#F0E8DD] shadow-sm">
                    <div class="text-5xl mb-4">🧸</div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">V tomto seznamu zatím nemáte žádné dárky</h3>
                    <p class="text-gray-500 text-xs mb-6">Přidejte například knihu, elektroniku, oblečení nebo zážitek a přidejte odkaz, kde ho lze koupit.</p>
                    <button @click="$dispatch('open-modal', 'add-item-modal')" class="bg-[#6B1D2F] text-white px-6 py-2.5 rounded-2xl text-xs font-medium hover:bg-[#541523] transition">
                        ➕ Přidat první dárek
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($wishlist->items as $item)
                        @php
                            $editItemPayload = [
                                'id' => $item->id,
                                'title' => $item->title,
                                'url' => $item->url,
                                'price' => $item->price !== null ? (float) $item->price : null,
                                'currency' => $item->currency,
                                'description' => $item->description,
                                'priority' => $item->priority,
                                'is_group_gift' => $item->is_group_gift,
                            ];
                        @endphp
                        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition">

                            <!-- Obrázek dárku -->
                            <div class="relative h-48 bg-gray-100 flex items-center justify-center overflow-hidden border-b border-gray-100">
                                @if($item->image_path)
                                    <img src="{{ $item->resolved_image_url }}"
                                         alt="{{ $item->title }}" class="w-full h-full object-cover" />
                                @else
                                    <div class="text-center text-gray-400 p-4">
                                        <span class="text-4xl block mb-1">🎁</span>
                                        <span class="text-xs">Bez obrázku</span>
                                    </div>
                                @endif

                                <!-- Badge stavu -->
                                <div class="absolute top-3 right-3">
                                    @if($item->isReserved())
                                        <span class="bg-amber-500 text-white font-bold text-xs px-3 py-1 rounded-full shadow-md flex items-center gap-1">
                                            <span>🔒</span> Rezervováno
                                        </span>
                                    @else
                                        <span class="bg-emerald-500 text-white font-bold text-xs px-3 py-1 rounded-full shadow-md flex items-center gap-1">
                                            <span>✨</span> Volné k výběru
                                        </span>
                                    @endif
                                </div>

                                @if($item->is_group_gift)
                                    <div class="absolute top-3 left-3">
                                        <span class="bg-white/90 text-[#6B1D2F] font-bold text-[10px] uppercase px-2.5 py-1 rounded-full shadow-md">
                                            👥 Skupinový dárek
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Detail dárku -->
                            <div class="p-6 space-y-3 flex-grow">
                                <h3 class="text-lg font-bold text-gray-900 font-serif leading-snug">
                                    {{ $item->title }}
                                </h3>

                                @if($item->formatted_price)
                                    <div class="text-xl font-extrabold text-[#6B1D2F]">
                                        {{ $item->formatted_price }}
                                    </div>
                                @endif

                                @if($item->description)
                                    <p class="text-gray-600 text-xs leading-relaxed line-clamp-3">
                                        {{ $item->description }}
                                    </p>
                                @endif

                                @if($item->url)
                                    <div class="pt-2">
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1 text-xs text-amber-700 hover:text-amber-900 font-semibold underline truncate max-w-full">
                                            <span>🌐 Zobrazit v obchodě</span> ↗
                                        </a>
                                    </div>
                                @endif

                                @if($item->is_group_gift && $item->price)
                                    <div class="pt-2">
                                        <div class="flex justify-between items-center text-[11px] font-bold text-[#6B1D2F] mb-1">
                                            <span>Vybráno od přátel</span>
                                            <span>{{ $item->contribution_percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="bg-[#D4AF37] h-full rounded-full transition-all duration-500" style="width: {{ $item->contribution_percentage }}%"></div>
                                        </div>
                                        <div class="text-[10px] text-gray-400 mt-1">
                                            Kdo přispěl, zůstává tajemstvím — vidíte jen vybranou částku.
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Akce vlastníka -->
                            <div class="bg-[#FAF7F2] px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">
                                    Priorita: <span class="font-bold text-gray-600 uppercase">{{ $item->priority }}</span>
                                </span>

                                <div class="flex items-center gap-1">
                                    @if($item->isReserved())
                                        <form method="POST" action="{{ route('gift-items.clear-reservation', $item) }}" onsubmit="return confirm('Opravdu ručně uvolnit tuto rezervaci? Dárek bude znovu volný k výběru.');">
                                            @csrf
                                            <button type="submit" class="text-xs text-amber-700 hover:text-amber-900 font-medium px-2 py-1 rounded-lg hover:bg-amber-100 transition">
                                                🔓 Uvolnit rezervaci
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('gift-items.mark-reserved', $item) }}" onsubmit="return confirm('Označit tento dárek jako rezervovaný? Zmizí z nabídky k výběru pro ostatní.');">
                                            @csrf
                                            <button type="submit" class="text-xs text-amber-700 hover:text-amber-900 font-medium px-2 py-1 rounded-lg hover:bg-amber-100 transition">
                                                🔒 Označit jako rezervováno
                                            </button>
                                        </form>
                                    @endif

                                    <button type="button"
                                            @click="editItem = {{ json_encode($editItemPayload) }}; $dispatch('open-modal', 'edit-item-modal')"
                                            class="text-xs text-[#6B1D2F] hover:text-[#541523] font-medium px-2 py-1 rounded-lg hover:bg-amber-100 transition">
                                        ✏️ Upravit
                                    </button>

                                    <form method="POST" action="{{ route('gift-items.destroy', $item) }}" onsubmit="return confirm('Opravdu chcete smazat tento dárek?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium px-2 py-1 rounded-lg hover:bg-rose-50 transition">
                                            🗑️ Smazat
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Modal pro úpravu položky dárku -->
        <x-modal name="edit-item-modal" focusable>
            <form method="POST" :action="`/gift-items/${editItem.id}`" enctype="multipart/form-data" class="p-6">
                @csrf
                @method('PUT')
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                        <span>✏️</span> Upravit dárek
                    </h2>
                    <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-6 space-y-4">
                    <div>
                        <x-input-label for="edit_item_title" value="Název dárku *" class="font-medium text-gray-700" />
                        <x-text-input id="edit_item_title" name="title" type="text" x-model="editItem.title" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" required />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="edit_item_price" value="Předpokládaná cena" class="font-medium text-gray-700" />
                            <div class="flex gap-2 mt-1">
                                <x-text-input id="edit_item_price" name="price" type="number" step="1" x-model="editItem.price" class="block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" />
                                <select name="currency" x-model="editItem.currency" class="rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]">
                                    <option value="Kč">Kč</option>
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="edit_item_priority" value="Priorita" class="font-medium text-gray-700" />
                            <select id="edit_item_priority" name="priority" x-model="editItem.priority" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]">
                                <option value="medium">Medium (Standardní)</option>
                                <option value="high">🔥 High (Moc si přeji)</option>
                                <option value="low">Low (Jen tak mimochodem)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="edit_item_url" value="Odkaz na e-shop (kde lze koupit)" class="font-medium text-gray-700" />
                        <x-text-input id="edit_item_url" name="url" type="url" x-model="editItem.url" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" />
                    </div>

                    <div>
                        <x-input-label for="edit_item_image" value="Nahrát novou fotku/obrázek (nahradí stávající)" class="font-medium text-gray-700" />
                        <input type="file" id="edit_item_image" name="image" accept="image/*" class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-[#6B1D2F] hover:file:bg-amber-200 cursor-pointer" />
                    </div>

                    <div>
                        <x-input-label for="edit_item_image_url" value="Nebo vložit odkaz na obrázek (URL)" class="font-medium text-gray-700" />
                        <x-text-input id="edit_item_image_url" name="image_url" type="url" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="https://..." />
                    </div>

                    <div>
                        <x-input-label value="Nebo vyberte ikonu dárku" class="font-medium text-gray-700" />
                        <div class="mt-2 grid grid-cols-5 gap-2">
                            @foreach($giftPlaceholders as $placeholder)
                                <button type="button"
                                        @click="document.getElementById('edit_item_image_url').value = '{{ asset('images/placeholders/'.$placeholder['file']) }}'"
                                        class="flex flex-col items-center gap-1 p-1.5 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:bg-amber-50 transition">
                                    <img src="{{ asset('images/placeholders/'.$placeholder['file']) }}" alt="{{ $placeholder['label'] }}" class="w-10 h-10 rounded-lg" />
                                    <span class="text-[9px] text-gray-500 leading-tight text-center">{{ $placeholder['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <x-input-label for="edit_item_description" value="Doplňující informace (barva, velikost, verze...)" class="font-medium text-gray-700" />
                        <textarea id="edit_item_description" name="description" rows="3" x-model="editItem.description" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]"></textarea>
                    </div>

                    <div class="flex items-start gap-2 pt-2 bg-amber-50 border border-amber-200 rounded-xl p-3">
                        <input type="checkbox" id="edit_item_is_group_gift" name="is_group_gift" value="1" x-model="editItem.is_group_gift" class="mt-0.5 rounded text-[#6B1D2F] focus:ring-[#6B1D2F] border-gray-300" />
                        <label for="edit_item_is_group_gift" class="text-xs text-gray-700">
                            <span class="font-bold">👥 Skupinový dárek</span> — přátelé se mohou složit na cenu společně, místo aby ho rezervoval jen jeden člověk. Vyžaduje vyplněnou cenu.
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                        Zrušit
                    </x-secondary-button>
                    <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                        Uložit změny
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

    </div>

    <!-- Modal pro přidání nového dárku -->
    <x-modal name="add-item-modal" focusable>
        <form method="POST" action="{{ route('gift-items.store', $wishlist) }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                    <span>🎁</span> Přidat nový dárek do seznamu
                </h2>
                <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            @if ($errors->addItem->any())
                <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs">
                    <div class="font-bold mb-1">⚠️ Dárek se nepodařilo uložit:</div>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->addItem->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="item_title" value="Název dárku *" class="font-medium text-gray-700" />
                    <x-text-input id="item_title" name="title" type="text" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. Kniha Stopařův průvodce Po Galaxii nebo Bezdrátová sluchátka" value="{{ old('title') }}" required />
                    <x-input-error :messages="$errors->addItem->get('title')" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="price" value="Předpokládaná cena" class="font-medium text-gray-700" />
                        <div class="flex gap-2 mt-1">
                            <x-text-input id="price" name="price" type="number" step="1" class="block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. 499" value="{{ old('price') }}" />
                            <select name="currency" class="rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]">
                                <option value="Kč" @selected(old('currency', 'Kč') === 'Kč')>Kč</option>
                                <option value="EUR" @selected(old('currency') === 'EUR')>EUR</option>
                                <option value="USD" @selected(old('currency') === 'USD')>USD</option>
                            </select>
                        </div>
                        <x-input-error :messages="$errors->addItem->get('price')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="priority" value="Priorita" class="font-medium text-gray-700" />
                        <select id="priority" name="priority" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]">
                            <option value="medium" @selected(old('priority', 'medium') === 'medium')>Medium (Standardní)</option>
                            <option value="high" @selected(old('priority') === 'high')>🔥 High (Moc si přeji)</option>
                            <option value="low" @selected(old('priority') === 'low')>Low (Jen tak mimochodem)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <x-input-label for="url" value="Odkaz na e-shop (kde lze koupit)" class="font-medium text-gray-700" />
                    <x-text-input id="url" name="url" type="url" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="https://www.alza.cz/kod-zbozi..." value="{{ old('url') }}" />
                    <x-input-error :messages="$errors->addItem->get('url')" class="mt-1" />
                    <p class="text-[11px] text-gray-400 mt-1">Musí to být celá adresa včetně "https://" (např. https://www.alza.cz/produkt, ne jen alza.cz).</p>
                </div>

                <div>
                    <x-input-label for="image" value="Nahrát fotku/obrázek dárku" class="font-medium text-gray-700" />
                    <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-[#6B1D2F] hover:file:bg-amber-200 cursor-pointer" />
                    <x-input-error :messages="$errors->addItem->get('image')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="image_url" value="Nebo vložit odkaz na obrázek (URL)" class="font-medium text-gray-700" />
                    <x-text-input id="image_url" name="image_url" type="url" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="https://..." value="{{ old('image_url') }}" />
                    <x-input-error :messages="$errors->addItem->get('image_url')" class="mt-1" />
                </div>

                <div>
                    <x-input-label value="Nebo vyberte ikonu dárku" class="font-medium text-gray-700" />
                    <div class="mt-2 grid grid-cols-5 gap-2">
                        @foreach($giftPlaceholders as $placeholder)
                            <button type="button"
                                    @click="document.getElementById('image_url').value = '{{ asset('images/placeholders/'.$placeholder['file']) }}'"
                                    class="flex flex-col items-center gap-1 p-1.5 rounded-xl border border-gray-200 hover:border-[#D4AF37] hover:bg-amber-50 transition">
                                <img src="{{ asset('images/placeholders/'.$placeholder['file']) }}" alt="{{ $placeholder['label'] }}" class="w-10 h-10 rounded-lg" />
                                <span class="text-[9px] text-gray-500 leading-tight text-center">{{ $placeholder['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <x-input-label for="item_description" value="Doplňující informace (barva, velikost, verze...)" class="font-medium text-gray-700" />
                    <textarea id="item_description" name="description" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="Např. Velikost M, tmavě modrá barva, pevná vazba knihy...">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->addItem->get('description')" class="mt-1" />
                </div>

                <div class="flex items-start gap-2 pt-2 bg-amber-50 border border-amber-200 rounded-xl p-3">
                    <input type="checkbox" id="is_group_gift" name="is_group_gift" value="1" @checked(old('is_group_gift')) class="mt-0.5 rounded text-[#6B1D2F] focus:ring-[#6B1D2F] border-gray-300" />
                    <label for="is_group_gift" class="text-xs text-gray-700">
                        <span class="font-bold">👥 Skupinový dárek</span> — přátelé se mohou složit na cenu společně, místo aby ho rezervoval jen jeden člověk. Vyžaduje vyplněnou cenu.
                    </label>
                </div>
                <x-input-error :messages="$errors->addItem->get('is_group_gift')" class="mt-1" />
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                    Zrušit
                </x-secondary-button>
                <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                    Uložit dárek do seznamu
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    @if ($errors->addItem->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'add-item-modal' }));
            });
        </script>
    @endif

    <!-- Modal pro úpravu seznamu -->
    <x-modal name="edit-wishlist-modal" focusable>
        <form method="POST" action="{{ route('wishlists.update', $wishlist) }}" class="p-6">
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                    <span>✏️</span> Upravit seznam přání
                </h2>
                <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            @if ($errors->updateWishlist->any())
                <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs">
                    <div class="font-bold mb-1">⚠️ Změny se nepodařilo uložit:</div>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->updateWishlist->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="edit_title" value="Název seznamu *" class="font-medium text-gray-700" />
                    <x-text-input id="edit_title" name="title" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" value="{{ old('title', $wishlist->title) }}" required />
                    <x-input-error :messages="$errors->updateWishlist->get('title')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="edit_occasion" value="Příležitost *" class="font-medium text-gray-700" />
                    <select id="edit_occasion" name="occasion" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F] text-gray-800">
                        <option value="christmas" @selected(old('occasion', $wishlist->occasion) === 'christmas')>🎄 Vánoce</option>
                        <option value="birthday" @selected(old('occasion', $wishlist->occasion) === 'birthday')>🎂 Narozeniny</option>
                        <option value="wedding" @selected(old('occasion', $wishlist->occasion) === 'wedding')>💍 Svatba</option>
                        <option value="anniversary" @selected(old('occasion', $wishlist->occasion) === 'anniversary')>🥂 Výročí</option>
                        <option value="other" @selected(old('occasion', $wishlist->occasion) === 'other')>🎁 Ostatní příležitost</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="edit_event_date" value="Datum události (volitelné)" class="font-medium text-gray-700" />
                    <x-text-input id="edit_event_date" name="event_date" type="date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" value="{{ old('event_date', $wishlist->event_date?->format('Y-m-d')) }}" />
                    <x-input-error :messages="$errors->updateWishlist->get('event_date')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="edit_description" value="Popis / Poznámka (volitelné)" class="font-medium text-gray-700" />
                    <textarea id="edit_description" name="description" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]">{{ old('description', $wishlist->description) }}</textarea>
                    <x-input-error :messages="$errors->updateWishlist->get('description')" class="mt-1" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="edit_is_public" name="is_public" value="1" @checked(old('is_public', $wishlist->is_public)) class="rounded text-[#6B1D2F] focus:ring-[#6B1D2F] border-gray-300" />
                    <label for="edit_is_public" class="text-xs text-gray-600 font-medium">
                        Povolit zobrazení v přehledu veřejných wishlistů (ostatní budou moci vidět přání)
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                    Zrušit
                </x-secondary-button>
                <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                    Uložit změny
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    @if ($errors->updateWishlist->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'edit-wishlist-modal' }));
            });
        </script>
    @endif

    <!-- Modal s QR kódem pro sdílení -->
    <x-modal name="qr-code-modal" focusable>
        <div class="p-6 text-center">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 text-left">
                <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                    <span>📱</span> QR kód pro sdílení
                </h2>
                <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <p class="text-xs text-gray-500 mt-4 mb-4">
                Naskenováním se otevře sdílený seznam přímo na mobilu — hodí se třeba do tištěné pozvánky.
            </p>

            <div class="bg-white p-4 rounded-2xl border border-[#F0E8DD] inline-block">
                <img src="{{ route('wishlists.qr-code', $wishlist) }}" alt="QR kód na seznam {{ $wishlist->title }}" class="w-56 h-56" />
            </div>

            <div class="mt-4">
                <a href="{{ route('wishlists.qr-code', $wishlist) }}" download="giftis-{{ $wishlist->share_code }}.svg"
                   class="inline-flex items-center gap-2 bg-amber-50 hover:bg-amber-100 border border-[#D4AF37] text-[#6B1D2F] font-semibold px-4 py-2.5 rounded-2xl text-xs transition">
                    <span>⬇️</span> Stáhnout QR kód (SVG)
                </a>
            </div>
        </div>
    </x-modal>
</x-app-layout>
