<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Project Model
 * 
 * Model para gerenciar projetos
 */
class Project extends BaseModel
{
    protected string $table = 'projects';

    protected array $fillable = [
        'user_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'attachment_path'
    ];

    /**
     * Busca projetos do usuário
     */
    public function findByUser(int $userId): array
    {
        $sql = "SELECT p.* FROM {$this->table} p
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca todos os projetos do usuário (criados + membros)
     */
    public function findAllByUser(int $userId): array
    {
        $sql = "SELECT DISTINCT p.*,
                    CASE WHEN p.user_id = ? THEN 'owner' ELSE 'member' END AS user_role 
                FROM {$this->table} p
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE p.user_id = ? OR pm.user_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Verifica se usuário tem acesso ao projeto
     */
    public function userHasAccess(int $projectId, int $userId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} p
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE p.id = ? AND (p.user_id = ? OR pm.user_id = ?)
                LIMIT 1";


        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId, $userId]);

        return (bool) $stmt->fetch();
    }

    /**
     * Verifica se usuário é dono do projeto
     */
    public function userIsOwner(int $projectId, int $userId): bool
    {
        return (($this->where('id', $projectId)['user_id'] ?? 0) === $userId);
    }

    /**
     * Conta projetos do usuário
     */
    public function countByUser(int $userId): int
    {
        return $this->count('user_id', $userId);
    }

    /**
     * Busca projetos com filtros
     */
    public function search(int $userId, array $filters = []): array
    {
        $sql = "SELECT DISTINCT p.*
                FROM {$this->table} p
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE (p.user_id = ? OR pm.user_id = ?)";
        $params = [$userId, $userId];

        // Fitlro por data de início
        if (!empty($filters['start_date_from'])) {
            $sql .= " AND p.start_date >= ?";
            $params[] = $filters['start_date_from'];
        }

        //Filtro por data de fim
        if (!empty($filters['end_date_to'])) {
            $sql .= " AND p.end_date <= ?";
            $params[] = $filters['end_date_to'];
        }

        // Busca por título
        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
