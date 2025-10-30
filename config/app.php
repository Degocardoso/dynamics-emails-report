<?php

return [
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    
    'log' => [
        'path' => __DIR__ . '/../logs/app.log',
        'level' => $_ENV['LOG_LEVEL'] ?? 'error',
    ],
    
    'cache' => [
        'driver' => !empty($_ENV['REDIS_HOST']) ? 'redis' : 'filesystem',
        'redis' => [
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        ],
        'filesystem' => [
            'path' => __DIR__ . '/../storage/cache',
        ],
    ],
    
    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'path' => __DIR__ . '/../storage/sessions',
    ],
];