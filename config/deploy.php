<?php

return [
    'extract_token' => env('DEPLOY_EXTRACT_TOKEN'),
    'public_web_root' => env('DEPLOY_PUBLIC_WEB_ROOT'),
    'site_url' => env('DEPLOY_SITE_URL', env('APP_URL')),
];
