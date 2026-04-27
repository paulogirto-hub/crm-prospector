<?php
/**
 * Funções globais de conveniência (namespace global)
 * Carregado via Composer autoload files
 */

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return \App\Core\Helper::e($value);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \App\Core\Config::get($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        \App\Core\Flash::set($type, $message);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): \App\Core\Response
    {
        return \App\Core\Response::redirect($url);
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return \App\Core\Auth::user();
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $baseUrl = \App\Core\Config::get('APP_URL', '');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): string
    {
        return \App\Core\Session::getFlash("_old_input.{$key}", (string)$default);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Csrf::token();
    }
}

if (!function_exists('method')) {
    function method(string $verb): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($verb) . '">';
    }
}