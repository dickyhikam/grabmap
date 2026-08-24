<?php

/**
 * Katalog izin.
 *
 * Sengaja di kode, bukan di database: tiap izin hanya berarti kalau ada kode yang
 * menegakkannya. Yang disimpan di database adalah ROLE dan izin mana yang dicentang.
 *
 * Aturan main: JANGAN menambah kunci untuk aksi yang belum ada rutenya. Contohnya
 * Perusahaan dan API Key memang tidak punya aksi hapus, jadi tidak ada *.delete —
 * lebih baik daftarnya pendek tapi jujur daripada panjang tapi separuh hiasan.
 *
 * Label dibaca dari lang/{locale}/permissions.php.
 */
return [
    'dashboard' => [
        'dashboard.view',
    ],
    'companies' => [
        'companies.view',
        'companies.create',
        'companies.update',
    ],
    'api_keys' => [
        'api_keys.view',
        'api_keys.create',
        'api_keys.update',
        'api_keys.assign',
    ],
    'aws_accounts' => [
        'aws_accounts.view',
        'aws_accounts.create',
        'aws_accounts.update',
        'aws_accounts.delete',
    ],
    'cost' => [
        'cost_settings.view',
        'cost_settings.update',
    ],
    'tools' => [
        'simulator.use',
    ],
    'users' => [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.credentials',
    ],
    'roles' => [
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
    ],
];
