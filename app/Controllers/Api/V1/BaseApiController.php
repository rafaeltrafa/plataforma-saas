<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * @property \CodeIgniter\HTTP\IncomingRequest $request
 * @property \CodeIgniter\Database\BaseConnection $db
 */
class BaseApiController extends \App\Controllers\BaseController
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

    /**
     * Retorna payload de entrada combinando JSON, raw input e POST, sem depender
     * de métodos específicos para evitar alertas da IDE.
     */
    protected function getInputPayload(): array
    {
        $input = [];
        if (method_exists($this->request, 'getJSON')) {
            $json = $this->request->getJSON(true);
            if (is_array($json) && ! empty($json)) {
                return $json;
            }
        }
        if (method_exists($this->request, 'getRawInput')) {
            $raw = (array) ($this->request->getRawInput() ?? []);
            if (! empty($raw)) {
                return $raw;
            }
        }
        $body = '';
        if (method_exists($this->request, 'getBody')) {
            $body = (string) ($this->request->getBody() ?? '');
        }
        if ($body === '') {
            $body = @file_get_contents('php://input') ?: '';
        }
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        }
        if (method_exists($this->request, 'getPost')) {
            $input = (array) ($this->request->getPost() ?? []);
        }
        return $input;
    }

    /**
     * Decodifica o JWT do cabeçalho Authorization (Bearer) e retorna o payload.
     * Retorna null se ausente ou inválido.
     */
    protected function getAuthPayload(): ?array
    {
        $auth = $this->request->getHeaderLine('Authorization');
        if (! $auth || ! preg_match('/Bearer\s+(.*)/i', $auth, $m)) {
            return null;
        }
        $token = trim($m[1] ?? '');
        if ($token === '') {
            return null;
        }
        // Segredo do JWT: garantir string não vazia (evitar boolean false de .env)
        $rawSecret = env('JWT_SECRET') ?? getenv('JWT_SECRET') ?? '';
        $secret = is_string($rawSecret) ? trim($rawSecret) : '';
        if ($secret === '') {
            $secret = (string) (config('Encryption')->key ?? '');
        }
        if ($secret === '' || ! is_string($secret)) {
            log_message('error', 'JWT_SECRET ausente ou inválido ao decodificar token');
            return null;
        }
        try {
            // decode retorna stdClass; converter para array
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return json_decode(json_encode($decoded), true);
        } catch (\Throwable $e) {
            return null;
        }
    }
}