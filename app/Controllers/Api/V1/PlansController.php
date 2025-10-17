<?php

namespace App\Controllers\Api\V1;

class PlansController extends BaseApiController
{
    public function index()
    {
        // Requer autenticação via JWT
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }

        $tokenAppId = (int) ($payload['app'] ?? 0);

        // app_id obrigatório via query (?app_id=) ou header X-App-ID
        $appId = (int) ($this->request->getGet('app_id')
            ?? $this->request->getHeaderLine('X-App-ID')
            ?? $this->request->getVar('app_id')
            ?? 0);

        if ($appId <= 0) {
            return $this->respondError('Parâmetro app_id é obrigatório', 422);
        }
        if ($tokenAppId > 0 && $tokenAppId !== $appId) {
            return $this->respondError('Token não pertence ao app informado', 403);
        }

        $rows = $this->db->table('app_plans')
            ->where('app_id', $appId)
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        return $this->respondOk([
            'app_id' => $appId,
            'plans' => $rows,
        ]);
    }
}