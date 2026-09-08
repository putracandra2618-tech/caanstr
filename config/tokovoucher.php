<?php

return [

    'member_code' => env('TOKOVOUCHER_MEMBER_CODE', ''),

    'secret_key' => env('TOKOVOUCHER_SECRET_KEY', ''),

    'webhook_url' => env('TOKOVOUCHER_WEBHOOK_URL') ?: rtrim(env('APP_URL', 'http://localhost:8000'), '/').'/tokovoucher/callback',

    'base_url' => 'https://api.tokovoucher.net',

    'cache_ttl' => 3600,

    'dry_run' => strtolower((string) env('TOKOVOUCHER_DRY_RUN', 'off')),

];
