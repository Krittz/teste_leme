<?php

declare(strict_types=1);

use App\Routes\Router;
use App\Middlewares\CorsMiddleware;
use App\Middlewares\JsonMiddleware;
use App\Middlewares\AuthMiddleware;

/**
 * Entry Point da API - Task Manager
 */

require_once __DIR__ . '/../autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

$config = [
    'app' => require __DIR__ . '/../config/app.php',
    'database' => require __DIR__ . '/../config/database.php',
    'jwt' => require __DIR__ . '/../config/jwt.php',
    'cors' => require __DIR__ . '/../config/cors.php',
];

date_default_timezone_set($config['app']['timezone']);

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Type: application/json; charset=utf-8');

try {
    $router = new Router($config);

    $router->addGlobalMiddleware(new CorsMiddleware($config['cors']));
    $router->addGlobalMiddleware(new JsonMiddleware());

    require __DIR__ . '/../src/Routes/api.php';

    $router->dispatch();
} catch (Throwable $e) {
    http_response_code(500);

    $response = [
        'success' => false,
        'message' => 'Erro interno do servidor',
        'errors' => null,
        'meta' => [
            'timestamp' => date('c'),
            'version' => $config['app']['version'] ?? '1.0.0',
        ],
    ];

    if ($config['app']['debug']) {
        $response['errors'] = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
    }

    error_log(sprintf(
        "[%s] %s: %s in %s:%d\n%s",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
