<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Admin SDK integration
    |
    */

    'project_id' => 'healthreach-9167b',

    'credentials' => json_decode(file_get_contents(base_path('firebase-service-account.json')), true),

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'firestore' => [
        'database' => '(default)',
    ],
];
