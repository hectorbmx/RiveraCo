<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Grandstream UCM API
    |--------------------------------------------------------------------------
    |
    | Configuracion para consultar el UCM6304A desde SIRICO local o desde el
    | futuro agente local. No guardar secretos en Git; usar variables .env.
    |
    */

    'enabled' => env('GRANDSTREAM_ENABLED', false),

    'base_url' => env('GRANDSTREAM_BASE_URL'),

    'api_path' => env('GRANDSTREAM_API_PATH', '/api'),

    'username' => env('GRANDSTREAM_USERNAME'),

    'password' => env('GRANDSTREAM_PASSWORD'),

    'version' => env('GRANDSTREAM_API_VERSION', '1.0'),

    'verify_tls' => env('GRANDSTREAM_VERIFY_TLS', true),

    'timeout' => env('GRANDSTREAM_TIMEOUT', 15),

    'connect_timeout' => env('GRANDSTREAM_CONNECT_TIMEOUT', 5),

    'mode' => env('GRANDSTREAM_MODE', 'local'),

    'cdr' => [
        'page_size' => env('GRANDSTREAM_CDR_PAGE_SIZE', 100),
        'padding_hours' => env('GRANDSTREAM_CDR_PADDING_HOURS', 12),
        'timezone' => env('GRANDSTREAM_TIMEZONE', 'America/Mexico_City'),
        'format' => env('GRANDSTREAM_CDR_FORMAT', 'json'),
    ],
    'agent' => [
        'server_url' => env('GRANDSTREAM_AGENT_SERVER_URL'),
        'token' => env('GRANDSTREAM_AGENT_TOKEN'),
        'email' => env('GRANDSTREAM_AGENT_EMAIL'),
        'password' => env('GRANDSTREAM_AGENT_PASSWORD'),
        'timeout' => env('GRANDSTREAM_AGENT_TIMEOUT', 30),
    ],


    'actions' => [
        'challenge' => 'challenge',
        'login' => 'login',
        'system_status' => 'getSystemStatus',
        'extensions' => 'listAccount',
        'cdr' => 'cdrapi',
        'dial_outbound' => 'dialOutbound',
    ],

];