<?php

/** Label untuk config/geo_actions.php. */
return [
    'groups' => [
        'maps' => 'Maps',
        'places' => 'Places',
        'routes' => 'Routes',
    ],
    'group_desc' => [
        'maps' => 'Tile & style peta',
        'places' => 'Pencarian, autocomplete, geocoding',
        'routes' => 'Rute & matriks jarak',
    ],
    'all' => [
        'maps' => 'Semua aksi peta',
        'places' => 'Semua aksi places',
        'routes' => 'Semua aksi rute',
    ],
    'keys' => [
        'geo-maps:GetTile' => 'Tile peta',
        'geo-places:SearchText' => 'Cari berdasarkan teks',
        'geo-places:Suggest' => 'Saran / autocomplete',
        'geo-places:ReverseGeocode' => 'Koordinat → alamat',
        'geo-places:GetPlace' => 'Detail tempat',
        'geo-routes:CalculateRoutes' => 'Hitung rute',
        'geo-routes:CalculateRouteMatrix' => 'Matriks jarak',
    ],
];
