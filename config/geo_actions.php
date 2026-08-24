<?php

/**
 * Aksi AWS Location Service yang boleh dipilih saat membuat API key.
 *
 * Daftarnya SENGAJA dibatasi pada aksi yang memang didukung GrabMaps (lihat tabel
 * "Authorized Actions" di dokumen integrasi) — bukan seluruh aksi yang dikenal AWS.
 * Menawarkan aksi yang tidak didukung hanya akan menghasilkan key yang gagal dipakai.
 *
 * Tiap grup punya:
 *   - resource : ARN provider yang wajib ikut kalau ada aksi grup ini yang dipilih
 *   - wildcard : nilai "semua aksi di grup ini"
 *   - actions  : aksi satuan
 *
 * Label ada di lang/{locale}/geo_actions.php.
 */
return [
    'maps' => [
        'icon'     => 'bi-map',
        'wildcard' => 'geo-maps:*',
        'resource' => 'arn:aws:geo-maps:{region}::provider/default',
        'actions'  => [
            'geo-maps:GetTile',
        ],
    ],
    'places' => [
        'icon'     => 'bi-search',
        'wildcard' => 'geo-places:*',
        'resource' => 'arn:aws:geo-places:{region}::provider/default',
        'actions'  => [
            'geo-places:SearchText',
            'geo-places:Suggest',
            'geo-places:ReverseGeocode',
            'geo-places:GetPlace',
        ],
    ],
    'routes' => [
        'icon'     => 'bi-sign-turn-right',
        'wildcard' => 'geo-routes:*',
        'resource' => 'arn:aws:geo-routes:{region}::provider/default',
        'actions'  => [
            'geo-routes:CalculateRoutes',
            'geo-routes:CalculateRouteMatrix',
        ],
    ],
];
