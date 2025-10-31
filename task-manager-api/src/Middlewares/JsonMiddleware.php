<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Helpers\ResponseHelper;

/**
 * Middleware JSON
 * 
 * Valida e processa requisiçãoes JSON
 */
class JsonMiddleware
{
    /**
     * Handle do middlware
     */
    public function handle(array $params = []): bool
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return true;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        $contentType = explode(';', $contentType)[0];
        $contentType = trim($contentType);

        if ($contentType !== 'application/json') {
            ResponseHelper::error(
                'Content-Type deve ser application/json',
                ['content_type' => 'Content-Type inválido ou ausente'],
                415
            );
        }

        $body = file_get_contents('php://input');

        if (empty($body)) {
            ResponseHelper::error(
                'Corpo da requisição não pode estar vazio',
                ['body' => 'JSON body obrigatório'],
                400
            );
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            ResponseHelper::error(
                'JSON inválido',
                [
                    'json_error' => json_last_error_msg(),
                    'hint' => 'Verifique a sintaxe do JSON enviado'
                ],
                400
            );
        }

        $_POST = $data;
        $_REQUEST = array_merge($_REQUEST, $data);

        return true;
    }
}
