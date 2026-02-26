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
    'Snap to Roads' => [
        'Advanced' => 'snap_to_roads_advanced',
        'Premium' => 'snap_to_roads_premium',
    ],
    'Optimize Waypoints' => [
        'Advanced' => 'optimize_waypoints_advanced',
        'Premium' => 'optimize_waypoints_premium',
    ],
    'Calculate Isolines' => [
        'Advanced' => 'calculate_isolines_advanced',
        'Premium' => 'calculate_isolines_premium',
    ],
    'Dynamic Maps' => ['*' => 'dynamic_maps'],
    'Map Tiles' => ['*' => 'map_tiles'],
    'Static Maps' => ['*' => 'static_maps'],
    'Open Data Dynamic Maps (Tiles)' => ['*' => 'open_data_dynamic_maps'],
    'Positions Written' => [
        '0 - 500K' => 'positions_written_0_500k',
        '500K - 5M' => 'positions_written_500k_5m',
        '5M - 50M' => 'positions_written_5m_50m',
        'Above 50M' => 'positions_written_above_50m',
    ],
    'Batch Positions Read' => ['*' => 'batch_positions_read'],
    'Devices Deleted' => ['*' => 'devices_deleted'],
    'Position Integrity Evaluated' => ['*' => 'position_integrity_evaluated'],
    'Positions Evaluated' => [
        '0 - 250K' => 'positions_evaluated_0_250k',
        '250K - 2M' => 'positions_evaluated_250k_2m',
        '2M - 25M' => 'positions_evaluated_2m_25m',
        'Above 25M' => 'positions_evaluated_above_25m',
    ],
    'Geofences Created/Deleted/Described' => ['*' => 'geofences_crud_described'],
    'Geofence List Requests' => ['*' => 'geofence_list_requests'],
    'Geofence-months Stored' => ['*' => 'geofence_months_stored'],
    'Geofence Event Forecast Requested' => ['*' => 'geofence_event_forecast'],
    'Resource CRUD/List Requests' => ['*' => 'resource_crud_list'],
];
