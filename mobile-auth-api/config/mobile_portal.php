<?php

return [
    'jwt_secret' => env('MOBILE_JWT_SECRET', env('APP_KEY')),
    'jwt_ttl_minutes' => (int) env('MOBILE_JWT_TTL_MINUTES', 1440),

    'temporary_token_ttl_seconds' => (int) env('TEMPORARY_LOGIN_TOKEN_TTL_SECONDS', 60),

    'integration_secret' => env('MOBILE_AUTH_INTEGRATION_SECRET'),

    'systems' => [
        'leave' => [
            'url' => env('LEAVE_APP_URL', 'https://leave-management-mdjw.onrender.com'),
            'enabled' => (bool) env('LEAVE_APP_ENABLED', true),
            'sync_secret' => env('LEAVE_APP_SYNC_SECRET', env('MOBILE_AUTH_INTEGRATION_SECRET')),
        ],
        'medical' => [
            'url' => env('MEDICAL_APP_URL'),
            'enabled' => (bool) env('MEDICAL_APP_ENABLED', false),
        ],
    ],
];
