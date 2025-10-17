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
        // app_id pode vir no corpo, header ou query
        $headerAppIdRaw = $this->request->getHeaderLine('X-App-ID');
        $headerAppId = is_numeric($headerAppIdRaw) ? (int) $headerAppIdRaw : 0;
        $queryAppId = 0;
        $uri = method_exists($this->request, 'getUri') ? $this->request->getUri() : null;
        if ($uri && method_exists($uri, 'getQuery')) {
            $queryStr = (string) $uri->getQuery();
            if ($queryStr !== '') {
                $qs = [];
                parse_str($queryStr, $qs);
                if (isset($qs['app_id'])) {
                    $queryAppId = (int) $qs['app_id'];
                }
            }
        }
        $appId = (int) (
            ($input['app_id'] ?? 0)
            ?: $headerAppId
            ?: $queryAppId
        );

        if ($email === '' || $password === '' || $appId <= 0) {
            return $this->respondError('Email, senha e app_id são obrigatórios', 422);
        }

        $model = new TenantModel();
        $tenant = $model->where('contact_email', $email)->first();
        if (! $tenant) {
            return $this->respondError('Credenciais inválidas', 401);
        }

        // Validar vínculo tenant->app e senha por app em tenant_apps
        $tenantApp = $this->db->table('tenant_apps')
            ->where(['tenant_id' => (int) $tenant['id'], 'app_id' => $appId])
            ->get()
            ->getRowArray();
        if (! $tenantApp || (int) ($tenantApp['is_enabled'] ?? 0) !== 1) {
            return $this->respondError('Tenant não possui este app instalado ou está desativado', 403);
        }
        $credentialHash = (string) ($tenantApp['credential_hash'] ?? '');
        if ($credentialHash === '') {
            // Sem credencial configurada para este app
            return $this->respondError('Senha não configurada para este app. Defina a senha primeiro.', 409);
        }
        if (! password_verify($password, $credentialHash)) {
            return $this->respondError('Credenciais inválidas', 401);
        }

        // Atualiza last_login_at no tenant
        $model->update((int) $tenant['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        // Opcional: atualizar updated_at do vínculo
        $this->db->table('tenant_apps')
            ->where(['tenant_id' => (int) $tenant['id'], 'app_id' => $appId])
            ->update(['updated_at' => date('Y-m-d H:i:s')]);

        // Segredo do JWT
        $rawSecret = env('JWT_SECRET') ?? getenv('JWT_SECRET') ?? '';
        $secret = is_string($rawSecret) ? trim($rawSecret) : '';
        if ($secret === '') {
            $secret = (string) (config('Encryption')->key ?? '');
        }
        if ($secret === '' || ! is_string($secret)) {
            return $this->respondError('Configuração JWT_SECRET ausente ou inválida', 500);
        }

        // TTL
        $ttlRaw = env('JWT_TTL') ?? getenv('JWT_TTL') ?? 3600;
        $ttl = is_numeric($ttlRaw) ? (int) $ttlRaw : 3600;
        if ($ttl <= 0) {
            $ttl = 3600;
        }

        $now = time();
        $payload = [
            'sub' => (int) $tenant['id'],
            'email' => (string) $tenant['contact_email'],
            'app' => $appId,
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
            'app_id' => $appId,
        ], 200);
    }

    public function changePassword()
    {
        // Requer autenticação
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }
        $tenantId = (int) $payload['sub'];
        $tokenAppId = (int) ($payload['app'] ?? 0);

        $input = $this->getInputPayload();
        // app_id pode vir no corpo, header ou query
        $headerAppIdRaw = $this->request->getHeaderLine('X-App-ID');
        $headerAppId = is_numeric($headerAppIdRaw) ? (int) $headerAppIdRaw : 0;
        $queryAppId = 0;
        $uri = method_exists($this->request, 'getUri') ? $this->request->getUri() : null;
        if ($uri && method_exists($uri, 'getQuery')) {
            $queryStr = (string) $uri->getQuery();
            if ($queryStr !== '') {
                $qs = [];
                parse_str($queryStr, $qs);
                if (isset($qs['app_id'])) {
                    $queryAppId = (int) $qs['app_id'];
                }
            }
        }
        $appId = (int) (
            ($input['app_id'] ?? 0)
            ?: $headerAppId
            ?: $queryAppId
        );
        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');

        if ($appId <= 0 || $newPassword === '' || $currentPassword === '') {
            return $this->respondError('Parâmetros obrigatórios: app_id, current_password, new_password', 422);
        }
        if ($tokenAppId > 0 && $tokenAppId !== $appId) {
            return $this->respondError('Token não pertence ao app informado', 403);
        }

        $tenantApp = $this->db->table('tenant_apps')
            ->where(['tenant_id' => $tenantId, 'app_id' => $appId])
            ->get()
            ->getRowArray();
        if (! $tenantApp || (int) ($tenantApp['is_enabled'] ?? 0) !== 1) {
            return $this->respondError('Tenant não possui este app instalado ou está desativado', 403);
        }

        $credentialHash = (string) ($tenantApp['credential_hash'] ?? '');
        if ($credentialHash !== '' && ! password_verify($currentPassword, $credentialHash)) {
            return $this->respondError('Senha atual inválida', 401);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');
        $this->db->table('tenant_apps')
            ->where(['tenant_id' => $tenantId, 'app_id' => $appId])
            ->update([
                'credential_hash' => $newHash,
                'credential_updated_at' => $now,
                'updated_at' => $now,
            ]);

        return $this->respondOk([
            'tenant_id' => $tenantId,
            'app_id' => $appId,
            'updated' => true,
        ], 200);
    }
}
