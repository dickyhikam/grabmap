<?php

return [
    'region' => env('AWS_REGION', 'ap-southeast-1'),
    'version' => 'latest',
    'credentials' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

    // Kurs estimasi USD -> IDR untuk menampilkan perkiraan biaya dalam Rupiah.
    // Bisa di-override lewat input di halaman usage (tersimpan), atau ubah default di sini.
    'usd_to_idr' => (float) env('AWS_USD_TO_IDR', 16500),

    // Tarif pajak (PPN) yang ditambahkan AWS pada tagihan. Indonesia = 11%.
    'tax_rate' => (float) env('AWS_TAX_RATE', 0.11),
];
