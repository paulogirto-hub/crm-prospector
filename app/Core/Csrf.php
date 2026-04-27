<?php
/**
 * Csrf — Proteção contra Cross-Site Request Forgery
 */

namespace App\Core;

class Csrf
{
    /**
     * Gera um novo token CSRF e armazena na sessão
     */
    public static function token(): string
    {
        Session::start();
        
        // Se já existe um token válido, reutiliza
        $existingToken = Session::get('csrf_token');
        $tokenTime = Session::get('csrf_token_time', 0);
        
        // Token expira em 2 horas
        if ($existingToken && (time() - $tokenTime) < 7200) {
            return $existingToken;
        }

        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        Session::set('csrf_token_time', time());
        return $token;
    }

    /**
     * Valida um token CSRF
     */
    public static function validate(string $token): bool
    {
        $storedToken = Session::get('csrf_token');
        
        if (!$storedToken || !$token) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Verifica CSRF da requisição atual (POST/PUT/DELETE)
     */
    public static function check(): bool
    {
        $request = new Request();
        $method = $request->method();

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        $token = $request->input('_csrf') 
            ?? $request->server['HTTP_X_CSRF_TOKEN'] 
            ?? null;

        if (!$token || !self::validate($token)) {
            return false;
        }

        return true;
    }

    /**
     * Gera campo hidden para formulário
     */
    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Gera meta tag para AJAX
     */
    public static function meta(): string
    {
        $token = self::token();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}