<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class BaseApiController extends ResourceController
{
    use ResponseTrait;

    protected $format = 'json';

    protected function respondOk($data = [], int $status = 200)
    {
        return $this->respond(['data' => $data], $status);
    }

    protected function respondError(string $message, int $status = 400, array $errors = [])
    {
        return $this->respond(['error' => $message, 'errors' => $errors], $status);
    }
}