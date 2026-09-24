<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4" x-data="{}">
            <div>
                <a href="{{ route('groups.index') }}" class="text-xs text-gray-500 hover:text-[#6B1D2F] transition">← Zpět na skupiny</a>
                <h1 class="text-3xl font-bold text-[#6B1D2F] mt-1">
                    👨‍👩‍👧‍👦 {{ $group->name }}
                </h1>
                <p class="text-gray-600 text-sm mt-1">
                    Zakladatel: {{ $group->owner->name }} · {{ $group->acceptedMembers->count() }} {{ \Illuminate\Support\Str::plural('člen', $group->acceptedMembers->count()) }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if($isOwner)
                    <button @click="$dispatch('open-modal', 'invite-member-modal')"
                        class="inline-flex items-center gap-2 bg-[#6B1D2F] hover:bg-[#541523] text-white font-semibold px-4 py-2.5 rounded-2xl shadow-md transition text-sm">
                        <span>✉️</span>
                        <span>Pozvat člena</span>
                    </button>
                    <form method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('Opravdu trvale smazat tuto skupinu? Všichni členové o ni přijdou.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 font-semibold px-4 py-2.5 rounded-2xl text-sm transition">
                            🗑️ Smazat skupinu
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('groups.leave', $group) }}" onsubmit="return confirm('Opravdu opustit tuto skupinu?');">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2.5 rounded-2xl text-sm transition">
                            🚪 Opustit skupinu
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="text-xl">✨</span>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="text-xl">⚠️</span>
                <div class="text-sm">{{ session('error') }}</div>
            </div>
        @endif

        <!-- Členové -->
        <div>
            <h2 class="text-xl font-bold text-[#6B1D2F] mb-4 flex items-center gap-2">
                <span>👥</span> Členové
            </h2>
            <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm divide-y divide-gray-100">
                @foreach($group->acceptedMembers as $member)
                    <div class="flex items-center justify-between px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-amber-100 text-[#6B1D2F] flex items-center justify-center font-bold text-sm">
                                {{ mb_substr($member->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ $member->name }}</div>
                                <div class="text-xs text-gray-400">{{ $member->email }}</div>
                            </div>
                            @if($member->id === $group->owner_id)
                                <span class="bg-[#D4AF37] text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Zakladatel</span>
                            @endif
                        </div>
                        @if($isOwner && $member->id !== $group->owner_id)
                            <form method="POST" action="{{ route('groups.members.remove', [$group, $member]) }}" onsubmit="return confirm('Odebrat tohoto člena ze skupiny?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium transition">Odebrat</button>
                            </form>
                        @endif
                    </div>
                @endforeach

                @foreach($group->pendingMembers as $member)
                    <div class="flex items-center justify-between px-6 py-3 bg-amber-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold text-sm">
                                {{ mb_substr($member->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-600">{{ $member->name }}</div>
                                <div class="text-xs text-gray-400">{{ $member->email }}</div>
                            </div>
                            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">Čeká na přijetí</span>
                        </div>
                        @if($isOwner)
                            <form method="POST" action="{{ route('groups.members.remove', [$group, $member]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium transition">Zrušit pozvánku</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Sdílené seznamy ostatních členů -->
        <div>
            <h2 class="text-xl font-bold text-[#6B1D2F] mb-1 flex items-center gap-2">
                <span>🎁</span> Seznamy přání ve skupině
            </h2>
            <p class="text-xs text-gray-500 mb-6">
                Zobrazují se jen seznamy, které jejich majitelé zpřístupnili této konkrétní skupině.
            </p>

            @if($groupWishlists->isEmpty())
                <div class="bg-white rounded-3xl p-12 text-center border border-[#F0E8DD] shadow-sm">
                    <div class="text-5xl mb-3">📦</div>
                    <h3 class="text-lg font-bold text-gray-800">Zatím tu nejsou žádné sdílené seznamy</h3>
                    <p class="text-gray-500 text-xs mt-1">Až někdo ze skupiny zpřístupní svůj seznam, objeví se tady. Svůj vlastní seznam zpřístupníte na jeho stránce.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($groupWishlists as $wishlist)
                        @php($isMyWishlist = $wishlist->user_id === auth()->id())
                        <div class="bg-white rounded-3xl border {{ $isMyWishlist ? 'border-[#D4AF37]' : 'border-[#F0E8DD]' }} shadow-sm hover:shadow-md transition p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 font-medium px-2.5 py-0.5 rounded-full text-xs">
                                        <span>{{ $wishlist->occasion_icon }}</span>
                                        <span>{{ $wishlist->occasion_label }}</span>
                                    </span>
                                    @if($isMyWishlist)
                                        <span class="bg-[#D4AF37] text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Váš seznam</span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ $wishlist->user->name }}</span>
                                    @endif
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 font-serif mb-1">{{ $wishlist->title }}</h3>
                                @if($wishlist->is_public)
                                    <div class="mt-4 bg-sky-50 border border-sky-200 text-sky-900 text-[11px] rounded-xl px-3 py-2 flex items-center gap-2">
                                        <span>💡</span>
                                        <span>Veřejný seznam — jen inspirace, bez rezervací</span>
                                    </div>
                                @else
                                    <div class="mt-4">
                                        <div class="flex justify-between items-center text-xs text-gray-600 mb-1">
                                            <span>Rezervováno dárků</span>
                                            <span class="font-bold text-[#6B1D2F]">{{ $wishlist->progress_percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                            <div class="bg-[#D4AF37] h-full rounded-full" style="width: {{ $wishlist->progress_percentage }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                @if($isMyWishlist)
                                    <a href="{{ route('wishlists.show', $wishlist) }}"
                                       class="w-full inline-flex items-center justify-center gap-2 bg-amber-100 hover:bg-amber-200 text-[#6B1D2F] font-semibold text-xs py-2.5 px-4 rounded-xl transition">
                                        <span>✏️ Spravovat seznam</span>
                                    </a>
                                @else
                                    <a href="{{ route('public.wishlists.show', $wishlist->share_code) }}"
                                       class="w-full inline-flex items-center justify-center gap-2 bg-amber-100 hover:bg-amber-200 text-[#6B1D2F] font-semibold text-xs py-2.5 px-4 rounded-xl transition">
                                        <span>🎁 Zobrazit seznam a vybrat dárek</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal pro pozvání člena -->
    @if($isOwner)
        <x-modal name="invite-member-modal" focusable>
            <form method="POST" action="{{ route('groups.invite', $group) }}" class="p-6">
                @csrf
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                        <span>✉️</span> Pozvat člena do skupiny
                    </h2>
                    <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                @if ($errors->inviteMember->any())
                    <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs">
                        <div class="font-bold mb-1">⚠️ Pozvánku se nepodařilo odeslat:</div>
                        <ul class="list-disc pl-4 space-y-0.5">
                            @foreach ($errors->inviteMember->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6 space-y-4">
                    <div>
                        <x-input-label for="invite_email" value="E-mail uživatele *" class="font-medium text-gray-700" />
                        <x-text-input id="invite_email" name="email" type="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. teta.alena@example.com" value="{{ old('email') }}" required />
                        <p class="text-[11px] text-gray-400 mt-1">Uživatel musí mít u nás už založený účet.</p>
                        <x-input-error :messages="$errors->inviteMember->get('email')" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                        Zrušit
                    </x-secondary-button>
                    <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                        Odeslat pozvánku
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

        @if ($errors->inviteMember->any())
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'invite-member-modal' }));
                });
            </script>
        @endif
    @endif
</x-app-layout>
