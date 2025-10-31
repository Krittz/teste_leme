<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;
use App\Middlewares\AuthMiddleware;

/**
 * Base Controller
 * 
 * Controller base com métodos auxiliares
 */
abstract class BaseController
{
    /**
     * Configurações da aplicação
     */
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Obtpem dados da requiição (POST/PUT/PATCH)
     */
    protected function getInput(string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST ?? [];
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Obtém todos os dados da requisição
     */
    protected function all(): array
    {
        return $_POST ?? [];
    }

    /**
     * Obtpem apenas campos específicos
     */
    protected function only(array $keys): array
    {
        $data = $this->all();
        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * Ontpem query parameters
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        if ($key ===  null) {
            return $_GET ?? [];
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Obtém usuário autenticado
     */
    protected function user(): ?array
    {
        return AuthMiddleware::user();
    }

    /**
     * Obtém ID do usuário autenticado
     */
    protected function userId(): ?int
    {
        return AuthMiddleware::userId();
    }

    /**
     * Verifica se usuário está autenticado
     */
    protected function isAuthenticated(): bool
    {
        return AuthMiddleware::check();
    }

    /**
     * Valida campos obrigatórios
     */
    protected function validateRequired(array $fields): void
    {
        $data = $this->all();
        $missing = [];

        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            ResponseHelper::validationError([
                'missing_fields' => $missing,
                'message' => 'Campos obrigatórios ausentes: ' . implode(', ', $missing)
            ]);
        }
    }

    /**
     * Valida email
     */ protected function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ResponseHelper::validationError([
                'email' => 'Email inválido'
            ]);
        }
    }

    /**
     * Valida tamanho mínimo de string
     */
    protected function validateMinLength(string $value, int $min, string $field): void
    {
        if (strlen($value) < $min) {
            ResponseHelper::validationError([
                $field => "O campo {$field} deve ter no mínimo {$min} caracteres"
            ]);
        }
    }

    /**
     * Valida tamanho máximo de string
     */
    protected function validateMaxLength(string $value, int $max, string $field): void
    {
        if (strlen($value) > $max) {
            ResponseHelper::validationError([
                $field => "O campo {$field} deve ter no máximo {$max} caracteres"
            ]);
        }
    }

    /**
     * Valida data
     */
    protected function validateDate(string $date, string $field = 'data'): void
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            ResponseHelper::validationError([
                $field => 'Data inválida. Use o formato YYYY-MM-DD'
            ]);
        }
    }

    /**
     * Valida enum
     */
    protected function validateEnum(string $value, array $allowed, string $field): void
    {
        if (!in_array($value, $allowed, true)) {
            ResponseHelper::validationError([
                $field => "Valor inválido para {$field}. Valores permitidos: " . implode(', ', $allowed)
            ]);
        }
    }

    /**
     * Resposta de sucesso
     */
    protected function success(mixed $data = null, string $message = 'Operação realizada com sucesso', int $code = 200): never
    {
        ResponseHelper::success($data, $message, $code);
    }

    /**
     * Resposta de erro
     */
    protected function error(string $message, mixed $errors = null, int $code = 400): never
    {
        ResponseHelper::error($message, $errors, $code);
    }

    /**
     * Resposta de recurso criado
     */
    protected function created(mixed $data = null, string $message = 'Recurso criado com sucesso'): never
    {
        ResponseHelper::created($data, $message);
    }

    /**
     * Resposta sem conteúdo
     */
    protected function noContent(): never
    {
        ResponseHelper::noContent();
    }
}
