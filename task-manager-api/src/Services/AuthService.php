<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\SecurityHelper;
use App\Models\User;
use Random\Engine\Secure;

/**
 * Auth Service
 * 
 * Serviço para lógica de autenticação
 */
class AuthService
{
    private User $userModel;
    private JWTService $jwtService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwtService = new JWTService();
    }

    /**
     * Registra novo usuário
     */
    public function register(string $name, string $email, string $password): array
    {
        if ($this->userModel->emailExists($email)) {
            throw new \Exception('Email já cadastrado');
        }
        if (strlen($password) < 8) {
            throw new \Exception('Senha deve ter no mínimo 8 caracteres');
        }
        $passwordHash = SecurityHelper::hashPassword($password);

        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);

        if (!$userId) {
            throw new \Exception('Erro ao criar usuário');
        }

        $user = $this->userModel->findSafe($userId);

        $token = $this->generateToken($user);

        $this->jwtService->setCookie($token);

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Autentica usuario
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            throw new \Exception('Credenciais inválidas');
        }
        if (!SecurityHelper::verifyPassword($password, $user['password_hash'])) {
            throw new \Exception('Credenciais inválidas');
        }
        $this->userModel->updateLastLogin($user['id']);

        unset($user['password_hash']);

        $token = $this->generateToken($user);

        $this->jwtService->setCookie($token);

        if (SecurityHelper::neeedsRehash($user['password_hash'] ?? '')) {
            $newHash = SecurityHelper::hashPassword($password);
            $this->userModel->update($user['id'], ['password_hash' => $newHash]);
        }

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Logout (remove cookie)
     */
    public function logout(): void
    {
        $this->jwtService->deleteCookie();
    }

    /**
     * Renova token JWT
     */
    public function refresh(string $oldToken): array
    {
        $payload = $this->jwtService->validate($oldToken);
        if (!$payload) {
            throw new \Exception('Token inválido');
        }

        $user = $this->userModel->findSafe($payload['user_id']);

        if (!$user) {
            throw new \Exception('Usuário não encontrado');
        }

        $token = $this->generateToken($user);

        $this->jwtService->setCookie($token);

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /** 
     * Obtém usuário autenticado pelo token
     */
    public function me(string $token): ?array
    {
        $payload = $this->jwtService->validate($token);
        if (!$payload) {
            return null;
        }

        return $this->userModel->findSafe($payload['user_id']);
    }

    /**
     * Gera token JWT para usuário
     */
    private function generateToken(array $user): string
    {
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ];

        return $this->jwtService->generate($payload);
    }

    /**
     * Altera senha do usuário
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userModel->find($userId);

        if (!$user) {
            throw new \Exception('Usuário não encontrado');
        }

        if (!SecurityHelper::verifyPassword($currentPassword, $user['password_hash'])) {
            throw new \Exception('Senha atual incorreta');
        }

        if (strlen($newPassword) < 8) {
            throw new \Exception('Nova senha deve ter no mínimo 8 caracteres');
        }

        $passwordHash = SecurityHelper::hashPassword($newPassword);
        return $this->userModel->update($userId, ['password_hash' => $passwordHash]);
    }
}
