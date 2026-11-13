<?php

return [

    /*
    | Povolené cílové adresy (prefixy), na které se po přihlášení přes Google
    | z mobilní aplikace smí vrátit jednorázový kód. Adresa musí začínat jedním
    | z těchto prefixů, za kterým následuje konec, "/", "?" nebo "#".
    */
    'mobile_return_prefixes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MOBILE_RETURN_PREFIXES', 'giftis://'))
    ))),

    // Pouze pro lokální vývoj (Expo Go a Expo web).
    'mobile_return_dev_prefixes' => ['exp://', 'http://localhost:8081', 'http://127.0.0.1:8081'],

];
