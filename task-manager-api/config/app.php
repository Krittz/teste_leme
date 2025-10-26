<?php

declare(strict_types=1);
return [
    'name' => $_ENV['APP_NAME'] ?? 'Task Manager API',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'version' => $_ENV['APP_VERSION'] ?? '1.0.0',
    'timezone' => $_ENV['TIMEZONE'] ?? 'America/Sao_Paulo',

    'log' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'path' => $_ENV['LOG_PATH'] ?? __DIR__ . '/../storage/logs',
        'file' => $_ENV['LOG_FILE'] ?? 'app.log',
        'error_file' => $_ENV['LOG_ERROR_FILE'] ?? 'error.log',
    ],

    'upload' => [
        'max_size' => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
        'allowed_extensions' => explode(',', $_ENV['UPLOAD_ALLOWED_EXTENSIONS'] ?? 'pdf,jpg,jpeg,png'),
        'path' => $_ENV['UPLOAD_PATH'] ?? 'public/uploads',
        'projects_path' => 'public/uploads/projects',
        'task_path' => 'public/uploads/tasks',
    ],

    'security' => [
        'password_cost' => (int)($_ENV['PASSWORD_COST'] ?? 12),
        'rate_limit' => [
            'max_attemps' => (int)($_ENV['RATE_LIMIT_MAX_ATTEMPS'] ?? 5),
            'window' => (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 300),
        ],
    ],
];
