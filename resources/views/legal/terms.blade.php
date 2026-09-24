<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Podmínky používání - Giftis</title>
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
        <h1 class="text-3xl font-bold text-[#6B1D2F] font-serif mb-2">Podmínky používání</h1>
        <p class="text-sm text-gray-500 mb-10">Platí od 23. 9. 2026</p>

        <div class="bg-white rounded-3xl border border-[#F0E8DD] shadow-sm p-6 md:p-10 space-y-8 text-sm leading-relaxed text-gray-700">
            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">1. Přijetí podmínek</h2>
                <p>Používáním appky Giftis (<a href="https://giftis.cz" class="text-[#6B1D2F] underline">giftis.cz</a>) souhlasíte s těmito podmínkami. Pokud s nimi nesouhlasíte, appku prosím nepoužívejte.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">2. Co Giftis dělá</h2>
                <p>Giftis umožňuje vytvářet seznamy přání, sdílet je s rodinou a přáteli přes odkaz a koordinovat, kdo který dárek koupí — aby nedocházelo k duplicitním dárkům a aby vlastník seznamu nevěděl dopředu, co konkrétně dostane.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">3. Registrace účtu</h2>
                <p>K vytváření vlastních seznamů je potřeba účet. Odpovídáte za správnost údajů, které při registraci uvedete, a za zachování bezpečnosti svého hesla. Za aktivity provedené pod vaším účtem odpovídáte vy.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">4. Giftis nezpracovává platby</h2>
                <p>
                    Rezervace dárku ani příspěvek na skupinový dárek <strong>není platební transakcí</strong> — appka nezprostředkovává ani nezpracovává žádné platby. Jde čistě o koordinační nástroj, který má zabránit duplicitním nákupům. Samotný nákup dárku a případné vyrovnání příspěvků mezi lidmi probíhá zcela mimo appku a je věcí dohody mezi vámi.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">5. Pravidla pro obsah</h2>
                <p>Obsah, který do seznamů vkládáte (názvy, popisy, obrázky), musí být zákonný a neměl by porušovat práva třetích osob. Vyhrazujeme si právo seznam pozastavit nebo účet zablokovat, pokud obsahuje nevhodný nebo protiprávní obsah.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">6. Moderace a správa účtu</h2>
                <p>Administrátoři appky mohou z důvodu podpory nebo moderace obsahu pozastavit veřejnou dostupnost seznamu, upravit či smazat nevhodný obsah, nebo v odůvodněných případech smazat uživatelský účet.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">7. Omezení odpovědnosti</h2>
                <p>Appku poskytujeme „tak, jak je", bez záruky nepřetržité dostupnosti. Neodpovídáme za škody vzniklé v souvislosti s nákupem, výběrem nebo nedodáním dárků dohodnutých mezi uživateli mimo appku.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">8. Zrušení účtu</h2>
                <p>Svůj účet můžete kdykoliv smazat v nastavení profilu. Smazáním účtu se nevratně odstraní i všechny vaše seznamy a položky v nich.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">9. Změny podmínek</h2>
                <p>Tyto podmínky můžeme čas od času upravit. O podstatných změnách vás budeme informovat v appce.</p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#6B1D2F] font-serif mb-2">10. Rozhodné právo a kontakt</h2>
                <p>Tyto podmínky se řídí právním řádem České republiky. S dotazy nás kontaktujte na <a href="mailto:anemec.vm@gmail.com" class="text-[#6B1D2F] underline">anemec.vm@gmail.com</a>.</p>
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
