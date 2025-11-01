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

        $this->config['allowed_origins'] = $this->config['allowed_origins'] ?? [];
        $this->config['allowed_methods'] = $this->config['allowed_methods'] ?? [];
        $this->config['allowed_headers'] = $this->config['allowed_headers'] ?? [];
    }

    /**
     * Handle do middlware
     */
    public function handle(array $params = []): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (!$this->isOriginAllowed($origin)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Origem não permitida pelo CORS',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Methods: ' . implode(',', $this->config['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(',', $this->config['allowed_headers']));
        header('Access-Control-Expose-Headers: ' . implode(',', $this->config['exposed_headers'] ?? []));
        header('Access-Control-Allow-Credentials: ' . ($this->config['allow_credentials'] ? 'true' : 'false'));
        header('Access-Control-Max-Age: ' . (string)$this->config['max_age']);


        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Verifica se origem é permitida
     */
    private function isOriginAllowed(string $origin): bool
    {

        if (empty($origin)) {
            return true;
        }
        if (in_array('*', $this->config['allowed_origins'], true)) {
            return true;
        }
        return in_array($origin, $this->config['allowed_origins'], true);
    }
}
