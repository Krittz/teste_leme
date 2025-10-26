<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Helpers\ResponseHelper;
use App\Helpers\SecurityHelper;

/**
 * Middlewre de Rate Limiting
 * 
 * Limita número de requisições por IP/usuário
 */
class RateLimitMiddleware
{
    private string $storageFile;
    private int $maxAttempts;
    private int $window;

    public function __construct()
    {
        $this->storageFile = storage_path('rate_limit.json');
        $this->maxAttempts = config('app.security.rate_limit.max_attempts', 5);
        $this->window = config('app.security.rate_limit.window', 300);

        if (!file_exists($this->storageFile)) {
            $dir = dirname($this->storageFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($this->storageFile, json_encode([]));
        }
    }

    /**
     * Handle do middlware
     */
    public function handle(array $params = []): bool
    {
        $identifier = $this->getIdentifier();
        $attempts = $this->getAttempts($identifier);

        $attempts = $this->cleanOldAttempts($attempts);

        if (count($attempts) >= $this->maxAttempts) {
            $retryAfter = $this->window - (time() - min($attempts));

            header("Retry-After: {$retryAfter}");
            header("X-RateLimit-Limit: {$this->maxAttempts}");
            header("X-RateLimit-Remaining: 0");
            header("X-RateLimit-Reset: " . (time() + $retryAfter));

            ResponseHelper::error(
                'Muitas requisições. Tente novamente mais tarde.',
                [
                    'retry_after' => $retryAfter,
                    'message' => "Aguarde {$retryAfter} segundos antes de tentar novamente"
                ],
                429
            );
        }

        $this->recordAttempt($identifier, $attempts);
        $remaining = $this->maxAttempts - count($attempts) - 1;
        header("X-RateLimit-Limit: {$this->maxAttempts}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: " . (time() + $this->window));

        return true;
    }

    /**
     * Obtém identificador único (IP ou user_id)
     */
    private function getIdentifier(): string
    {
        $userId = $_SERVER['AUTH_USER_ID'] ?? null;
        if ($userId) {
            return "user_{$userId}";
        }

        $ip = SecurityHelper::getClientIp();
        return "ip_{$ip}";
    }

    /**
     * Obtém tentativas do identificador
     */
    private function getAttempts(string $identifier): array
    {
        $data = $this->loadData();
        return $data[$identifier] ?? [];
    }

    /**
     * Remove tentativas antigas (fora da janela)
     */
    private function cleanOldAttempts(array $attempts): array
    {
        $cutoff = time() - $this->window;
        return array_filter($attempts, fn($timestamp) => $timestamp > $cutoff);
    }

    /**
     * Registra nova tentativa
     */
    private function recordAttempt(string $identifier, array $attempts): void
    {
        $attempts[] = time();
        $data = $this->loadData();

        $this->saveData($data);
    }

    /**
     * Carrega dados do arquivo
     */
    private function loadData(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }
        $content = file_get_contents($this->storageFile);
        return json_decode($content, true) ?? [];
    }

    /**
     * Salva dados no arquivo
     */
    private function saveData(array $data): void
    {
        $cutoff = time() - ($this->window * 2);
        foreach ($data as $key => $attempts) {
            $data[$key] = array_filter($attempts, fn($timestamp) => $timestamp > $cutoff);
            if (empty($data[$key])) {
                unset($data[$key]);
            }
        }
        file_put_contents($this->storageFile, json_encode($data));
    }

    /**
     * Reseta tentativas para um identificador
     */

    public static function reset(string $identifier): void
    {
        $storageFile = storage_path('rate_limit.json');

        if (!file_exists($storageFile)) {
            return;
        }
        $content = file_get_contents($storageFile);
        $data = json_decode($content, true) ?? [];

        unset($data[$identifier]);

        file_put_contents($storageFile, json_encode($data));
    }
}
