<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4" x-data="{}">
            <div>
                <h1 class="text-3xl font-bold text-[#6B1D2F]">
                    👨‍👩‍👧‍👦 Skupiny
                </h1>
                <p class="text-gray-600 text-sm mt-1">
                    Vytvořte skupinu s rodinou nebo přáteli a sdílejte mezi sebou vybrané seznamy přání.
                </p>
            </div>

            <button @click="$dispatch('open-modal', 'create-group-modal')"
                class="inline-flex items-center gap-2 bg-[#6B1D2F] hover:bg-[#541523] text-white font-semibold px-6 py-3 rounded-2xl shadow-md transition transform active:scale-95">
                <span>➕</span>
                <span>Vytvořit skupinu</span>
            </button>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12" x-data="{}">

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

        @if ($pendingInvitations->isNotEmpty())
            <div>
                <h2 class="text-2xl font-bold text-[#6B1D2F] flex items-center gap-2 mb-6">
                    <span>✉️</span> Čekající pozvánky ({{ $pendingInvitations->count() }})
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($pendingInvitations as $invitation)
                        <div class="bg-white rounded-3xl border border-amber-300 shadow-sm p-6 space-y-3">
                            <div class="font-bold text-gray-900">{{ $invitation->name }}</div>
                            <p class="text-xs text-gray-500">Zve vás: {{ $invitation->owner->name }}</p>
                            <div class="flex gap-2 pt-2">
                                <form method="POST" action="{{ route('groups.invitations.accept', $invitation) }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-[#6B1D2F] hover:bg-[#541523] text-white font-semibold text-xs py-2.5 px-3 rounded-xl transition">
                                        ✅ Přijmout
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('groups.invitations.decline', $invitation) }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs py-2.5 px-3 rounded-xl transition">
                                        ❌ Odmítnout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <h2 class="text-2xl font-bold text-[#6B1D2F] flex items-center gap-2 mb-6">
                <span>👨‍👩‍👧‍👦</span> Moje skupiny ({{ $myGroups->count() }})
            </h2>

            @if ($myGroups->isEmpty())
                <div class="bg-white rounded-3xl p-12 text-center border border-[#F0E8DD] shadow-sm">
                    <div class="text-6xl mb-4">👨‍👩‍👧‍👦</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Zatím nejste v žádné skupině</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">Založte skupinu pro rodinu nebo přátele a domluvte se, kdo uvidí čí seznamy přání.</p>
                    <button @click="$dispatch('open-modal', 'create-group-modal')" class="bg-[#6B1D2F] text-white px-6 py-3 rounded-2xl font-medium hover:bg-[#541523] transition">
                        Vytvořit první skupinu
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($myGroups as $group)
                        <a href="{{ route('groups.show', $group) }}" class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm hover:shadow-md transition p-6 flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900 font-serif">{{ $group->name }}</h3>
                                @if($group->owner_id === auth()->id())
                                    <span class="bg-[#D4AF37] text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Zakladatel</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500">{{ $group->accepted_members_count }} {{ \Illuminate\Support\Str::plural('člen', $group->accepted_members_count) }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal pro vytvoření skupiny -->
    <x-modal name="create-group-modal" focusable>
        <form method="POST" action="{{ route('groups.store') }}" class="p-6">
            @csrf
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <h2 class="text-xl font-bold text-[#6B1D2F] flex items-center gap-2">
                    <span>👨‍👩‍👧‍👦</span> Vytvořit novou skupinu
                </h2>
                <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            @if ($errors->createGroup->any())
                <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs">
                    <div class="font-bold mb-1">⚠️ Formulář se nepodařilo uložit:</div>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->createGroup->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="name" value="Název skupiny *" class="font-medium text-gray-700" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" placeholder="např. Naše rodina nebo Kamarádi z práce" value="{{ old('name') }}" required />
                    <x-input-error :messages="$errors->createGroup->get('name')" class="mt-1" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <x-secondary-button @click="$dispatch('close')" class="rounded-xl">
                    Zrušit
                </x-secondary-button>
                <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                    Vytvořit skupinu
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    @if ($errors->createGroup->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'create-group-modal' }));
            });
        </script>
    @endif
</x-app-layout>
