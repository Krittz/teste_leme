<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

/**
 * User Controller
 * 
 * Gerencia operações de usuários
 */
class UserController extends BaseController
{
    private User $userModel;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->userModel = new User();
    }

    /**
     * Lista usuários (para adicionar em projetos)
     * GET /api/users
     */
    public function index(array $params): void
    {
        $userId = $this->userId();

        //Retorna usuários excluindo o próprio
        $users = $this->userModel->getAvaibleUsers($userId);
        $this->success(['users' => $users]);
    }

    /**
     * Detalhes de um usuário
     * GET /api/users/{id}
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];

        $user = $this->userModel->findSafe($id);

        if (!$user) {
            $this->error('Usuário não encontrado', null, 404);
        }

        $this->success(['user' => $user]);
    }

    /**
     * Atualiza perfil do usuário
     * PUT /api/users/{id}
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $userId = $this->userId();

        // Usuário só pode atualizar próprio perfil
        if ($id !== $userId) {
            $this->error('Você não tem permissão para atualizar este usuário', null, 403);
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            $this->error('Usuário não encontrado', null, 404);
        }

        // Campos permitidos para atualização
        $name = $this->getInput('name');
        $email = $this->getInput('email');

        $updateData = [];

        if ($name !== null) {
            $this->validateMinLength($name, 3, 'name');
            $this->validateMaxLength($name, 100, 'name');
            $updateData['name'] = $name;
        }

        if ($email !== null) {
            $this->validateEmail($email);

            // Verifica se email já está em uso por outro usuário
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser && $existingUser['id'] !== $id) {
                $this->error('Email já está em uso', ['email' => 'Email já cadastrado'], 400);
            }
            $updateData['email'] = $email;
        }

        if (empty($updateData)) {
            $this->error('Nenhum dado para atualizar', null, 400);
        }
        $this->userModel->update($id, $updateData);

        $updatedUser = $this->userModel->findSafe($id);

        $this->success(['user' => $updatedUser], 'Perfil atualizado com sucesso');
    }
}
