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

    // Nilai awal formulir "API key baru", mengikuti kredit percobaan AWS:
    // USD 200 dan masa berlaku 3 bulan. Hanya titik mulai — keduanya tetap bisa
    // diubah di formulir, dan angka hari harus salah satu preset (30/90/180/365)
    // supaya chip-nya ikut terpilih.
    'new_key_defaults' => [
        'budget_usd'  => (float) env('AWS_NEW_KEY_BUDGET_USD', 200),
        'expiry_days' => (int) env('AWS_NEW_KEY_EXPIRY_DAYS', 90),
    ],

    // Keterangan tetap di teks serah-terima API key (resources/views/admin/api-keys/handover.blade.php).
    'handover' => [
        'provider'    => env('AWS_HANDOVER_PROVIDER', 'Grab Maps (AWS Location Service v2)'),
        'environment' => env('AWS_HANDOVER_ENV', 'Production'),
    ],

    // Tarif pajak (PPN) yang ditambahkan AWS pada tagihan. Indonesia = 11%.
    'tax_rate' => (float) env('AWS_TAX_RATE', 0.11),
];
