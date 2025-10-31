<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Task;
use App\Models\Project;

/**
 * Dashboard Controller
 * 
 * Fornece dados para o dashboard do usuário
 */
class DashboardController extends BaseController
{
    private Task $taskModel;
    private Project $projectModel;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->taskModel = new Task();
        $this->projectModel = new Project();
    }

    /**
     * Resumo do dashboard
     * GET /api/dashboard/summary
     */
    public function summary(array $params): void
    {
        $userId = $this->userId();

        // Tarefas pendentes
        $pendingTasks = $this->taskModel->findPending($userId);

        // Tarefas atrasadas
        $overdueTasks = $this->taskModel->findOverdue($userId);

        // Contadores
        $summary = [
            'pending_count' => count($pendingTasks),
            'overdue_count' => count($overdueTasks),
            'pending_tasks' => array_slice($pendingTasks, 0, 5), // Últimas 5
            'overdue_tasks' => array_slice($overdueTasks, 0, 5), // Últimas 5
        ];

        $this->success($summary);
    }

    /**
     * Estatísticas gerais
     * GET /api/dashboard/stats
     */
    public function stats(array $params): void
    {
        $userId = $this->userId();

        // Estatísticas de tarefas
        $taskStats = $this->taskModel->getStats($userId);

        // Contagem de projetos
        $projectCount = $this->projectModel->countByUser($userId);

        // Projetos do usuário (todos - criados + membro)
        $allProjects = $this->projectModel->findAllByUser($userId);
        $totalProjects = count($allProjects);

        // Tarefas por prioridade
        $priorityDistribution = [
            'high' => (int) ($taskStats['high_priority'] ?? 0),
            'medium' => (int) ($taskStats['medium_priority'] ?? 0),
            'low' => (int) ($taskStats['low_priority'] ?? 0),
        ];

        // Tarefas por status
        $statusDistribution = [
            'pending' => (int) ($taskStats['pending'] ?? 0),
            'in_progress' => (int) ($taskStats['in_progress'] ?? 0),
            'completed' => (int) ($taskStats['completed'] ?? 0),
        ];

        // Taxa de conclusão
        $totalTasks = (int) ($taskStats['total'] ?? 0);
        $completedTasks = (int) ($taskStats['completed'] ?? 0);
        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 2)
            : 0;

        $stats = [
            'tasks' => [
                'total' => $totalTasks,
                'pending' => (int) ($taskStats['pending'] ?? 0),
                'in_progress' => (int) ($taskStats['in_progress'] ?? 0),
                'completed' => $completedTasks,
                'overdue' => (int) ($taskStats['overdue'] ?? 0),
                'completion_rate' => $completionRate,
            ],
            'projects' => [
                'owned' => $projectCount,
                'total' => $totalProjects,
            ],
            'priority_distribution' => $priorityDistribution,
            'status_distribution' => $statusDistribution,
        ];

        $this->success($stats);
    }
}
