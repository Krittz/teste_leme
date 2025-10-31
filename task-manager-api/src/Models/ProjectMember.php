<?php

declare(strict_types=1);

namespace App\Models;

/**
 * ProjectMember Model
 * 
 * Model para gerenciar membros dos projetos (compartilhamento)
 */
class ProjectMember extends BaseModel
{
    protected string $table = 'project_members';
    
    protected array $fillable = [
        'project_id',
        'user_id',
        'role'
    ];
    
    protected bool $timestamps = false;
    
    /**
     * Adiciona membro ao projeto
     */
    public function addMember(int $projectId, int $userId, string $role = 'member'): ?int
    {
        // Verifica se já é membro
        if ($this->isMember($projectId, $userId)) {
            return null;
        }
        
        // Valida role
        if (!in_array($role, ['owner', 'member'])) {
            throw new \InvalidArgumentException('Role inválido. Use: owner ou member');
        }
        
        return $this->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role
        ]);
    }
    
    /**
     * Remove membro do projeto
     * Nota: Não permite remover owners
     */
    public function removeMember(int $projectId, int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE project_id = ? AND user_id = ? AND role != 'owner'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$projectId, $userId]);
    }
    
    /**
     * Remove todos os membros de um projeto (usado ao deletar projeto)
     */
    public function removeAllMembers(int $projectId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE project_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$projectId]);
    }
    
    /**
     * Verifica se usuário é membro do projeto
     */
    public function isMember(int $projectId, int $userId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} 
                WHERE project_id = ? AND user_id = ? 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId]);
        
        return (bool) $stmt->fetch();
    }
    
    /**
     * Verifica se usuário é owner do projeto
     */
    public function isOwner(int $projectId, int $userId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} 
                WHERE project_id = ? AND user_id = ? AND role = 'owner'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId]);
        
        return (bool) $stmt->fetch();
    }
    
    /**
     * Lista membros do projeto com informações do usuário
     */
    public function getMembers(int $projectId): array
    {
        $sql = "SELECT pm.*, u.name, u.email, u.created_at as user_created_at
                FROM {$this->table} pm
                INNER JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = ?
                ORDER BY 
                    CASE pm.role 
                        WHEN 'owner' THEN 1 
                        WHEN 'member' THEN 2 
                    END,
                    u.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Lista apenas owners do projeto
     */
    public function getOwners(int $projectId): array
    {
        $sql = "SELECT pm.*, u.name, u.email
                FROM {$this->table} pm
                INNER JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = ? AND pm.role = 'owner'
                ORDER BY u.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Lista projetos de um usuário (como membro)
     */
    public function getProjectsByUser(int $userId): array
    {
        $sql = "SELECT pm.*, p.title, p.description, p.start_date, p.end_date
                FROM {$this->table} pm
                INNER JOIN projects p ON pm.project_id = p.id
                WHERE pm.user_id = ?
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Conta membros do projeto
     */
    public function countMembers(int $projectId): int
    {
        return $this->count('project_id', $projectId);
    }
    
    /**
     * Conta owners do projeto
     */
    public function countOwners(int $projectId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}
                WHERE project_id = ? AND role = 'owner'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId]);
        
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }
    
    /**
     * Conta projetos onde usuário é membro
     */
    public function countProjectsByUser(int $userId): int
    {
        return $this->count('user_id', $userId);
    }
    
    /**
     * Obtém papel (role) do usuário no projeto
     */
    public function getUserRole(int $projectId, int $userId): ?string
    {
        $sql = "SELECT role FROM {$this->table} 
                WHERE project_id = ? AND user_id = ? 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId]);
        
        $result = $stmt->fetch();
        return $result['role'] ?? null;
    }
    
    /**
     * Atualiza papel do usuário no projeto
     */
    public function updateRole(int $projectId, int $userId, string $role): bool
    {
        // Valida role
        if (!in_array($role, ['owner', 'member'])) {
            throw new \InvalidArgumentException('Role inválido. Use: owner ou member');
        }
        
        $sql = "UPDATE {$this->table} 
                SET role = ? 
                WHERE project_id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$role, $projectId, $userId]);
    }
    
    /**
     * Busca membro específico
     */
    public function getMember(int $projectId, int $userId): ?array
    {
        $sql = "SELECT pm.*, u.name, u.email
                FROM {$this->table} pm
                INNER JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = ? AND pm.user_id = ?
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Verifica se há pelo menos um owner no projeto
     */
    public function hasOwner(int $projectId): bool
    {
        return $this->countOwners($projectId) > 0;
    }
    
    /**
     * Promove membro a owner
     */
    public function promoteToOwner(int $projectId, int $userId): bool
    {
        return $this->updateRole($projectId, $userId, 'owner');
    }
    
    /**
     * Rebaixa owner a member
     */
    public function demoteToMember(int $projectId, int $userId): bool
    {
        // Verifica se há mais de um owner
        if ($this->countOwners($projectId) <= 1) {
            throw new \Exception('Não é possível rebaixar o único owner do projeto');
        }
        
        return $this->updateRole($projectId, $userId, 'member');
    }
    
    /**
     * Transfere ownership do projeto
     */
    public function transferOwnership(int $projectId, int $fromUserId, int $toUserId): bool
    {
        // Verifica se o destinatário é membro
        if (!$this->isMember($projectId, $toUserId)) {
            throw new \Exception('Destinatário não é membro do projeto');
        }
        
        $this->beginTransaction();
        
        try {
            // Promove novo owner
            $this->promoteToOwner($projectId, $toUserId);
            
            // Rebaixa antigo owner (se não for o único)
            if ($this->countOwners($projectId) > 1) {
                $this->demoteToMember($projectId, $fromUserId);
            }
            
            $this->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}