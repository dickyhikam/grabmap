<?php

/*
|--------------------------------------------------------------------------
| Per-Feature Maintenance Registry
|--------------------------------------------------------------------------
|
| Toggle any feature into maintenance mode by flipping its env flag to true.
| The `feature.maintenance:<key>` middleware reads this registry and returns
| the shared errors.maintenance view (HTTP 503) when the flag is on.
|
| To add a new feature:
|   1. Add an entry below with a unique key.
|   2. Attach `->middleware('feature.maintenance:<key>')` to the route.
|   3. Set `MAINTENANCE_<KEY_UPPER>=true` in .env to activate.
|
*/

return [

    'pricing' => [
        'enabled'     => env('MAINTENANCE_PRICING', false),
        'name'        => 'Pricing Comparison',
        'description' => 'the pricing data and cost calculations',
    ],

    'tester' => [
        'enabled'     => env('MAINTENANCE_TESTER', false),
        'name'        => 'API Tester',
        'description' => 'the interactive API testing playground',
    ],

    'docs' => [
        'enabled'     => env('MAINTENANCE_DOCS', false),
        'name'        => 'API Reference',
        'description' => 'the AWS Location Service API documentation',
    ],

    'tutorial' => [
        'enabled'     => env('MAINTENANCE_TUTORIAL', false),
        'name'        => 'Tutorial Hub',
        'description' => 'the tutorial content',
    ],

    'home' => [
        'enabled'     => env('MAINTENANCE_HOME', false),
        'name'        => 'Interactive Map',
        'description' => 'the interactive live map',
    ],

    'feature_lab' => [
        'enabled'     => env('MAINTENANCE_FEATURE_LAB', false),
        'name'        => 'Feature Lab',
        'description' => 'experimental feature previews',
    ],

    /*
    |------------------------------------------------------------------
    | Global bypass token (optional)
    |------------------------------------------------------------------
    | If set, users who append ?bypass=<token> or have a cookie with
    | this value can access the page even while maintenance is on.
    | Handy for QA/preview on prod without exposing the feature yet.
    */
    'bypass_token' => env('MAINTENANCE_BYPASS_TOKEN'),

];
