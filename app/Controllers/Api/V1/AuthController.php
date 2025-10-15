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

        // Segredo do JWT: garantir string não vazia (evitar boolean false de .env)
        $rawSecret = env('JWT_SECRET') ?? getenv('JWT_SECRET') ?? '';
        $secret = is_string($rawSecret) ? trim($rawSecret) : '';
        if ($secret === '') {
            $secret = (string) (config('Encryption')->key ?? '');
        }
        if ($secret === '' || ! is_string($secret)) {
            return $this->respondError('Configuração JWT_SECRET ausente ou inválida', 500);
        }

        // TTL: sanitizar para evitar expiração imediata (ex.: JWT_TTL=false/0)
        $ttlRaw = env('JWT_TTL') ?? getenv('JWT_TTL') ?? 3600;
        $ttl = is_numeric($ttlRaw) ? (int) $ttlRaw : 3600;
        if ($ttl <= 0) {
            $ttl = 3600;
        }

        $now = time();
        $payload = [
            'sub' => (int) $tenant['id'],
            'email' => (string) $tenant['contact_email'],
            'iat' => $now,
            'exp' => $now + $ttl,
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
