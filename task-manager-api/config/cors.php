<?php

declare(strict_types=1);

return [
    'enabled' => filter_var($_ENV['CORS_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5500'),
    'allowed_methods' => explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
    'allowed_headers' => explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With'),
    'exposed_headers' => ['Content-Length', 'X-Request-Id'],
    'allow_credentials' => filter_var($_ENV['CORS_ALLOW_CREDENTIALS'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'max_age' => (int)($_ENV['CORS_MAX_AGE'] ?? 3600),
];
