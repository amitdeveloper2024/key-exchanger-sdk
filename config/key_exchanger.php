<?php
return [

    'url' => env('KEY_EXCHANGER_URL'),

    'application_id' => env('KEY_EXCHANGER_APPLICATION_ID'),

    'private_key' => env('KEY_EXCHANGER_PRIVATE_KEY'),

    'server_public_key' => env('KEY_EXCHANGER_SERVER_PUBLIC_KEY'),

    'connection_timeout' => 10,

    'max_retries' => 10,

    'retry_intervals' => [
        5,
        30,
        60,
        300,
        900,
        1800,
    ],
];