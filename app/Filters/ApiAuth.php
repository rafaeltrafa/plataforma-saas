<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = $request->getHeaderLine('Authorization');
        if (! $auth) {
            $response = service('response');
            return $response->setStatusCode(401)->setJSON([
                'error' => 'Unauthorized',
                'message' => 'Missing Authorization header',
            ]);
        }

        // TODO: validar token/jwt/api-key conforme sua regra
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // noop
    }
}