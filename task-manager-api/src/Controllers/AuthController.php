<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

/**
 * Auth Controller
 * 
 * Gerencia autenticação de usuários
 */

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->authService = new AuthService();
    }

    /**
     * Registra novo usuário
     * POST /api/auth/register
     */
    public function register(array $params): void
    {
        $this->validateRequired(['name', 'email', 'password']);

        $name = $this->getInput('name');
        $email = $this->getInput('email');
        $password = $this->getInput('password');

        $this->validateEmail($email);

        $this->validateMinLength($name, 3, 'name');
        $this->validateMaxLength($name, 100, 'name');

        $this->validateMinLength($password, 8, 'password');

        try {
            $result = $this->authService->register($name, $email, $password);

            $this->created([
                'user' => $result['user'],
            ], 'Usuário registrado com sucesso');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), null, 400);
        }
    }

    /**
     * Login de usuário
     * POST /api/auth/login
     */
    public function login(array $params): void
    {
        $this->validateRequired(['email', 'password']);

        $email = $this->getInput('email');
        $password = $this->getInput('password');

        $this->validateEmail($email);

        try {
            $result = $this->authService->login($email, $password);

            $this->success([
                'user' => $result['user'],
            ], 'Login realizado com sucesso');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), null, 401);
        }
    }

    /**
     * Logout de usuário
     * POST /api/auth/logout    
     */
    public function logout(array $params): void
    {
        $this->authService->logout();
        $this->success(null, 'Logout realizado com sucesso');
    }

    /**
     * Obtém dados do usuário autenticado
     * GET /api/auth/me
     * 
     */
    public function me(array $params): void
    {
        $user = $this->user();

        if (!$user) {
            $this->error('Usuário não autenticado', null, 401);
        }
        $this->success(['user' => $user]);
    }

    /**
     * Renova token JWT
     * POST /api/auth/refres
     */
    public function refresh(array $params): void

    {
        $cookieName = config('jwt.cookie.name', 'auth_token');
        $token = $_COOKIE[$cookieName] ?? null;

        if (!$token) {
            $this->error('Token não fornecido', null, 401);
        }
        try {
            $result = $this->authService->refresh($token);
            $this->success([
                'user' => $result['user'],
            ], 'Token renovado com sucesso');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), null, 401);
        }
    }

    /**
     * Altera senha do usuário
     * POST /api/auth/change-password
     */
    public function changePassword(array $params): void
    {
        $this->validateRequired(['current_password', 'new_password']);
        $currentPassword = $this->getInput('current_password');
        $newPassword = $this->getInput('new_password');

        $this->validateMinLength($newPassword, 8, 'new_password');

        $userId = $this->userId();

        if (!$userId) {
            $this->error('Usuário não autenticado', null, 401);
        }
        try {
            $this->authService->changePassword($userId, $currentPassword, $newPassword);

            $this->success(null, 'Senha alterada com sucesso');
        } catch (\Exception $e) {
            $this->error($e->getMessage(), null, 400);
        }
    }
}
