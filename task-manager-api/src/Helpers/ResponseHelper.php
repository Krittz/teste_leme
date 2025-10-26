<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper para padronizar respostas JSON da API
 */
class ResponseHelper
{
    /**
     * Envia resposta JSON de sucesso
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operação realizada com sucesso',
        int $statusCode = 200
    ): never {
        http_response_code($statusCode);

        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => self::getMeta(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    /**
     * Envia resposta JSON de erro
     */
    public static function error(string $message = 'Ocorreu um erro', mixed $errors = null, int $statusCode = 400): never
    {
        http_response_code($statusCode);

        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'meta' => self::getMeta(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envia resposta de validação com erros
     */
    public static function validationError(array $errors, string $message = 'Erro de validação'): never
    {
        self::error($message, $errors, 422);
    }

    /**
     * Envia resposta de não autorizado
     */
    public static function unauthorized(string $message = 'Não autorizado'): never
    {
        self::error($message, null, 401);
    }

    /**
     * Envia repsosta de proibido
     */
    public function forbidden(string $message = 'Acesso negado'): never
    {
        self::error($message, null, 403);
    }
    /**
     * Envia resposta de não encontrado
     */
    public static function notFound(string $message = 'Recurso não encontrado'): never
    {
        self::error($message, null, 404);
    }

    /**
     * Envia resposta de erro interno
     */
    public static function internalError(string $message = 'Erro interno do servidor', mixed $errors = null): never
    {
        self::error($message, $errors, 500);
    }

    /**
     * Envia resposta de criação com sucesso
     */
    public static function created(mixed $data = null, string $message = 'Recurso criado com sucesso'): never
    {
        self::success($data, $message, 201);
    }
    /**
     * Envia resposta sem conteúdo
     */
    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }
}
