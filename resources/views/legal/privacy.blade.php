<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zásady ochrany osobních údajů - Giftis</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:600,700|plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        h1, h2, .font-serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-[#FAF7F2] text-gray-800 min-h-screen flex flex-col antialiased">

    <header class="bg-[#6B1D2F] text-white shadow-md py-4">
        <div class="max-w-4xl mx-auto px-4 flex justify-between items-center">
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="flex items-center gap-2">
                <span class="text-2xl">🎁</span>
                <span class="font-serif font-bold text-2xl tracking-wide text-white">Gift<span class="text-[#D4AF37]">is</span></span>
            </a>
            <a href="{{ url()->previous() === url()->current() ? '/' : url()->previous() }}" class="text-xs font-semibold text-white/90 hover:text-white transition">← Zpět</a>
        </div>
    </header>

    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 py-12 w-full">
        <h1 class="text-3xl font-bold text-[#6B1D2F] font-serif mb-2">Zásady ochrany osobních údajů</h1>
        <p class="text-sm text-gray-500 mb-10">Platí od 23. 9. 2026</p>

        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm p-6 md:p-10 space-y-8 text-sm leading-relaxed text-gray-700">
            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">1. Úvod</h2>
                <p>
                    Giftis (dostupné na <a href="https://giftis.cz" class="text-[#6B1D2F] underline">giftis.cz</a>) je aplikace pro tvorbu a sdílení seznamů přání a koordinaci nákupu dárků mezi rodinou a přáteli. Tento dokument popisuje, jaké osobní údaje shromažďujeme, proč je zpracováváme a jaká máte v souvislosti s tím práva.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">2. Jaké údaje shromažďujeme</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li><strong>Registrační údaje:</strong> jméno a e-mailová adresa. Pokud se registrujete přes Google, získáme jméno a e-mail z vašeho Google účtu.</li>
                    <li><strong>Heslo:</strong> ukládáme jen jeho nevratně zahashovanou podobu, nikdy ho nevidíme v čitelné formě.</li>
                    <li><strong>Obsah seznamů přání:</strong> názvy, popisy, ceny a obrázky dárků, které do appky sami vložíte.</li>
                    <li><strong>Rezervace a příspěvky:</strong> pokud si rezervujete dárek nebo přispějete na skupinový dárek jako nepřihlášený host, uložíme jméno/přezdívku, kterou zadáte, a technický identifikátor (token) vázaný na váš prohlížeč, aby bylo možné rezervaci později dohledat nebo zrušit.</li>
                    <li><strong>Technické údaje:</strong> cookies nutné pro přihlášení a fungování rezervací (viz níže), a standardní záznamy serveru (IP adresa, čas požadavku) pro provoz a bezpečnost.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">3. K čemu údaje používáme</h2>
                <p>Výhradně k provozu appky: k vytvoření a zobrazení vašich seznamů přání, ke koordinaci rezervací dárků mezi vámi a vašimi blízkými a k zaslání upozornění vlastníkovi seznamu, že byl dárek vybrán. Údaje nepoužíváme k reklamě ani je neprodáváme třetím stranám.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">4. Jak funguje "režim překvapení"</h2>
                <p>
                    Giftis je navržený tak, aby vlastník seznamu neviděl, kdo konkrétní dárek rezervoval nebo na něj přispěl — vidí jen to, že je dárek vybraný. Jméno rezervujícího je viditelné pouze jemu samotnému (přes cookie nebo přihlášený účet) a administrátorům appky v případě řešení technického problému.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">5. Cookies</h2>
                <p>Používáme dvě funkční cookies, žádné reklamní ani sledovací třetích stran:</p>
                <ul class="list-disc pl-5 space-y-1.5 mt-2">
                    <li><strong>Přihlašovací (session) cookie</strong> — udržuje vás přihlášené.</li>
                    <li><strong>Guest token cookie</strong> — umožňuje nepřihlášeným návštěvníkům spravovat vlastní rezervace dárků v rámci jejich prohlížeče.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">6. Sdílení údajů</h2>
                <p>Vaše údaje nesdílíme s žádnými třetími stranami pro marketingové účely. Data jsou uložena na serveru v rámci Evropské unie. Přístup k datům mají pouze administrátoři appky, a to výhradně za účelem technické podpory a moderace.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">7. Doba uchovávání</h2>
                <p>Údaje uchováváme po dobu existence vašeho účtu. Při smazání účtu se spolu s ním smažou i všechny vaše seznamy přání a jejich položky.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">8. Vaše práva</h2>
                <p>V souladu s GDPR máte právo na přístup ke svým osobním údajům, jejich opravu, výmaz i přenositelnost. Úpravu jména a e-mailu můžete provést přímo ve svém profilu. O smazání účtu a všech souvisejících dat můžete požádat přímo v appce (Profil → Smazat účet) nebo nás kontaktovat na e-mailu níže.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">9. Kontakt</h2>
                <p>S dotazy ohledně zpracování osobních údajů nás kontaktujte na <a href="mailto:anemec.vm@gmail.com" class="text-[#6B1D2F] underline">anemec.vm@gmail.com</a>.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">10. Změny těchto zásad</h2>
                <p>Tyto zásady můžeme čas od času aktualizovat. O podstatných změnách vás budeme informovat v appce.</p>
            </section>
        </div>
    </main>

    <footer class="bg-white border-t border-[#F0E8DD] py-8 text-center text-sm text-gray-500 mt-12">
        <div class="max-w-4xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">🎁</span>
                <span class="font-serif font-bold text-[#6B1D2F]">Gift<span class="text-[#D4AF37]">is</span></span>
                <span>&copy; {{ date('Y') }} — Radost z dárků bez duplicit.</span>
            </div>
            <div class="text-xs text-gray-400 flex gap-4">
                <a href="{{ route('privacy') }}" class="hover:text-[#6B1D2F]">Ochrana údajů</a>
                <a href="{{ route('terms') }}" class="hover:text-[#6B1D2F]">Podmínky</a>
            </div>
        </div>
    </footer>

</body>
</html>
