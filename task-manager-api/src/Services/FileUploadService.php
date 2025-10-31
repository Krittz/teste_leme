<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\SecurityHelper;

/**
 * File Upload Service
 * 
 * Serviço pra gerenciar upload de arquivos
 */
class FileUploadService
{
    private string $uploadPath;
    private int $maxSize;
    private array $allowedExtensions;

    public function __construct()
    {
        $this->uploadPath = config('app.upload.path', 'public/uploads');
        $this->maxSize = config('app.upload.max_size', 10485760); //10MB
        $this->allowedExtensions = config('app.upload.allowed_extensions', ['pdf', 'jpg', 'jpeg', 'png']);

        // Cria diretórios se não existirem
        $this->ensureDirectoryExists($this->uploadPath . '/projects');
        $this->ensureDirectoryExists($this->uploadPath . '/tasks');
    }

    /**
     * Upload de arquivo de projeto
     */
    public function uploadProjectFile(array $file): string
    {
        return $this->upload($file, 'projects');
    }

    /**
     * Processa upload de arquivo
     */
    private function upload(array $file, string $type): string
    {
        // Valida se arquiv foi enviado
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('Nenhum arquio válido foi enviado');
        }

        // Verifica erros de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception($this->getUploadError($file['error']));
        }

        // Valida extensão
        $originalName = $file['name'];
        if (!SecurityHelper::isAllowedExtension($originalName)) {
            $allowed = implode(', ', $this->allowedExtensions);
            throw new \Exception("Extensão não permitida. Permitidos: {$allowed}");
        }

        // Gera nome seguro
        $safeName = SecurityHelper::generateSafeFilename($originalName);

        // Define caminho completo
        $directory = "{$this->uploadPath}/{$type}";
        $fullPath = "{$directory}/{$safeName}";

        // Move arquivo
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \Exception('Erro ao salvar arquivo');
        }

        // Valida tipo MIME real
        if (!SecurityHelper::validateMimeType($fullPath)) {
            unlink($fullPath);
            throw new \Exception('Tipo de arquivo não permitido');
        }

        // Retorna caminho relativo
        return "uploads/{$type}/{$safeName}";
    }

    /**
     * Delete arquivo
     */
    public function deleteFile(string $path): bool
    {
        $fullPath = public_path($path);

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    /**
     * Garante que diretório existe
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Retorna mensagem de erro de upload
     */
    private function getUploadError(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporario não encontrado',
            UPLOAD_ERR_CANT_WRITE => 'Upload bloqueado por extensão',
            default => 'Erro desconhecido no upload'
        };
    }

    /**
     * Obtém informações do arquivo
     */
    public function getFileInfo(string $path): ?array
    {
        $fullPath = public_path($path);

        if (!file_exists($fullPath)) {
            return null;
        }

        return [
            'name' => basename($path),
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath),
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
            'url' => config('app.url') . '/' . $path,
        ];
    }
}
