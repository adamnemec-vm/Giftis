<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giftis - Pomocník s nákupem vánočních a narozeninových dárků</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:600,700|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        h1, h2, h3, .font-serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-[#FAF7F2] text-gray-800 antialiased min-h-screen flex flex-col justify-between">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur border-b border-[#F0E8DD] sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="text-3xl">🎁</span>
                <span class="font-serif font-bold text-3xl tracking-wide text-[#6B1D2F]">Gift<span class="text-[#D4AF37]">is</span></span>
            </div>

            <div class="flex items-center gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="bg-[#6B1D2F] text-white px-5 py-2.5 rounded-2xl font-semibold text-xs hover:bg-[#541523] transition">
                            Přejít do mého účtu
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-bold text-[#6B1D2F] hover:text-[#541523] transition">
                            Přihlásit se
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-[#6B1D2F] text-white px-5 py-2.5 rounded-2xl font-bold text-xs hover:bg-[#541523] shadow-sm transition">
                                Registrace zdarma
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-grow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 bg-amber-100 text-[#6B1D2F] font-bold text-xs px-4 py-1.5 rounded-full border border-[#D4AF37]/40 shadow-sm">
                        <span>🎄</span> <span>Vánoce & Narozeniny bez nevhodných dárků</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl font-extrabold text-[#6B1D2F] font-serif leading-tight">
                        Přejte si to, co opravdu chcete. Bez duplicit a trapasů.
                    </h1>

                    <p class="text-gray-600 text-base leading-relaxed">
                        <strong>Giftis</strong> vám umožní snadno vytvořit seznam přání k Vánocům, narozeninám nebo svatbě. Přidejte odkazy, obrázky i ceny a sdílejte seznam s rodinou a přáteli.
                    </p>

                    <!-- Klíčové výhody -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-amber-100 text-[#6B1D2F] flex items-center justify-center font-bold text-sm shrink-0">✨</span>
                            <div class="text-xs text-gray-700">
                                <strong>Žádné duplicitní dárky:</strong> Přátelé si mohou položku rezervovat přímo v systému.
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-amber-100 text-[#6B1D2F] flex items-center justify-center font-bold text-sm shrink-0">🕵️‍♀️</span>
                            <div class="text-xs text-gray-700">
                                <strong>Tajemství zůstává zachováno:</strong> Vy uvidíte pouze to, že dárek dostanete, ale <u>nikdy neuvidíte jméno dárce</u>!
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-amber-100 text-[#6B1D2F] flex items-center justify-center font-bold text-sm shrink-0">📊</span>
                            <div class="text-xs text-gray-700">
                                <strong>Ukazatel pokroku (Progress Bar):</strong> Přehled o tom, kolik přání z vašich seznamů je už vybraných.
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 bg-[#6B1D2F] hover:bg-[#541523] text-white font-bold px-8 py-4 rounded-2xl shadow-lg transition transform active:scale-95 text-sm">
                            <span>🎁 Vytvořit účet v Giftis zdarma</span>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-[#6B1D2F] border border-[#F0E8DD] font-bold px-6 py-4 rounded-2xl shadow-sm transition text-sm">
                            <span>Přihlásit se</span>
                        </a>
                    </div>
                </div>

                <!-- Vizuální karta v novém designu -->
                <div class="bg-gradient-to-br from-[#6B1D2F] to-[#8C273F] p-8 rounded-3xl text-white shadow-2xl space-y-6 relative overflow-hidden">
                    <div class="flex justify-between items-center border-b border-white/20 pb-4">
                        <span class="bg-[#D4AF37] text-gray-900 font-bold px-3 py-1 rounded-full text-xs">
                            🎄 Vánoční přání 2026
                        </span>
                        <span class="text-xs text-white/80">Sdílený seznam</span>
                    </div>

                    <div class="space-y-4">
                        <!-- Item 1 -->
                        <div class="bg-white/10 backdrop-blur rounded-2xl p-4 flex items-center justify-between border border-white/10">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📚</span>
                                <div>
                                    <div class="font-bold text-sm">Stopařův průvodce Po Galaxii</div>
                                    <div class="text-xs text-amber-200 font-medium">499 Kč (Pevná vazba)</div>
                                </div>
                            </div>
                            <span class="bg-amber-400 text-gray-900 font-bold text-[10px] uppercase px-3 py-1 rounded-full shadow-sm">
                                🔒 Rezervováno
                            </span>
                        </div>

                        <!-- Item 2 -->
                        <div class="bg-white/10 backdrop-blur rounded-2xl p-4 flex items-center justify-between border border-white/10">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🎧</span>
                                <div>
                                    <div class="font-bold text-sm">Bezdrátová sluchátka Bose</div>
                                    <div class="text-xs text-amber-200 font-medium">5 990 Kč</div>
                                </div>
                            </div>
                            <span class="bg-emerald-400 text-gray-900 font-bold text-[10px] uppercase px-3 py-1 rounded-full shadow-sm">
                                ✨ Volné k rezervaci
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar example -->
                    <div class="pt-2">
                        <div class="flex justify-between items-center text-xs font-bold text-amber-200 mb-1">
                            <span>Stav vybraných dárků</span>
                            <span>50%</span>
                        </div>
                        <div class="w-full bg-white/20 rounded-full h-3 overflow-hidden p-0.5">
                            <div class="bg-[#D4AF37] h-full rounded-full w-1/2"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-[#F0E8DD] py-8 text-center text-xs text-gray-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">🎁</span>
                <span class="font-serif font-bold text-[#6B1D2F]">Gift<span class="text-[#D4AF37]">is</span></span>
                <span>&copy; {{ date('Y') }} — Všechna práva vyhrazena.</span>
            </div>
            <div class="flex gap-4">
                <a href="{{ route('privacy') }}" class="hover:text-[#6B1D2F]">Ochrana údajů</a>
                <a href="{{ route('terms') }}" class="hover:text-[#6B1D2F]">Podmínky</a>
            </div>
        </div>
    </footer>

</body>
</html>
