<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User Model
 * 
 * Model para gerenciar usuários
 */
class User extends BaseModel
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password_hash'
    ];

    /**
     * Busca usuário por email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email);
    }
    /**
     * Verifica se email já existe
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);

        return (bool) $stmt->fetch();
    }

    /**
     * Busca usuários para adicionar em projetos (exlui o próprio usuário)
     */
    public function getAvaibleUsers(int $excludeUserId): array
    {
        $sql = "SELECT id, name, email FROM {$this->table} WHERE id != ? ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$excludeUserId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca múltiplos usuários por IDs
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, name, email FROM {$this->table} WHERE id INT ({$placeholders})";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);

        return $stmt->fetchAll();
    }

    /**
     * Atualiza último login
     */
    public function updateLastLogin(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET last_login = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([now(), $id]);
    }

    /**
     * Busca usuário sem o campo password_hash
     */
    public function findSafe(int $id): ?array
    {
        return $this->find($id, ['id', 'name', 'email', 'created_at', 'updated_at']);
    }
}
