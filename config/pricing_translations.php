<?php

/**
 * Maps (api_name, tier) to translation key for pricing items.
 * Used for multi-language support without DB schema changes.
 */
return [
    'Autocomplete, Suggest' => ['*' => 'autocomplete_suggest'],
    'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby' => [
        'Core' => 'geocode_core',
        'Advanced' => 'geocode_advanced',
        'Stored' => 'geocode_stored',
    ],
    'Calculate Routes' => [
        'Core' => 'calculate_routes_core',
        'Advanced' => 'calculate_routes_advanced',
        'Premium' => 'calculate_routes_premium',
    ],
    'Calculate Route Matrix' => [
        'Core' => 'calculate_route_matrix_core',
        'Advanced' => 'calculate_route_matrix_advanced',
    ],
    'Map Tiles' => ['*' => 'map_tiles'],
];
