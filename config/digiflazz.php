<?php

return [

    'username' => env('DIGIFLAZZ_USERNAME', ''),

    'api_key' => env('DIGIFLAZZ_API_KEY', ''),

    'webhook_secret' => env('DIGIFLAZZ_WEBHOOK_SECRET', ''),

    'webhook_url' => env('DIGIFLAZZ_WEBHOOK_URL') ?: rtrim(env('APP_URL', 'http://localhost:8000'), '/').'/digiflazz/callback',

    'development' => env('DIGIFLAZZ_DEVELOPMENT', true),

    'base_url' => 'https://api.digiflazz.com/v1',

    'cache_ttl' => 3600,

];
