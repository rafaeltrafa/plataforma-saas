<?php

namespace App\Controllers\Api\V1;

use App\Models\TenantModel;

class TenantsController extends BaseApiController
{
    public function index()
    {
        // Esqueleto: retorna estrutura básica
        return $this->respondOk([
            'message' => 'Lista de tenants (placeholder)',
        ]);
    }

    public function create()
    {
        // Parsing robusto do corpo da requisição
        $input = [];
        if (method_exists($this->request, 'getJSON')) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    $input = $json;
                }
            } catch (\Throwable $e) {
                // segue para fallbacks
            }
        }
        if (empty($input) && method_exists($this->request, 'getRawInput')) {
            $raw = (array) ($this->request->getRawInput() ?? []);
            if (! empty($raw)) {
                $input = $raw;
            }
        }
        if (empty($input)) {
            $rawBody = $this->request->getBody();
            if ($rawBody === '' || $rawBody === null) {
                $rawBody = @file_get_contents('php://input');
            }
            if (is_string($rawBody) && trim($rawBody) !== '') {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input = $decoded;
                }
            }
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        // Completa chaves possivelmente ausentes a partir de variáveis do request
        $input['name'] = $input['name'] ?? $this->request->getVar('name');
        $input['contact_email'] = $input['contact_email'] ?? $this->request->getVar('contact_email');
        // Alias: aceitar "email" como "contact_email"
        if (empty($input['contact_email'])) {
            $input['contact_email'] = $input['email'] ?? $this->request->getVar('email');
        }

        // Regras de validação básicas
        $rules = [
            'name'           => 'required|min_length[3]|max_length[100]',
            'contact_email'  => 'required|valid_email|max_length[255]',
            'contact_phone'  => 'permit_empty|max_length[50]',
            'document_number' => 'permit_empty|max_length[50]',
            'slug'           => 'permit_empty|alpha_dash|max_length[100]',
            'subdomain'      => 'permit_empty|alpha_dash|max_length[63]',
            'domain'         => 'permit_empty|max_length[255]',
            'locale'         => 'permit_empty|max_length[10]',
            'timezone'       => 'permit_empty|max_length[50]',
            'stripe_customer_id' => 'permit_empty|max_length[64]',
            'trial_end_at'   => 'permit_empty|valid_date',
            'is_active'      => 'permit_empty|in_list[0,1]',
            'app_id'         => 'required|is_natural_no_zero',
            'password'       => 'permit_empty|min_length[8]'
        ];

        if (method_exists($this, 'validateData')) {
            if (! $this->validateData($input, $rules)) {
                return $this->respondError('Dados inválidos', 422, $this->validator->getErrors());
            }
        } else {
            $validation = \Config\Services::validation();
            /** @var \CodeIgniter\Validation\Validation $validation */
            $validation->setRules($rules);
            if (! $validation->run($input)) {
                return $this->respondError('Dados inválidos', 422, $validation->getErrors());
            }
        }

        $model = new TenantModel();

        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['contact_email'] ?? ''));
        $appId = (int) (($input['app_id'] ?? $this->request->getVar('app_id')) ?? 0);

        // Se já existe tenant com o mesmo e-mail, não registra novamente — apenas vincula ao app
        $existingTenant = $model->where('contact_email', $email)->first();
        if ($existingTenant) {
            $tenant = $existingTenant;
            $tenantId = (int) $tenant['id'];

            // Vincular tenant ao app na tabela tenant_apps (idempotente)
            if ($appId > 0) {
                $tenantApps = $this->db->table('tenant_apps');
                $now = date('Y-m-d H:i:s');
                $exists = $tenantApps->where(['tenant_id' => $tenantId, 'app_id' => $appId])->get()->getRow();
                if (! $exists) {
                    $tenantApps->insert([
                        'tenant_id'    => $tenantId,
                        'app_id'       => $appId,
                        'installed_at' => $now,
                        'is_enabled'   => 1,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                        'credential_hash' => ! empty($input['password']) ? password_hash((string) $input['password'], PASSWORD_DEFAULT) : null,
                        'credential_updated_at' => ! empty($input['password']) ? $now : null,
                    ]);
                } else if (! empty($input['password'])) {
                    $hash = password_hash((string) $input['password'], PASSWORD_DEFAULT);
                    $tenantApps->where(['tenant_id' => $tenantId, 'app_id' => $appId])->update([
                        'credential_hash' => $hash,
                        'credential_updated_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            return $this->respondOk([
                'tenant'  => $tenant,
                'app_id'  => $appId,
                'linked'  => true,
                'created' => false,
            ], 200);
        }

        // Slug
        $slug = $input['slug'] ?? null;
        if (empty($slug) && $name !== '') {
            $slug = $this->slugify($name);
        }
        $slug = $this->ensureUnique($model, 'slug', $slug);

        // Subdomínio
        $subdomain = $input['subdomain'] ?? null;
        if (empty($subdomain)) {
            $subdomain = $slug ?: $this->slugify($name ?: 'tenant');
        }
        $subdomain = $this->ensureUnique($model, 'subdomain', $subdomain);

        // Normaliza is_active
        $isActive = $input['is_active'] ?? 1;
        $isActive = (string) $isActive === '0' ? 0 : 1;

        // Garantir defaults não nulos para campos possivelmente NOT NULL
        $locale = $input['locale']
            ?? $this->request->getVar('locale')
            ?? (config('App')->defaultLocale ?? 'en');
        $timezone = $input['timezone']
            ?? $this->request->getVar('timezone')
            ?? (config('App')->appTimezone ?? 'UTC');

        $data = [
            'name'            => $name,
            'contact_email'   => $email,
            'contact_phone'   => $input['contact_phone'] ?? null,
            'document_number' => $input['document_number'] ?? null,
            'slug'            => $slug,
            'subdomain'       => $subdomain,
            'domain'          => $input['domain'] ?? null,
            'locale'          => $locale,
            'timezone'        => $timezone,
            'stripe_customer_id' => $input['stripe_customer_id'] ?? null,
            'trial_end_at'    => $input['trial_end_at'] ?? null,
            'is_active'       => $isActive,
        ];

        // Se a senha foi fornecida no cadastro, gerar e armazenar o hash
        if (! empty($input['password'])) {
            $data['password_hash'] = password_hash((string) $input['password'], PASSWORD_DEFAULT);
        }

        $id = $model->insert($data);
        if (! $id) {
            return $this->respondError('Falha ao inserir tenant', 500);
        }

        $tenant = $model->find($id);

        // Vincular tenant ao app na tabela tenant_apps
        if ($appId > 0) {
            $tenantApps = $this->db->table('tenant_apps');
            $now = date('Y-m-d H:i:s');
            $exists = $tenantApps->where(['tenant_id' => (int) $id, 'app_id' => $appId])->get()->getRow();
            if (! $exists) {
                $tenantApps->insert([
                    'tenant_id'    => (int) $id,
                    'app_id'       => $appId,
                    'installed_at' => $now,
                    'is_enabled'   => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                    'credential_hash' => ! empty($input['password']) ? password_hash((string) $input['password'], PASSWORD_DEFAULT) : null,
                    'credential_updated_at' => ! empty($input['password']) ? $now : null,
                ]);
            } else if (! empty($input['password'])) {
                $hash = password_hash((string) $input['password'], PASSWORD_DEFAULT);
                $tenantApps->where(['tenant_id' => (int) $id, 'app_id' => $appId])->update([
                    'credential_hash' => $hash,
                    'credential_updated_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return $this->respondOk([
            'tenant'  => $tenant,
            'app_id'  => $appId,
            'linked'  => true,
            'created' => true,
        ], 201);
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-');
    }

    private function ensureUnique(TenantModel $model, string $field, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $base = $value;
        $suffix = 1;
        while ($model->where($field, $value)->first()) {
            $value = $base . '-' . $suffix++;
        }
        return $value;
    }
}
