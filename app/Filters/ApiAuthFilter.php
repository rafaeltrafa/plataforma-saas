<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = $request->getHeaderLine('Authorization');
        if (! $auth || ! preg_match('/Bearer\s+(.*)/i', $auth, $m)) {
            return $this->unauthorized('Missing or invalid Authorization header.');
        }
        $token = trim($m[1] ?? '');
        if ($token === '') {
            return $this->unauthorized('Empty bearer token.');
        }

        $secret = env('JWT_SECRET') ?? getenv('JWT_SECRET') ?? 'changeme';
        try {
            // Apenas valida o token; payload será reprocessado no controller quando necessário
            JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid token.');
        }

        return null; // permite a continuação
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    private function unauthorized(string $message)
    {
        $response = Services::response();
        return $response->setStatusCode(401)->setJSON([
            'error' => 'Unauthorized',
            'message' => $message,
        ]);
    }
}