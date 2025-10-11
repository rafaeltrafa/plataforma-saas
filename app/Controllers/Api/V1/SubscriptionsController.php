<?php

namespace App\Controllers\Api\V1;

class SubscriptionsController extends BaseApiController
{
    public function index()
    {
        return $this->respondOk([
            'message' => 'Lista de assinaturas (placeholder)',
        ]);
    }
}