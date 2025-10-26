<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Helpers\ResponseHelper;
use Apps\Services\JWTService;

/**
 * Middleware de Autenticação JWT
 * 
 * Valida token JWT e injeta usuário autenticado.
 */
class AuthMiddleware
{
    /**
     * Handle do middleware
     */
    public function handle(array $params = []): bool
    {
        $cookieName = config('jwt.cookie.name', 'auth_token');
        $token = $_COOKIE[$cookieName] ?? null;

        if (!$token) {
            $token = $this->getTokenFromHeader();
        }

        if (!$token) {
            ResponseHelper::unauthorized('Token de autenticação não fornecido');
        }

        $jwtService = new JWTService();
        $payload = $jwtService->validate($token);

        if (!$payload) {
            ResponseHelper::unauthorized('Token inválido ou expirado');
        }

        $_SERVER['AUTH_USER'] = $payload;
        $_SERVER['AUTH_USER_ID'] = $payload['user_id'] ?? null;
        return true;
    }

    /**
     * Obtém token do header Authorization
     */
    private function getTokenFromHeader(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Obtém usuário autenticado
     */
    public static function error(): ?array
    {
        return $_SERVER['AUTH_USER'] ?? null;
    }

    /**
     * Obtém usuário autenticado
     */
    public static function user(): ?array
    {
        return $_SERVER['AUTH_USER'] ?? null;
    }
    /**
     * Obtém ID do usuário autenticado
     */
    public static function userId(): ?int
    {
        return $_SERVER['AUTH_USER_ID'] ?? null;
    }

    /**
     * Verifica se usuário está autenticado
     */
    public static function check(): bool
    {
        return isset($_SERVER['AUTH_USER']);
    }
}
