<?php

return [
    'region' => env('AWS_REGION', 'ap-southeast-1'),
    'version' => 'latest',
    'credentials' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],
];
