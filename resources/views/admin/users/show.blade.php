<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}" class="w-10 h-10 rounded-2xl bg-white border border-[#F0E8DD] flex items-center justify-center text-gray-600 hover:bg-gray-50 transition shadow-sm">
                ←
            </a>
            <h1 class="text-3xl font-bold text-[#6B1D2F] font-serif">{{ $user->name }}</h1>
            @if($user->is_admin)
                <span class="bg-[#D4AF37] text-gray-900 text-xs font-bold px-3 py-1 rounded-full">ADMIN</span>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        @if (session('generated_password'))
            <div class="bg-amber-50 border border-amber-300 text-amber-900 px-6 py-4 rounded-2xl shadow-sm text-sm space-y-2">
                <div class="font-bold">🔑 Nové heslo (zobrazí se jen teď, nikam se neukládá):</div>
                <div class="font-mono text-lg bg-white border border-amber-200 rounded-xl px-4 py-2 inline-block select-all">{{ session('generated_password') }}</div>
                <p class="text-xs">Předejte ho uživateli bezpečnou cestou (osobně, telefonem). Všechny jeho aktivní přihlášení byly odhlášeny.</p>
            </div>
        @endif

        <!-- Úprava uživatele -->
        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm p-6 md:p-8">
            <h2 class="text-lg font-bold text-[#6B1D2F] mb-4">Údaje uživatele</h2>

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Jméno" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" value="{{ old('name', $user->name) }}" required />
                    </div>
                    <div>
                        <x-input-label for="email" value="E-mail" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F]" value="{{ old('email', $user->email) }}" required />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_admin" name="is_admin" value="1" @checked($user->is_admin) class="rounded text-[#6B1D2F] focus:ring-[#6B1D2F] border-gray-300" />
                    <label for="is_admin" class="text-sm text-gray-700">Administrátorská práva</label>
                </div>

                <div class="text-xs text-gray-400">
                    Registrace: {{ $user->created_at->format('d. m. Y H:i') }}
                    · E-mail {{ $user->email_verified_at ? 'ověřen ' . $user->email_verified_at->format('d. m. Y') : 'neověřen' }}
                </div>

                <div class="flex justify-end pt-2">
                    <x-primary-button class="bg-[#6B1D2F] hover:bg-[#541523] text-white rounded-xl border-none">
                        Uložit změny
                    </x-primary-button>
                </div>
            </form>
        </div>

        <!-- Akce -->
        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm p-6 md:p-8 flex flex-col md:flex-row gap-4 md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#6B1D2F]">Bezpečnostní akce</h2>
                <p class="text-xs text-gray-500 mt-1">Reset hesla odhlásí uživatele ze všech zařízení. Smazání účtu je nevratné a smaže i všechny jeho seznamy.</p>
            </div>
            <div class="flex flex-wrap gap-3 shrink-0">
                @if(! $user->email_verified_at)
                    <form method="POST" action="{{ route('admin.users.verify-email', $user) }}">
                        @csrf
                        <button type="submit" class="bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-800 font-semibold px-4 py-2.5 rounded-xl text-xs transition">
                            ✅ Označit e-mail jako ověřený
                        </button>
                    </form>
                @endif

                @if($user->id !== auth()->id() && ! $user->is_admin)
                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                        @csrf
                        <button type="submit" class="bg-blue-50 hover:bg-blue-100 border border-blue-300 text-blue-800 font-semibold px-4 py-2.5 rounded-xl text-xs transition">
                            🕵️ Přihlásit se jako uživatel
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('Opravdu resetovat heslo tohoto uživatele?');">
                    @csrf
                    <button type="submit" class="bg-amber-50 hover:bg-amber-100 border border-[#D4AF37] text-[#6B1D2F] font-semibold px-4 py-2.5 rounded-xl text-xs transition">
                        🔑 Resetovat heslo
                    </button>
                </form>

                @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Opravdu trvale smazat tohoto uživatele a všechny jeho seznamy?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 font-semibold px-4 py-2.5 rounded-xl text-xs transition">
                            🗑️ Smazat účet
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Seznamy přání -->
        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-[#6B1D2F]">Seznamy přání ({{ $user->wishlists->count() }})</h2>
            </div>
            @if($user->wishlists->isEmpty())
                <p class="px-6 py-6 text-sm text-gray-400">Uživatel zatím nemá žádný seznam.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($user->wishlists as $wishlist)
                        <a href="{{ route('wishlists.show', $wishlist) }}" class="flex items-center justify-between px-6 py-4 hover:bg-[#FAF7F2] transition">
                            <div>
                                <div class="font-semibold text-gray-900">
                                    {{ $wishlist->occasion_icon }} {{ $wishlist->title }}
                                    @if($wishlist->isSuspended())
                                        <span class="ml-1 bg-rose-100 text-rose-700 text-[10px] font-bold px-2 py-0.5 rounded-full">POZASTAVENO</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">{{ $wishlist->items->count() }} položek · {{ $wishlist->is_public ? 'veřejný' : 'soukromý' }}</div>
                            </div>
                            <span class="text-[#6B1D2F] text-xs font-semibold">Otevřít →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Rezervace u ostatních -->
        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-[#6B1D2F]">Rezervace u cizích seznamů ({{ $user->reservations->count() }})</h2>
                <p class="text-xs text-gray-500 mt-1">Dárky, které si tento uživatel jako host/přihlášený rezervoval jinde. Majitelé seznamů toto nevidí – zůstává to jejich překvapení.</p>
            </div>
            @if($user->reservations->isEmpty())
                <p class="px-6 py-6 text-sm text-gray-400">Žádné rezervace.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($user->reservations as $item)
                        <div class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $item->title }}</div>
                            <div class="text-xs text-gray-500">
                                v seznamu
                                <a href="{{ route('wishlists.show', $item->wishlist) }}" class="text-[#6B1D2F] hover:underline">{{ $item->wishlist->title }}</a>
                                (autor: {{ $item->wishlist->user->name }})
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
