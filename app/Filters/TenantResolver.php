<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TenantResolver implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Exemplo: resolver tenant por header X-Tenant-ID ou subdomínio
        // Este é um esqueleto; ajuste com sua regra e storage de contexto
        // $tenantId = $request->getHeaderLine('X-Tenant-ID');
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // noop
    }
}