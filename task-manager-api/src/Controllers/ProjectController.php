<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Services\FileUploadService;

/**
 * Project Controller
 * 
 * Gerencia operações de projetos
 */
class ProjectController extends BaseController
{
    private Project $projectModel;
    private ProjectMember $memberModel;
    private Task $taskModel;
    private User $userModel;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->projectModel = new Project();
        $this->memberModel = new ProjectMember();
        $this->taskModel = new Task();
        $this->userModel = new User();
    }

    /**
     * Lista projetos do usuário
     * GET /api/projects
     */
    public function index(array $params): void
    {
        $userId = $this->userId();

        // Busca filtros
        $filters = [
            'search' => $this->query('search'),
            'start_date_from' => $this->query('start_date_from'),
            'end_date_to' => $this->query('end_date_to'),
        ];

        $projects = $this->projectModel->search($userId, array_filter($filters));

        $this->success(['projects' => $projects]);
    }

    /**
     * Cria novo projeto
     * POST /api/projects
     */
    public function store(array $params): void
    {
        $this->validateRequired(['title', 'start_date', 'end_date']);

        $title = $this->getInput('title');
        $description = $this->getInput('description');
        $startDate = $this->getInput('start_date');
        $endDate = $this->getInput('end_date');

        // Validações
        $this->validateMinLength($title, 3, 'title');
        $this->validateMaxLength($title, 200, 'title');
        $this->validateDate($startDate, 'start_date');
        $this->validateDate($endDate, 'end_date');

        // Valida que end_date >= start_date
        if ($endDate < $startDate) {
            $this->error('Data de término deve ser maior ou igual à data de início', [
                'end_date' => 'Data inválida'
            ], 400);
        }

        $projectId = $this->projectModel->create([
            'user_id' => $this->userId(),
            'title' => $title,
            'description' => $description,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        if (!$projectId) {
            $this->error('Erro ao criar projeto', null, 500);
        }

        $project = $this->projectModel->find($projectId);

        $this->created(['project' => $project], 'Projeto criado com sucesso');
    }

    /**
     * Detalhes de um projeto
     * GET /api/projects/{id}
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        // Verifica acesso
        if (!$this->projectModel->userHasAccess($id, $userId)) {
            $this->error('Acesso negado', null, 403);
        }

        $project = $this->projectModel->find($id);

        if (!$project) {
            $this->error('Projeto não encontrado', null, 404);
        }

        // Adiciona membros e estatísticas
        $project['members'] = $this->memberModel->getMembers($id);
        $project['members_count'] = $this->memberModel->countMembers($id);
        $project['tasks_count'] = $this->taskModel->count('project_id', $id);

        $this->success(['project' => $project]);
    }

    /**
     * Atualiza projeto
     * PUT /api/projects/{id}
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        // Apenas o dono pode atualizar
        if (!$this->projectModel->userIsOwner($id, $userId)) {
            $this->error('Apenas o dono do projeto pode atualizá-lo', null, 403);
        }

        $project = $this->projectModel->find($id);

        if (!$project) {
            $this->error('Projeto não encontrado', null, 404);
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

        $startDate = $this->getInput('start_date');
        if ($startDate !== null) {
            $this->validateDate($startDate, 'start_date');
            $updateData['start_date'] = $startDate;
        }

        $endDate = $this->getInput('end_date');
        if ($endDate !== null) {
            $this->validateDate($endDate, 'end_date');
            $updateData['end_date'] = $endDate;
        }

        if (empty($updateData)) {
            $this->error('Nenhum dado para atualizar', null, 400);
        }

        $this->projectModel->update($id, $updateData);

        $updated = $this->projectModel->find($id);

        $this->success(['project' => $updated], 'Projeto atualizado com sucesso');
    }

    /**
     * Deleta projeto
     * DELETE /api/projects/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        // Apenas o dono pode deletar
        if (!$this->projectModel->userIsOwner($id, $userId)) {
            $this->error('Apenas o dono do projeto pode excluí-lo', null, 403);
        }

        if (!$this->projectModel->exists($id)) {
            $this->error('Projeto não encontrado', null, 404);
        }

        $this->projectModel->delete($id);

        $this->success(null, 'Projeto excluído com sucesso');
    }

    /**
     * Adiciona membro ao projeto
     * POST /api/projects/{id}/members
     */
    public function addMember(array $params): void
    {
        $projectId = (int) $params['id'];
        $userId = $this->userId();

        $this->validateRequired(['user_id']);

        $newMemberId = (int) $this->getInput('user_id');

        // Apenas o dono pode adicionar membros
        if (!$this->projectModel->userIsOwner($projectId, $userId)) {
            $this->error('Apenas o dono do projeto pode adicionar membros', null, 403);
        }
        // Verifica se o projeto existe
        if (!$this->projectModel->exists($projectId)) {
            $this->error('Projeto não encontrado', null, 404);
        }

        // Verifica se o usuário existe
        if (!$this->userModel->exists($newMemberId)) {
            $this->error('Usuário não encontrado', null, 404);
        }
        // Verifica se já é membro
        if ($this->memberModel->isMember($projectId, $newMemberId)) {
            $this->error('Usuário já é membro deste projeto', null, 400);
        }

        $this->memberModel->addMember($projectId, $newMemberId, 'member');

        $this->created(null, 'Membro adicionado com sucesso');
    }

    /**
     * Remove membro do projeto
     * DELETE /api/projects/{id}/members/{userId}
     */
    public function removeMember(array $params): void
    {
        $projectId = (int) $params['id'];
        $memberUserId = (int) $params['userId'];
        $userId = $this->userId();

        // Apenas o dono pode remover membros
        if (!$this->projectModel->userIsOwner($projectId, $userId)) {
            $this->error('Apenas o dono do projeto pode remover membros', null, 403);
        }

        // Não pode remover o próprio dono
        if ($memberUserId === $userId) {
            $this->error('Não é possível remover o dono do projeto', null, 400);
        }

        $this->memberModel->removeMember($projectId, $memberUserId);

        $this->success(null, 'Membro removido com sucesso');
    }

    /**
     * Lista membros do projeto
     * GET /api/projects/{id}/members
     */
    public function getMembers(array $params): void
    {
        $projectId = (int) $params['id'];
        $userId = $this->userId();

        // Verifica acesso
        if (!$this->projectModel->userHasAccess($projectId, $userId)) {
            $this->error('Acesso negado', null, 403);
        }

        $members = $this->memberModel->getMembers($projectId);

        $this->success(['members' => $members]);
    }

    /**
     * Lista tarefas do projeto
     * GET /api/projects/{id}/tasks
     */
    public function getTasks(array $params): void
    {
        $projectId = (int) $params['id'];
        $userId = $this->userId();

        // Verifica acesso
        if (!$this->projectModel->userHasAccess($projectId, $userId)) {
            $this->error('Acesso negado', null, 403);
        }

        $tasks = $this->taskModel->findByProject($projectId);

        $this->success(['tasks' => $tasks]);
    }

    /**
     * Upload de arquivo para projeto
     * POST /api/upload/project
     */
    public function uploadFile(array $params): void
    {
        $this->validateRequired(['project_id']);

        $projectId = (int) $this->getInput('project_id');
        $userId = $this->userId();

        // Verifica se é dono do projeto
        if (!$this->projectModel->userIsOwner($projectId, $userId)) {
            $this->error('Apenas o dono pode fazer upload de arquivos', null, 403);
        }

        if (!isset($_FILES['file'])) {
            $this->error('Nenhum arquivo enviado', null, 400);
        }

        try {
            $uploadService = new FileUploadService();
            $filePath = $uploadService->uploadProjectFile($_FILES['file']);

            // Atualiza projeto com caminho do arquivo
            $this->projectModel->update($projectId, [
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
