<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Database\Connection;
use App\Helpers\ResponseHelper;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RateLimitMiddleware;

use function PHPSTORM_META\map;

/**
 * Definicação de Rotas da API
 * 
 * @var \App\Routes\Router $router
 */


// Rotas públicas
$router->group('/api/auth', [], function ($router) {
    // Registro de usuário
    $router->post('/register', [AuthController::class, 'register'], [
        new RateLimitMiddleware()
    ]);
    // Login
    $router->post('/login', [AuthController::class], 'login', [
        new RateLimitMiddleware()
    ]);
});


// Rotas protegidas
$router->group('/api', [new AuthMiddleware()], function ($router) {
    // Autenticação (rotas autenticadas)
    $router->group('/auth', [], function ($router) {
        $router->post('/logout', [AuthController::class, 'logout']);
        $router->get('/me', [AuthController::class, 'me']);
        $router->post('/refresh', [AuthController::class, 'refresh']);
        $router->post('/change-password', [AuthController::class, 'changePassword']);
    });


    // Usuários
    $router->group('/users', []. function($router) {
        $router->get('', [UserController::class, 'index']);
        $router->get('/{id}', [UserController::class, 'show']);
        $router->put('/{id}', [UserController::class, 'update']);
    });

    // Projetos
});


$router->get('/health', function () {


    $dbStatus = Connection::getInstance()->testConnection();

    ResponseHelper::success([
        'status' => 'ok',
        'timestamp' => date('c'),
        'database' => $dbStatus ? 'connected' : 'disconnected',
        'version' => config('app.version', '1.0.0')
    ], 'API funcionando corretamente');
});


if (config('app.debug')) {
    $router->get('api/routes', function () use ($router) {
        ResponseHelper::success([
            'routes' => $router->getRoutes()
        ]);
    });
}
