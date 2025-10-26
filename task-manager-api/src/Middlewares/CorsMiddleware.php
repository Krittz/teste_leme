<?php

namespace App\Middlewares;

/**
 * Middleware CORS 
 * 
 * Gerencia permissões de acesso cross-origin
 */
class CorsMiddleware
{
    private array $config;
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Handle do middlware
     */
    public function handle(array $params = []): bool
    {
        if (!$this->config['enabled']) {
            return true;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($this->isOriginAllowed($origin)) {
            header("Access-Control-Allow-Origin: {$origin}");

            if ($this->config['allow_credentials']) {
                header('Access-Control-Allow-Credentials: true');
            }
            header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['allowed_methods']));
            header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['allowed_headers']));

            if (!empty($this->config['exposed_headers'])) {
                header('Access-Control-Expose-Headers: ' . implode(', ', $this->config['exposed_headers']));
            }
            header('Access-Control-Max-Age: ' . $this->config['max_age']);
        }

        if ($_SERVER['REQUEST_METHOD' == 'OPTIONS']) {
            http_response_code(204);
            exit;
        }
        return true;
    }

    /**
     * Verifica se origem é permitida
     */
    private function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }
        if (in_array('*', $this->config['allowed_orgins'], true)) {
            return true;
        }
        return in_array($origin, $this->config['allowerd_origins'], true);
    }
}
