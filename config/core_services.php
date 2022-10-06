<?php

return [
    'cognito' => [
        'region' => env('COGNITO_REGION'),
        'version' => env('COGNITO_VERSION'),
        'key' => env('COGNITO_KEY'),
        'secret' => env('COGNITO_SECRET'),
        'client_id' => env('COGNITO_CLIENT_ID'),
        'client_secret' => env('COGNITO_CLIENT_SECRET'),
        'user_pool_id' => env('COGNITO_USER_POOL_ID'),
    ],
];
