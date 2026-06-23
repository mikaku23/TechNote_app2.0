<?php

return [

    /*
    |--------------------------------------------------------------------------
    | INTENT: STMIK / KAMPUS
    |--------------------------------------------------------------------------
    | Intent ini khusus untuk informasi kampus STMIK Triguna Dharma
    | Semua detail akan diambil dari config('jenis.datastmik')
    */
    'type' => 'intent',
    'priority' => 100,
    'trigger' => [
        'stmik',
        'kampus',
        'triguna dharma',
        'kampus stmik',
        'stmik triguna dharma',
        'kampus triguna dharma',
        'profil stmik',
        'profil kampus',
    ],

    // response dikosongkan karena data diambil dinamis
    'response' => [],
];
