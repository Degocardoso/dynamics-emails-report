<?php

return [
    'tenant_id' => $_ENV['TENANT_ID'] ?? '',
    'client_id' => $_ENV['CLIENT_ID'] ?? '',
    'client_secret' => $_ENV['CLIENT_SECRET'] ?? '',
    'resource' => $_ENV['RESOURCE'] ?? '',
    
    'api' => [
        'base_url' => ($_ENV['RESOURCE'] ?? '') . '/api/data/v9.2',
        'timeout' => 30,
        'verify_ssl' => $_ENV['APP_ENV'] === 'production',
    ],
    
    'oauth' => [
        'token_url' => 'https://login.microsoftonline.com/' . ($_ENV['TENANT_ID'] ?? '') . '/oauth2/token',
        'cache_ttl' => 3300, // 55 minutos (token dura 60min)
    ],
    
    'email' => [
        'default_sender' => 'sucessoalvarista@fecap.br',
        'max_results_per_request' => 5000,
    ],
    
    'rate_limit' => [
        'max_requests' => (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 100),
        'period_minutes' => (int)($_ENV['RATE_LIMIT_PERIOD_MINUTES'] ?? 60),
    ],
];