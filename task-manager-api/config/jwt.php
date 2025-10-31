<?php

declare(strict_types=1);

return [
    'secret' => $_ENV['JWT_SECRET'] ?? 'change-this-secret-key-in-production',
    'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
    'expiration' => (int)($_ENV['JWT_EXPIRATION'] ?? 86400),
    'refresh_expiration' => (int)($_ENV['JWT_REFRESH_EXPIRATION'] ?? 604800),
    'issuer' => $_ENV['JWT_ISSUER'] ?? 'taks-manager-api',
    'audience' => $_ENV['JWT_AUDIENCE'] ?? 'task-manager-client',

    'cookie' => [
        'name' => $_ENV['JWT_COOKIE_NAME'] ?? 'auth_token',
        'secure' => filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'httponly' => true,
        'samesite' => $_ENV['COOKIE_SAMESITE'] ?? 'Strict',
        'domain' => $_ENV['COOKIE_DOMAIN'] ?? '',
        'path' => $_ENV['COOKIE_PATH'] ?? '/',
    ],

    'claims' => [
        'user_id' => true,
        'email' => true,
        'name' => true,
    ],
];
