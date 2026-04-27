<?php
/**
 * Request — Wrapper para dados de requisição HTTP
 * 
 * Sanitiza e facilita acesso a $_GET, $_POST, $_SERVER, $_FILES
 */

namespace App\Core;

class Request
{
    private array $query = [];
    private array $body = [];
    private array $server = [];
    private array $files = [];

    public function __construct()
    {
        $this->query = $_GET;
        $this->server = $_SERVER;
        $this->files = $_FILES;

        // Parse body baseado no content type
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $this->body = json_decode($raw, true) ?? [];
        } elseif (in_array($this->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            // Suporte ao method spoofing (_method field)
            $this->body = $_POST;
            if (isset($this->body['_method'])) {
                $this->server['REQUEST_METHOD'] = strtoupper($this->body['_method']);
                unset($this->body['_method']);
            }
        }
    }

    /**
     * Obtém valor de query string (?foo=bar)
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($this->query[$key] ?? $default);
    }

    /**
     * Obtém valor do body (POST/PUT data)
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($this->body[$key] ?? $default);
    }

    /**
     * Alias para input()
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    /**
     * Obtém valor de GET ou POST
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($this->query[$key] ?? $this->body[$key] ?? $default);
    }

    /**
     * Retorna todos os dados do body
     */
    public function all(): array
    {
        return array_map([$this, 'sanitize'], $this->body);
    }

    /**
     * Retorna apenas campos específicos do body
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (isset($this->body[$key])) {
                $result[$key] = $this->sanitize($this->body[$key]);
            }
        }
        return $result;
    }

    /**
     * Retorna o body exceto campos específicos
     */
    public function except(array $keys): array
    {
        return array_map([$this, 'sanitize'], array_diff_key($this->body, array_flip($keys)));
    }

    /**
     * Verifica se campo existe no body
     */
    public function has(string $key): bool
    {
        return isset($this->body[$key]) && $this->body[$key] !== '';
    }

    /**
     * Método HTTP da requisição
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * URI da requisição (sem query string)
     */
    public function uri(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return '/' . trim($uri ?: '/', '/');
    }

    /**
     * URL completa
     */
    public function fullUrl(): string
    {
        $scheme = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * IP do cliente
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['HTTP_X_REAL_IP'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }

    /**
     * User Agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Verifica se é AJAX
     */
    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($this->server['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /**
     * Verifica se é método específico
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * Upload de arquivo
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Verifica se tem arquivo enviado
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Sanitiza valor
     */
    private function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }
}