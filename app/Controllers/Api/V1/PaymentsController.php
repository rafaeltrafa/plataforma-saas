<?php

namespace App\Controllers\Api\V1;

class PaymentsController extends BaseApiController
{
    public function index()
    {
        return $this->respondOk([
            'message' => 'Lista de pagamentos (placeholder)',
        ]);
    }
}