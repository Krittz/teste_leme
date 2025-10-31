<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Services\FileUploadService;

/**
 * Task Controller
 * 
 * Gerencia operações de tarefas
 */
class TaskController extends BaseController
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
     * Lista tarefas do usuário
     * GET /api/tasks
     */
    public function index(array $params): void
    {
        $userId = $this->userId();

        // Busca filtros da query string
        $filters = [
            'status' => $this->query('status'),
            'priority' => $this->query('priority'),
            'project_id' => $this->query('project_id'),
        ];

        // Remove filtros vazios
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        $tasks = $this->taskModel->findByUser($userId, $filters);

        $this->success(['tasks' => $tasks]);
    }

    /**
     * Cria nova tarefa
     * POST /api/tasks
     */
    public function store(array $params): void
    {
        $this->validateRequired(['title', 'due_date', 'priority']);

        $projectId = $this->getInput('project_id');
        $title = $this->getInput('title');
        $description = $this->getInput('description');
        $dueDate = $this->getInput('due_date');
        $priority = $this->getInput('priority');
        $status = $this->getInput('status', 'pending');

        $userId = $this->userId();

        // Validações
        $this->validateMinLength($title, 3, 'title');
        $this->validateMaxLength($title, 200, 'title');
        $this->validateDate($dueDate, 'due_date');
        $this->validateEnum($priority, ['low', 'medium', 'high'], 'priority');
        $this->validateEnum($status, ['pending', 'in_progress', 'completed'], 'status');

        // Se vinculado a projeto, verifica acesso
        if ($projectId !== null) {
            $projectId = (int) $projectId;

            if (!$this->projectModel->userHasAccess($projectId, $userId)) {
                $this->error('Você não tem acesso a este projeto', null, 403);
            }
        }

        $taskId = $this->taskModel->create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'priority' => $priority,
            'status' => $status
        ]);

        if (!$taskId) {
            $this->error('Erro ao criar tarefa', null, 500);
        }

        $task = $this->taskModel->find($taskId);

        $this->created(['task' => $task], 'Tarefa criada com sucesso');
    }

    /**
     * Detalhes de uma tarefa
     * GET /api/tasks/{id}
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        $task = $this->taskModel->find($id);

        if (!$task) {
            $this->error('Tarefa não encontrada', null, 404);
        }

        // Verifica acesso
        if (!$this->taskModel->userHasAccess($id, $userId)) {
            $this->error('Acesso negado', null, 403);
        }

        $this->success(['task' => $task]);
    }

    /**
     * Atualiza tarefa
     * PUT /api/tasks/{id}
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        $task = $this->taskModel->find($id);

        if (!$task) {
            $this->error('Tarefa não encontrada', null, 404);
        }

        // Verifica acesso
        if (!$this->taskModel->userHasAccess($id, $userId)) {
            $this->error('Acesso negado', null, 403);
        }

        $updateData = [];

        $title = $this->getInput('title');
        if ($title !== null) {
            $this->validateMinLength($title, 3, 'title');
            $this->validateMaxLength($title, 200, 'title');
            $updateData['title'] = $title;
        }

        $description = $this->getInput('description');
        if ($description !== null) {
            $updateData['description'] = $description;
        }

        $dueDate = $this->getInput('due_date');
        if ($dueDate !== null) {
            $this->validateDate($dueDate, 'due_date');
            $updateData['due_date'] = $dueDate;
        }

        $priority = $this->getInput('priority');
        if ($priority !== null) {
            $this->validateEnum($priority, ['low', 'medium', 'high'], 'priority');
            $updateData['priority'] = $priority;
        }

        $status = $this->getInput('status');
        if ($status !== null) {
            $this->validateEnum($status, ['pending', 'in_progress', 'completed'], 'status');
            $updateData['status'] = $status;

            // Se marcar como concluída, adiciona completed_at
            if ($status === 'completed') {
                $updateData['completed_at'] = now();
            } elseif ($task['status'] === 'completed' && $status !== 'completed') {
                $updateData['completed_at'] = null;
            }
        }

        if (empty($updateData)) {
            $this->error('Nenhum dado para atualizar', null, 400);
        }

        $this->taskModel->update($id, $updateData);

        $updated = $this->taskModel->find($id);

        $this->success(['task' => $updated], 'Tarefa atualizada com sucesso');
    }

    /**
     * Deleta tarefa
     * DELETE /api/tasks/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        $task = $this->taskModel->find($id);

        if (!$task) {
            $this->error('Tarefa não encontrada', null, 404);
        }

        // Apenas o responsável pode deletar
        if ($task['user_id'] !== $userId) {
            $this->error('Apenas o responsável pela tarefa pode excluí-la', null, 403);
        }

        $this->taskModel->delete($id);

        $this->success(null, 'Tarefa excluída com sucesso');
    }

    /**
     * Marca tarefa como concluída
     * PATCH /api/tasks/{id}/complete
     */
    public function markAsCompleted(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        $task = $this->taskModel->find($id);

        if (!$task) {
            $this->error('Tarefa não encontrada', null, 404);
        }

        // Verifica acesso
        if (!$this->taskModel->userHasAccess($id, $userId)) {
            $this->error('Acesso negado', null, 403);
        }

        if ($task['status'] === 'completed') {
            $this->error('Tarefa já está concluída', null, 400);
        }

        $this->taskModel->markAsCompleted($id);

        $updated = $this->taskModel->find($id);

        $this->success(['task' => $updated], 'Tarefa marcada como concluída');
    }

    /**
     * Upload de arquivo para tarefa
     * POST /api/upload/task
     */
    public function uploadFile(array $params): void
    {
        $this->validateRequired(['task_id']);

        $taskId = (int) $this->getInput('task_id');
        $userId = $this->userId();

        $task = $this->taskModel->find($taskId);

        if (!$task) {
            $this->error('Tarefa não encontrada', null, 404);
        }

        // Verifica acesso
        if (!$this->taskModel->userHasAccess($taskId, $userId)) {
            $this->error('Acesso negado', null, 403);
        }

        if (!isset($_FILES['file'])) {
            $this->error('Nenhum arquivo enviado', null, 400);
        }

        try {
            $uploadService = new FileUploadService();
            $filePath = $uploadService->uploadTaskFile($_FILES['file']);

            // Atualiza tarefa com caminho do arquivo
            $this->taskModel->update($taskId, [
                'attachment_path' => $filePath
            ]);

            $this->success([
                'file_path' => $filePath
            ], 'Arquivo enviado com sucesso');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), null, 400);
        }
    }
}
