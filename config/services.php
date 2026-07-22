<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'contpaqi' => [
      'api_key' => env('CONTPAQI_API_KEY'),
    ],
    'facturapi' => [
        'secret_key' => env('FACTURAPI_SECRET_KEY'),
        'sandbox' => env('FACTURAPI_SANDBOX', true),
    ],
    'facturacion_mail' => [
        'provider' => env('FACTURACION_MAIL_PROVIDER', 'laravel'),
        'mailer' => env('FACTURACION_MAIL_MAILER', 'facturas'),
        'from_address' => env('FACTURACION_MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('FACTURACION_MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Rivera Construcciones')),
        'microsoft_graph' => [
            'tenant_id' => env('FACTURACION_GRAPH_TENANT_ID'),
            'client_id' => env('FACTURACION_GRAPH_CLIENT_ID'),
            'client_secret' => env('FACTURACION_GRAPH_CLIENT_SECRET'),
            'user' => env('FACTURACION_GRAPH_USER', env('FACTURACION_MAIL_FROM_ADDRESS')),
        ],
    ],
    // config/services.php
    'attendance' => [
    'ingest_token' => env('ATTENDANCE_INGEST_TOKEN'),
],
'anticaptcha' => [
    'key' => env('ANTI_CAPTCHA_KEY'),
],

'sat_captcha' => [
    // manual: usuario captura captcha; command/local: resolver interno; auto: intenta interno y cae a manual.
    'driver' => env('SAT_CAPTCHA_DRIVER', 'auto'),
    'command' => env('SAT_CAPTCHA_COMMAND'),
    'local_url' => env('SAT_CAPTCHA_LOCAL_URL'),
    'local_initial_wait' => env('SAT_CAPTCHA_LOCAL_INITIAL_WAIT', 1),
    'local_timeout' => env('SAT_CAPTCHA_LOCAL_TIMEOUT', 30),
    'local_sleep_ms' => env('SAT_CAPTCHA_LOCAL_SLEEP_MS', 500),
    'manual_timeout' => env('SAT_CAPTCHA_MANUAL_TIMEOUT', 300),
    'manual_poll_seconds' => env('SAT_CAPTCHA_MANUAL_POLL_SECONDS', 3),
],

];
