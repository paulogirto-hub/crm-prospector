<?php
/**
 * Response — HTTP Response helper
 */

namespace App\Core;

class Response
{
    private int $status = 200;
    private array $headers = [];
    private ?string $content = null;

    public function setStatus(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Envia a resposta HTTP
     */
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        if ($this->content !== null) {
            echo $this->content;
        }
    }

    /**
     * Resposta JSON
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self();
        $response->status = $status;
        $response->headers['Content-Type'] = 'application/json; charset=utf-8';
        $response->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $response;
    }

    /**
     * Redirecionamento
     */
    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self();
        $response->status = $status;
        $response->headers['Location'] = $url;
        $response->content = '';
        return $response;
    }

    /**
     * Redirecionamento de volta para a página anterior
     */
    public static function back(): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return self::redirect($referer);
    }

    /**
     * Erro 404
     */
    public static function abort(int $code = 404, string $message = ''): never
    {
        http_response_code($code);
        
        if ($code === 404) {
            $message = $message ?: 'Página não encontrada';
        } elseif ($code === 403) {
            $message = $message ?: 'Acesso negado';
        } elseif ($code === 429) {
            $message = $message ?: 'Muitas requisições. Tente novamente mais tarde.';
        }

        // Tenta renderizar view de erro
        $errorView = dirname(__DIR__) . "/Views/errors/{$code}.php";
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>{$code}</h1><p>{$message}</p>";
        }
        exit;
    }
}