<?php

return [
    'superadmin' => [
        'name' => env('SEED_SUPERADMIN_NAME', 'Super Admin'),
        'email' => env('SEED_SUPERADMIN_EMAIL', 'superadmin@example.com'),
        'password' => env('SEED_SUPERADMIN_PASSWORD', 'Password@1234'),
    ],

    'test_user' => [
        'name' => env('SEED_TEST_USER_NAME', 'Test User'),
        'email' => env('SEED_TEST_USER_EMAIL', 'test@example.com'),
    ],
];
