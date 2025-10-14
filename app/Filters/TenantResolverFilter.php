<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filtro no-op para resolver tenant.
 * Mantido simples para não bloquear requests; posteriormente podemos popular
 * contexto via subdomínio, cabeçalhos, etc.
 */
class TenantResolverFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // No-op: futuramente podemos validar X-Tenant-ID, subdomínio, etc.
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}