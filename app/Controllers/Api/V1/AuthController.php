<?php

namespace App\Controllers\Api\V1;

use App\Models\TenantModel;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseApiController
{
    use ResponseTrait;

    public function login()
    {
        $input = $this->getInputPayload();

        $email = trim((string) ($input['email'] ?? $input['contact_email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->respondError('Email e senha são obrigatórios', 422);
        }

        $model = new TenantModel();
        $tenant = $model->where('contact_email', $email)->first();
        if (! $tenant || empty($tenant['password_hash'])) {
            return $this->respondError('Credenciais inválidas', 401);
        }

        if (! password_verify($password, (string) $tenant['password_hash'])) {
            return $this->respondError('Credenciais inválidas', 401);
        }

        // Atualiza last_login_at
        $model->update((int) $tenant['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        $secret = env('JWT_SECRET') ?? getenv('JWT_SECRET') ?? 'changeme';
        $ttl = (int) (env('JWT_TTL') ?? getenv('JWT_TTL') ?? 3600);

        $payload = [
            'sub' => (int) $tenant['id'],
            'email' => (string) $tenant['contact_email'],
            'iat' => time(),
            'exp' => time() + $ttl,
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return $this->respondOk([
            'token' => $token,
            'tenant' => [
                'id' => (int) $tenant['id'],
                'name' => (string) $tenant['name'],
                'contact_email' => (string) $tenant['contact_email'],
            ],
        ], 200);
    }
}
