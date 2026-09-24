<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-[#6B1D2F] font-serif">🛠️ Administrace</h1>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 bg-[#6B1D2F] hover:bg-[#541523] text-white font-semibold px-5 py-2.5 rounded-2xl text-xs shadow-md transition">
                👥 Správa uživatelů
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-3xl p-6 border border-[#F0E8DD] shadow-sm text-center">
                <div class="text-3xl font-extrabold text-[#6B1D2F]">{{ $stats['users'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Uživatelů</div>
            </div>
            <div class="bg-white rounded-3xl p-6 border border-[#F0E8DD] shadow-sm text-center">
                <div class="text-3xl font-extrabold text-[#6B1D2F]">{{ $stats['admins'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Administrátorů</div>
            </div>
            <div class="bg-white rounded-3xl p-6 border border-[#F0E8DD] shadow-sm text-center">
                <div class="text-3xl font-extrabold text-[#6B1D2F]">{{ $stats['wishlists'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Seznamů přání</div>
            </div>
            <div class="bg-white rounded-3xl p-6 border border-[#F0E8DD] shadow-sm text-center">
                <div class="text-3xl font-extrabold text-[#6B1D2F]">{{ $stats['gift_items'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Dárků celkem</div>
            </div>
            <div class="bg-white rounded-3xl p-6 border border-[#F0E8DD] shadow-sm text-center">
                <div class="text-3xl font-extrabold text-[#6B1D2F]">{{ $stats['reserved_items'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Rezervovaných</div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-[#6B1D2F]">Nejnovější uživatelé</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($recentUsers as $user)
                    <a href="{{ route('admin.users.show', $user) }}" class="flex items-center justify-between px-6 py-4 hover:bg-[#FAF7F2] transition">
                        <div>
                            <div class="font-semibold text-gray-900">
                                {{ $user->name }}
                                @if($user->is_admin)
                                    <span class="ml-1 bg-[#D4AF37] text-gray-900 text-[10px] font-bold px-2 py-0.5 rounded-full">ADMIN</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $user->wishlists_count }} {{ $user->wishlists_count === 1 ? 'seznam' : 'seznamů' }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
