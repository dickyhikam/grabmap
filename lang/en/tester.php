<?php

/**
 * Teks antarmuka halaman /tester-api.
 *
 * Nama parameter API (Bias Latitude, Device ID, Font Stack, dan sejenisnya)
 * sengaja TIDAK diterjemahkan — itu nama field milik AWS, dan menerjemahkannya
 * membuat halaman ini sulit dicocokkan dengan dokumentasi resminya.
 */
return [
    'title' => 'AWS Location Service API Tester',
    'subtitle' => 'Debug & test AWS Location Service APIs — Routes, Places, Maps, Geofencing & Tracking (Grab)',
    'back_to_map' => 'Back to map',

    'theme_light' => 'Light mode',
    'theme_dark' => 'Dark mode',
    'theme_system' => 'Follow system',
    'language' => 'Language',

    'api_key' => 'API Key',
    'api_key_saved' => 'API Key saved',
    'api_key_missing' => 'API Key not set',
    'api_reference' => 'API Reference',

    'rail_route' => 'Calculate Route',
    'rail_matrix' => 'Route Matrix',
    'rail_places' => 'Places',
    'rail_maps' => 'Maps',
    'rail_hide' => 'Hide panel',

    'request' => 'Request',
    'response' => 'Response',
    'request_history' => 'Request History',
    'no_requests' => 'No requests yet',
    'request_placeholder' => '// Click "Send" to see the request payload here',
    'response_placeholder' => '// Response will appear here',
    'clear' => 'Clear',
    'copy' => 'Copy',

    'gate_title' => 'API Key Required',
    'gate_subtitle' => 'Enter your AWS Location Service API key to continue',
    'gate_note' => 'This tester needs an AWS API key to render the map and call the APIs. Your key is stored in this browser (localStorage) and never sent to our server.',
    'gate_label' => 'AWS Location Service API Key',
    'gate_how' => 'How do I get an API key?',
    'gate_continue' => 'Continue',
    'gate_empty' => 'API key cannot be empty',

    // Keterangan tiap mode dan petunjuk singkat di panel kiri.
    'mode_route_desc' => 'Single route A&rarr;B with optional waypoints. Returns <b>full geometry (LineString)</b>, distance, duration, and leg details. Best for <b>drawing a route on the map</b>.',
    'mode_matrix_desc' => 'N origins &times; M destinations matrix. Returns <b>distance + duration</b> per pair only (no geometry). Best for <b>ordering stops, finding the nearest, batch comparison</b>.',
    'mode_places_desc' => 'Search, autocomplete, reverse geocode &amp; get place details (standalone v2).',
    'mode_maps_desc' => 'Inspect map resources: <b>style descriptor, tiles, glyphs, sprites</b>.',
    'mode_geofence_desc' => 'Manage geofences and evaluate positions. Provider-independent (AWS-native).',
    'mode_tracking_desc' => 'Track device positions and retrieve history. Provider-independent (AWS-native).',
    'mode_routes_desc' => 'Calculate route &amp; route matrix — no distance limit.',
    'hint_tile' => 'Returns a PBF vector tile. Response info will be shown.',
    'hint_glyph' => 'Returns PBF glyph data for map label rendering.',
    'hint_ring' => 'First and last coordinates must be identical (closed ring).',
    'hint_eval_click' => 'Click the map to set the evaluation position.',
    'hint_device_click' => 'Click the map to set the device position. The timestamp is generated automatically.',
    'maps_styles_note' => '2 map styles available — vector only, no satellite imagery.',

    // Panel isian rute dan penanda di peta.
    'pick_from_map' => 'Pick from map',
    'from_map' => 'From map',
    'add' => 'Add',
    'departure_position' => 'Departure Position',
    'destination_position' => 'Destination Position',
    'waypoints' => 'Waypoints',
    'waypoints_route_only' => '(Route only)',
    'no_waypoints' => 'No waypoints (optional)',
    'map_hint' => 'Press <b>Pick from map</b>, then click the map to set the coordinates.',
    'legend_departure' => 'Departure',
    'legend_destination' => 'Destination',
    'legend_route' => 'Route',
    'legend_result' => 'Location result',
    'api_endpoint' => 'API ENDPOINT',
    'route_matrix_input' => 'ROUTE & MATRIX INPUT',
    'send_route_request' => 'Send Route Request',

    // Petunjuk di bawah peta, per mode.
    'hint_route' => 'Press <b>Pick from map</b>, then click the map to set the coordinates.',
    'hint_matrix' => 'Press <b>Pick from map</b> / <b>Add from map</b>, then click the map to set the coordinates.',
    'hint_location' => 'Click the map to fill the reverse geocode coordinates. Search results appear as markers.',
    'hint_maps' => 'Press "Fill from map center" to take tile coordinates from the current view.',
    'hint_geofence' => 'Click the map to set the geofence center or evaluation position. Geofence boundaries are drawn as polygons.',
    'hint_tracking' => 'Click the map to set the device position. Device markers and history trails are drawn on the map.',
    'legend_geofence' => 'Geofence',
    'legend_tracking' => 'Tracking',

    // Tombol kirim dan judul daftar hasil.
    'send_request' => 'Send :mode Request',
    'geofences' => 'Geofences',
    'devices' => 'Devices',

    // Penanda versi API di panel kiri.
    'recommended' => 'Recommended',
    'legacy' => 'Legacy',
];
