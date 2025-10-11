<?php

namespace App\Controllers\Api\V1;

class TenantsController extends BaseApiController
{
    public function index()
    {
        // Esqueleto: retorna estrutura básica
        return $this->respondOk([
            'message' => 'Lista de tenants (placeholder)',
        ]);
    }
}