<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-[#6B1D2F] font-serif">👥 Uživatelé</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-xs text-[#6B1D2F] hover:text-[#541523] font-semibold">
                ← Zpět na přehled
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Hledat podle jména nebo e-mailu..."
                   class="flex-1 rounded-xl border-gray-300 focus:border-[#6B1D2F] focus:ring-[#6B1D2F] text-sm" />
            <button type="submit" class="bg-[#6B1D2F] hover:bg-[#541523] text-white font-semibold px-5 py-2 rounded-xl text-xs transition">
                Hledat
            </button>
        </form>

        <!-- Karty pro mobil -->
        <div class="md:hidden space-y-3">
            @forelse($users as $user)
                <a href="{{ route('admin.users.show', $user) }}" class="block bg-white rounded-2xl border border-[#F0E8DD] shadow-sm p-4 hover:bg-[#FAF7F2] transition">
                    <div class="font-semibold text-gray-900">
                        {{ $user->name }}
                        @if($user->is_admin)
                            <span class="ml-1 bg-[#D4AF37] text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">ADMIN</span>
                        @endif
                        @if(! $user->email_verified_at)
                            <span class="ml-1 bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-0.5 rounded-full">NEOVĚŘENO</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</div>
                    <div class="flex items-center justify-between mt-2 text-xs text-gray-400">
                        <span>{{ $user->wishlists_count }} seznamů · registrace {{ $user->created_at->format('d. m. Y') }}</span>
                        <span class="text-[#6B1D2F] font-semibold">Detail →</span>
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-2xl border border-[#F0E8DD] shadow-sm p-6 text-center text-gray-400 text-sm">
                    Žádní uživatelé nenalezeni.
                </div>
            @endforelse
        </div>

        <!-- Tabulka od tabletu výše -->
        <div class="hidden md:block bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#FAF7F2] text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Jméno</th>
                        <th class="px-6 py-3">E-mail</th>
                        <th class="px-6 py-3">Seznamy</th>
                        <th class="px-6 py-3">Registrace</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-[#FAF7F2] transition">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ $user->name }}
                                @if($user->is_admin)
                                    <span class="ml-1 bg-[#D4AF37] text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">ADMIN</span>
                                @endif
                                @if(! $user->email_verified_at)
                                    <span class="ml-1 bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-0.5 rounded-full">NEOVĚŘENO</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $user->wishlists_count }}</td>
                            <td class="px-6 py-4 text-gray-500 text-xs">{{ $user->created_at->format('d. m. Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-[#6B1D2F] hover:text-[#541523] font-semibold text-xs">
                                    Detail →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400">Žádní uživatelé nenalezeni.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </div>
</x-app-layout>
