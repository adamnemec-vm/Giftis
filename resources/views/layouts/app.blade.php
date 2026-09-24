<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Giftis') }} - Pomocník s nákupem dárků</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=playfair-display:600,700|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            h1, h2, h3, .font-serif {
                font-family: 'Playfair Display', serif;
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-[#FAF7F2] text-gray-800 min-h-screen flex flex-col">
        <div class="flex-grow">
            @if (session('impersonator_id'))
                <div class="bg-amber-400 text-gray-900 text-center text-sm font-semibold py-2 px-4 flex items-center justify-center gap-3">
                    <span>🕵️ Jste přihlášeni jako {{ auth()->user()->name }} (admin náhled)</span>
                    <form method="POST" action="{{ route('admin.stop-impersonating') }}">
                        @csrf
                        <button type="submit" class="underline hover:no-underline">Vrátit se do administrátorského účtu</button>
                    </form>
                </div>
            @endif

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white/80 backdrop-blur border-b border-[#F0E8DD]">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Flash Notifications -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                @if (session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm mb-4">
                        <span class="text-xl">✨</span>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm mb-4">
                        <span class="text-xl">⚠️</span>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif
            </div>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t border-[#F0E8DD] py-8 text-center text-sm text-gray-500 mt-12">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🎁</span>
                    <span class="font-serif font-bold text-[#6B1D2F]">Gift<span class="text-[#D4AF37]">is</span></span>
                    <span>&copy; {{ date('Y') }} — Radost z dárků bez duplicit.</span>
                </div>
                <div class="text-xs text-gray-400 flex gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-[#6B1D2F]">Ochrana údajů</a>
                    <a href="{{ route('terms') }}" class="hover:text-[#6B1D2F]">Podmínky</a>
                    <span>Vytvořeno s péčí pro Vánoce & Narozeniny</span>
                </div>
            </div>
        </footer>
    </body>
</html>
