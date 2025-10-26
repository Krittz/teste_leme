<?php

declare(strict_types=1);
/**
 * Funções auxiliares globais
 */
if (!function_exists('env')) {
    /**
     * Obtém variável de ambiente
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_EN[$key] ?? $default;
    }
}
if (!function_exists('config')) {
    /**
     * Obtém configuração usando dot notation
     * Exemplo: config('aap.name')
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $config = [
                'app' => require __DIR__ . '/../../config/app.php',
                'database' => require __DIR__ . '/../../config/database.php',
                'jwt' => require __DIR__ . '/../../config/jwt.php',
                'cors' => require __DIR__ . '/../../config/cors.php',
            ];
        }
        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }
}

if (!function_exists('base_path')) {
    /**
     * Retorna o caminho base do projeto
     */
    function base_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);
        return $path ? $basePath . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $basePath;
    }
}

if (!function_exists('storage_path')) {
    /**
     * Retorna o caminho do diretório storage
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Retorna o caminho do diretório public
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') :  ''));
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die - útil para debug
     */
    function dd(mixed ...$vars): never
    {
        header('Content-Type: text/plain; charset=utf-8');

        foreach ($vars as $var) {
            var_dump($var);
        }
        die(1);
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitiza string (proteção XSS)
     */
    function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('sanitize', $value);
        }
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $value;
    }
}

if (!function_exists('now')) {
    /**
     * Retorna a data/hora atual no formato MySQL
     */
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('uuid')) {
    /**
     * Gera um UUID v4
     */
    function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x0f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}


if (!function_exists('abort')) {
    /**
     * Abora a execução com erro HTTP
     */
    function abort(int $code = 404, string $message = ''): never
    {
        http_response_code($code);
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Nod Allowed',
            422 => 'Uncprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
        ];

        echo json_encode([
            'success' => false,
            'message' => $message ?: ($message[$code] ?? 'Error'),
            'errors' => null,
            'meta' => [
                'timestamp' => date('c'),
            ],
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

if (!function_exists('is_valid_email')) {
    /**
     * Valida email
     */
    function is_valid_email(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('slug')) {
    /**
     * Gera slug a partir de string
     */
    function slug(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return $text ?: 'n-a';
    }
}

if (!function_exists('str_random')) {
    /**
     * Gera string aleatória
     */
    function str_random(int $length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}


if (!function_exists('array_get')) {
    /**
     * Obtém valor de array usando dot notation
     */
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        return $array;
    }
}
