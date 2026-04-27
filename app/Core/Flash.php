<?php
/**
 * Flash — Mensagens flash (temporárias) entre requisições
 */

namespace App\Core;

class Flash
{
    /**
     * Define uma mensagem flash
     */
    public static function set(string $type, string $message): void
    {
        Session::start();
        $flashes = Session::get('_flashes', []);
        $flashes[] = ['type' => $type, 'message' => $message];
        Session::set('_flashes', $flashes);
    }

    /**
     * Mensagem de sucesso
     */
    public static function success(string $message): void
    {
        self::set('success', $message);
    }

    /**
     * Mensagem de erro
     */
    public static function error(string $message): void
    {
        self::set('error', $message);
    }

    /**
     * Mensagem de aviso
     */
    public static function warning(string $message): void
    {
        self::set('warning', $message);
    }

    /**
     * Mensagem informativa
     */
    public static function info(string $message): void
    {
        self::set('info', $message);
    }

    /**
     * Obtém e remove todas as mensagens flash
     */
    public static function all(): array
    {
        $flashes = Session::get('_flashes', []);
        Session::remove('_flashes');
        return $flashes;
    }

    /**
     * Verifica se há mensagens flash
     */
    public static function has(): bool
    {
        return !empty(Session::get('_flashes', []));
    }
}