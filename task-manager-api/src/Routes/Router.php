<?php

declare(strict_types=1);

namespace App\Routes;

use App\Helpers\ResponseHelper;

/**
 * Router - Sistema de roteamento da API
 * 
 * Gerencia rotas HTTP e execução de middlewares
 */
class Router
{
    /**
     * Rotas registradas
     */
    private array $routes = [];


    /**
     * Middlewares globais
     */
    private array $globalMiddlewares = [];

    /**
     * Configurações da aplicação
     */
    private array $config;

    /**
     * Prefixo das rotas
     */
    private string $prefix = '';

    /**
     * Middlewares do grupo atual
     */
    private array $groupMiddlewares = [];
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Registra middleware global
     */
    public function addGlobalMiddleware(object $middleware): void
    {
        $this->globalMiddlewares[] = $middleware;
    }

    /**
     * Registra rota GET
     */
    public function get(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }
    /**
     * Registra rota POST
     */
    public function post(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }
    /**
     * Registra rota PUT
     */
    public function put(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Registra rota PATH
     */
    public function patch(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    /**
     * Registra rota DELETE
     */
    public function delete(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('DEELTE', $path, $handler, $middlewares);
    }
    /**
     * Registra rota OPTIONS
     */
    public function options(string $path, callable|array $handler, array $middlewares = []): void
    {
        $this->addRoute('OPTIONS', $path, $handler, $middlewares);
    }

    /**
     * Grupo de rotas com prefixo e middlewares
     */
    public function group(string $prefix, array $middlewares, callable $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddlewares = $this->groupMiddlewares;

        $this->prefix = $previousPrefix . $prefix;
        $this->groupMiddlewares = array_merge($previousMiddlewares, $middlewares);

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    /**
     * Adiciona rota ao registro
     */
    private function addRoute(string $method, string $path, callable|array $handler, array $middlewares): void
    {
        $fullPath = $this->prefix . $path;
        $allMiddlewares = array_merge($this->groupMiddlewares, $middlewares);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $allMiddlewares,
            'pattern' => $this->pathToPattern($fullPath),
        ];
    }

    /**
     * Converte path em padrão regex
     */
    private function pathToPattern(string $path): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        return $pattern;
    }

    /**Processa a requisição */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();

        $this->executeMiddlewares($this->globalMiddlewares);

        $route = $this->matchRoute($method, $uri);

        if ($route === null) {
            ResponseHelper::notFound('Rota não encontrada');
        }
        $this->executeMiddlewares($route['middlewares'], $route['params']);

        $this->executeHandler($route['handler'], $route['params']);
    }

    /**
     * Obtém URI da requisição
     */
    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];

        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = $uri !== '/' ? rtrim($uri, '/') : $uri;
        return $uri;
    }

    /**
     * Encontra rota correspondente
     */
    private function matchRoute(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'OPTIONS') {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return [
                    'handler' => $route['handler'],
                    'middlewares' => $route['middlewares'],
                    'params' => $params,
                ];
            }
        }
        return null;
    }

    /**
     * Executa middlewares
     */
    private function executeMiddlewares(array $middlewares, array $params = []): void
    {
        foreach ($middlewares as $middleware) {
            $middleware = new $middleware();
        }
        if (method_exists($middleware, 'handle')) {
            $result = $middleware->handle($params);

            if ($result === false) {
                ResponseHelper::unauthorized('Acessp negado');
            }
        }
    }

    /**
     * Executa handler da rota
     */
    private function executeHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;

            $controller = new $controllerClass($this->config);

            if (!method_exists($controller, $method)) {
                ResponseHelper::internalError("Método {$method} naõ encontrado no controller");
            }

            $controller->method($params);
        } else {
            $handler($params);
        }
    }

    /**
     * Lista todas as rotas registradas (útil para debug)
     */
    public function getRoutes(): array
    {
        return array_map(function ($route) {
            return [
                'method' => $route['method'],
                'path' => $route['path'],
            ];
        }, $this->routes);
    }
}
