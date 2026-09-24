<?php

namespace Database\Seeders;

use App\Models\GiftItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Eva Nováková',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $friend = User::factory()->create([
            'name' => 'Petr Svoboda',
            'email' => 'petr@example.com',
            'password' => bcrypt('password'),
        ]);

        // Wishlist 1: Vánoce Eva
        $christmasList = Wishlist::create([
            'user_id' => $user->id,
            'title' => 'Moje Vánoční přání 2026 🎄',
            'description' => 'Ahoj všichni! Zde je můj seznam věcí, které by mi udělaly obrovskou radost pod stromečkem.',
            'occasion' => 'christmas',
            'event_date' => '2026-12-24',
            'share_code' => 'vanoce2026',
            'is_public' => true,
        ]);

        GiftItem::create([
            'wishlist_id' => $christmasList->id,
            'title' => 'Kniha: Stopařův průvodce Po Galaxii',
            'url' => 'https://www.knihydobrovsky.cz',
            'price' => 499,
            'currency' => 'Kč',
            'description' => 'Pevná vazba, ilustrované vydání.',
            'priority' => 'high',
            'status' => 'reserved',
            'reserved_by_name' => 'Káťa',
            'reserved_at' => now(),
        ]);

        GiftItem::create([
            'wishlist_id' => $christmasList->id,
            'title' => 'Bezdrátová sluchátka Bose QC SE',
            'url' => 'https://www.alza.cz',
            'price' => 5990,
            'currency' => 'Kč',
            'description' => 'Černá barva, s potlačením hluku (ANC).',
            'priority' => 'high',
            'status' => 'available',
        ]);

        GiftItem::create([
            'wishlist_id' => $christmasList->id,
            'title' => 'Tenisky Nike Air Max (Velikost 39)',
            'url' => 'https://www.nike.com',
            'price' => 3200,
            'currency' => 'Kč',
            'description' => 'Bílo-zlaté provedení.',
            'priority' => 'medium',
            'status' => 'available',
        ]);

        GiftItem::create([
            'wishlist_id' => $christmasList->id,
            'title' => 'Sada sklenic na víno Crystalex (6ks)',
            'url' => 'https://www.kuchynske-potreby.cz',
            'price' => 850,
            'currency' => 'Kč',
            'description' => 'Elegantní skleničky na červené víno.',
            'priority' => 'low',
            'status' => 'reserved',
            'reserved_by_name' => 'Babička',
            'reserved_at' => now(),
        ]);

        // Wishlist 2: Narozeniny Eva
        $birthdayList = Wishlist::create([
            'user_id' => $user->id,
            'title' => 'Narozeniny 30 let 🎂',
            'description' => 'Kulatiny jsou tu! Tipy na dárky k mým třicátým narozeninám.',
            'occasion' => 'birthday',
            'event_date' => '2026-10-15',
            'share_code' => 'narozeniny30',
            'is_public' => true,
        ]);

        GiftItem::create([
            'wishlist_id' => $birthdayList->id,
            'title' => 'Chytrý kávovar DeLonghi Magnifica',
            'url' => 'https://www.datart.cz',
            'price' => 12490,
            'currency' => 'Kč',
            'description' => 'Automatické espresso s mléčným systémem.',
            'priority' => 'high',
            'status' => 'reserved',
            'reserved_by_name' => 'Manžel Jirka',
            'reserved_at' => now(),
        ]);

        GiftItem::create([
            'wishlist_id' => $birthdayList->id,
            'title' => 'Zážitkový poukaz: Let horkovzdušným balónem',
            'url' => 'https://www.firky.cz',
            'price' => 3500,
            'currency' => 'Kč',
            'description' => 'Let nad Českým rájem pro 2 osoby.',
            'priority' => 'medium',
            'status' => 'available',
        ]);

        // Wishlist 3: Petr
        $petrList = Wishlist::create([
            'user_id' => $friend->id,
            'title' => 'Petrovo narozeninové přání 🎁',
            'description' => 'Moje tipy pro oslavu narozenin s přáteli.',
            'occasion' => 'birthday',
            'event_date' => '2026-11-05',
            'share_code' => 'petr-narozeniny',
            'is_public' => true,
        ]);

        GiftItem::create([
            'wishlist_id' => $petrList->id,
            'title' => 'Lego Technic Porsche 911 GT3',
            'url' => 'https://www.lego.com',
            'price' => 3990,
            'currency' => 'Kč',
            'description' => 'Detailní stavebnice auta.',
            'priority' => 'high',
            'status' => 'reserved',
            'reserved_by_name' => 'Honza',
            'reserved_at' => now(),
        ]);

        GiftItem::create([
            'wishlist_id' => $petrList->id,
            'title' => 'Nerezová grilovací sada v kufříku',
            'url' => 'https://www.grilovani.cz',
            'price' => 1200,
            'currency' => 'Kč',
            'description' => 'Sada 12 ks náčiní na gril.',
            'priority' => 'medium',
            'status' => 'available',
        ]);
    }
}
