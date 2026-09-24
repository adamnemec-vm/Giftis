<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Tailwind%20CSS-3-38B2AC?logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white" alt="Alpine.js">
</p>

# 🎁 Giftis

**Giftis** je webová aplikace pro správu seznamů přání (wishlistů) a jejich rezervaci mezi rodinou a přáteli — bez rizika, že si dva lidé koupí stejný dárek.

Majitel seznamu vidí jen to, že dárek už byl vybrán, ale **nevidí kým** — takže překvapení zůstává zachováno až do rozbalení.

## ✨ Funkce

- **Seznamy přání** — vytváření, úprava a mazání libovolného počtu seznamů s příležitostí (Vánoce, narozeniny, svatba, výročí…), datem události a popisem.
- **Anonymní rezervace** — kdokoliv s odkazem na seznam si může dárek "zamluvit" jako koupený; majitel seznamu vidí pouze stav rezervace, ne jméno rezervujícího.
- **Rezervace i bez účtu** — hosté rezervují jen podle jména, se session tokenem a obnovovacím odkazem pro případ ztráty cookies.
- **Skupinové dárky** — u dražších položek se může na dárek složit více lidí najednou (průběžné sledování vybrané částky).
- **Veřejné seznamy jako inspirace** — seznam lze zveřejnit v přehledu pro ostatní uživatele; veřejné seznamy ale slouží jen jako inspirace a nejde v nich nic rezervovat (to je vyhrazené pro lidi, kteří dostali přímý odkaz).
- **Skupiny** — založení uzavřené skupiny (rodina, přátelé), pozvání dalších uživatelů a výběr, které konkrétní seznamy skupina uvidí a může v nich rezervovat.
- **Moje rezervace** — přehled všeho, co uživatel sám zarezervoval nebo na co přispěl, napříč všemi seznamy.
- **Přihlášení přes Google** — kromě klasické registrace i OAuth přihlášení / registrace přes Google účet.
- **QR kód** — ke každému seznamu lze vygenerovat QR kód pro snadné sdílení offline.
- **Administrace** — správa uživatelů (reset hesla, ruční ověření e-mailu, "přihlásit se jako"), moderace veřejných seznamů (pozastavení) a mazání obsahu.
- **České lokalizované validace** — formulářové chyby v češtině, ne v angličtině.

## 🛠️ Technologie

- [Laravel 12](https://laravel.com) (PHP 8.2+)
- MySQL
- [Laravel Socialite](https://laravel.com/docs/socialite) (Google OAuth)
- [Tailwind CSS](https://tailwindcss.com) + [Alpine.js](https://alpinejs.dev)
- [Vite](https://vitejs.dev)
- PHPUnit (feature testy pokrývající autorizaci, rezervace, skupiny i administraci)

## 🚀 Lokální spuštění

### Požadavky

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL (nebo SQLite pro rychlý start)

### Instalace

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

V `.env` nastavte připojení k databázi (`DB_CONNECTION`, `DB_DATABASE`, ...) a případně údaje pro Google OAuth (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`).

```bash
php artisan migrate
npm run build
php artisan serve
```

Pro vývoj s hot-reloadem frontendu:

```bash
composer run dev
```

### Testy

```bash
php artisan test
```

## 📁 Struktura projektu

Jde o standardní Laravel aplikaci (MVC): modely v `app/Models`, controllery v `app/Http/Controllers`, autorizace přes policies v `app/Policies`, validace přes form requesty v `app/Http/Requests` a Blade šablony v `resources/views`.
