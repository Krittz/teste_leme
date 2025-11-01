<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Task Model
 * 
 * Model para gerenciar tarefas
 */
class Task extends BaseModel
{
    protected string $table = 'tasks';

    protected array $fillable = [
        'project_id',
        'user_id',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'attachment_path'
    ];

    /**
     * Busca tarefas do usuário com filtros
     */
    public function findByUser(int $userId, array $filters = []): array
    {
        $sql = "SELECT t.* FROM {$this->table} t
                WHERE t.user_id = ?";

        $params = [$userId];

        // Filtro por status
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }

        // Filtro por prioridade
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Filtro por projeto
        if (isset($filters['project_id'])) {
            if ($filters['project_id'] === 'null' || $filters['project_id'] === null) {
                $sql .= " AND t.project_id IS NULL";
            } else {
                $sql .= " AND t.project_id = ?";
                $params[] = (int)$filters['project_id'];
            }
        }

        // Ordenação por prioridade e data
        $sql .= " ORDER BY 
                  CASE t.priority 
                    WHEN 'high' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 3 
                  END,
                  t.due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Busca tarefas de um projeto específico
     */
    public function findByProject(int $projectId): array
    {
        $sql = "SELECT t.*, u.name as user_name, u.email as user_email
                FROM {$this->table} t
                INNER JOIN users u ON t.user_id = u.id
                WHERE t.project_id = ?
                ORDER BY t.due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca tarefas pendentes do usuário
     */
    public function findPending(int $userId): array
    {
        return $this->findByUser($userId, ['status' => 'pending']);
    }

    /**
     * Busca tarefas em progresso do usuário
     */
    public function findInProgress(int $userId): array
    {
        return $this->findByUser($userId, ['status' => 'in_progress']);
    }

    /**
     * Busca tarefas atrasadas do usuário
     */
    public function findOverdue(int $userId): array
    {
        $sql = "SELECT t.* FROM {$this->table} t
                WHERE t.user_id = ? 
                AND t.status != 'completed'
                AND t.due_date < CURDATE()
                ORDER BY t.due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Busca tarefas concluídas do usuário
     */
    public function findCompleted(int $userId): array
    {
        return $this->findByUser($userId, ['status' => 'completed']);
    }

    /**
     * Marca tarefa como concluída
     */
    public function markAsCompleted(int $id): bool
    {
        return $this->update($id, [
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Desmarca tarefa como concluída
     */
    public function markAsIncomplete(int $id): bool
    {
        return $this->update($id, [
            'status' => 'pending',
            'completed_at' => null
        ]);
    }

    /**
     * Conta tarefas por status
     */
    public function countByStatus(int $userId, string $status): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}
                WHERE user_id = ? AND status = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $status]);

        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Conta tarefas por prioridade
     */
    public function countByPriority(int $userId, string $priority): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}
                WHERE user_id = ? AND priority = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $priority]);

        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Conta tarefas atrasadas
     */
    public function countOverdue(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}
                WHERE user_id = ? 
                AND status != 'completed'
                AND due_date < CURDATE()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Estatísticas completas de tarefas do usuário
     */
    public function getStats(int $userId): array
    {
        $sql = "SELECT 
                COUNT(*) as total,
                COALESCE(SUM(status = 'pending'), 0) as pending,
                COALESCE(SUM(status = 'in_progress'), 0) as in_progress,
                COALESCE(SUM(status = 'completed'), 0) as completed,
                COALESCE(SUM(status != 'completed' AND due_date < CURDATE()), 0) as overdue,
                COALESCE(SUM(priority = 'high'), 0) as `high_priority`,
                COALESCE(SUM(priority = 'medium'), 0) as medium_priority,
                COALESCE(SUM(priority = 'low'), 0) as `low_priority`
            FROM {$this->table}
            WHERE user_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($result['total'] ?? 0),
            'pending' => (int) ($result['pending'] ?? 0),
            'in_progress' => (int) ($result['in_progress'] ?? 0),
            'completed' => (int) ($result['completed'] ?? 0),
            'overdue' => (int) ($result['overdue'] ?? 0),
            'high_priority' => (int) ($result['high_priority'] ?? 0),
            'medium_priority' => (int) ($result['medium_priority'] ?? 0),
            'low_priority' => (int) ($result['low_priority'] ?? 0),
        ];
    }
    /**
     * Verifica se usuário tem acesso à tarefa
     * (criador da tarefa ou membro do projeto)
     */
    public function userHasAccess(int $taskId, int $userId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN project_members pm ON t.project_id = pm.project_id
                WHERE t.id = ? AND (
                    t.user_id = ? OR 
                    p.user_id = ? OR 
                    pm.user_id = ?
                )
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$taskId, $userId, $userId, $userId]);

        return (bool) $stmt->fetch();
    }

    /**
     * Verifica se usuário é o responsável pela tarefa
     */
    public function userIsOwner(int $taskId, int $userId): bool
    {
        $task = $this->find($taskId);
        return $task && $task['user_id'] === $userId;
    }

    /**
     * Busca tarefas com vencimento próximo (próximos 7 dias)
     */
    public function findUpcoming(int $userId, int $days = 7): array
    {
        $sql = "SELECT t.* FROM {$this->table} t
                WHERE t.user_id = ? 
                AND t.status != 'completed'
                AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY t.due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $days]);

        return $stmt->fetchAll();
    }

    /**
     * Conta total de tarefas do usuário
     */
    public function countByUser(int $userId): int
    {
        return $this->count('user_id', $userId);
    }

    /**
     * Conta tarefas de um projeto
     */
    public function countByProject(int $projectId): int
    {
        return $this->count('project_id', $projectId);
    }
}
