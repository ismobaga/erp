<?php

return [
    'base_url'       => env('GOWA_BASE_URL', 'https://gowa.crommixmali.com'),
    'username'       => env('GOWA_USERNAME'),
    'password'       => env('GOWA_PASSWORD'),
    'timeout'        => env('GOWA_TIMEOUT', 30),
    'retry_times'    => env('GOWA_RETRY_TIMES', 2),
    'retry_sleep_ms' => env('GOWA_RETRY_SLEEP_MS', 300),

    /*
     |--------------------------------------------------------------------------
     | Webhook Secret
     |--------------------------------------------------------------------------
     |
     | If set, every incoming webhook request must carry this value in the
     | X-Gowa-Secret header.  Leave blank to accept all requests (development).
     |
     */
    'webhook_secret' => env('GOWA_WEBHOOK_SECRET'),
];
