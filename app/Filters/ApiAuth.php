<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

        if (preg_match('/Bearer\s+(.*)/i', $auth, $matches)) {
            $token = trim($matches[1] ?? '');
            if ($token === '') {
                $response = service('response');
                return $response->setStatusCode(401)->setJSON([
                    'error' => 'Unauthorized',
                    'message' => 'Empty Bearer token',
                ]);
            }

            $secret = env('JWT_SECRET') ?? getenv('JWT_SECRET') ?? 'changeme';
            try {
                // Validate token signature and exp
                JWT::decode($token, new Key($secret, 'HS256'));
            } catch (\Throwable $e) {
                $response = service('response');
                return $response->setStatusCode(401)->setJSON([
                    'error' => 'Unauthorized',
                    'message' => 'Invalid or expired token',
                ]);
            }

            return null;
        }

        $response = service('response');
        return $response->setStatusCode(401)->setJSON([
            'error' => 'Unauthorized',
            'message' => 'Invalid Authorization header format',
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // noop
    }
}