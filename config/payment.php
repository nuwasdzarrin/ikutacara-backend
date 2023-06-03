<?php

return [
    'xendit' => [
        'api_key' => env('XENDIT_API_KEY', 'randomstring'),
        'callback_token' => env('XENDIT_CALLBACK_TOKEN', 'randomstring'),
        'callback_base_url' => env('XENDIT_CALLBACK_BASE_URL', 'https://ikutacara-api.kreatora.id'),
    ]
];
