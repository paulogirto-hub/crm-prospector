<?php
/**
 * Session — Gerenciamento de sessões PHP
 */

namespace App\Core;

class Session
{
    private static bool $started = false;

    /**
     * Inicia a sessão se ainda não foi iniciada
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // Configurações de segurança
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        // HTTPS ativo — cookies seguros
        ini_set('session.cookie_secure', '0');
        ini_set('session.gc_maxlifetime', Config::get('SESSION_LIFETIME', 7200));

        session_start();
        self::$started = true;

        // Session fingerprinting — prevenir hijacking
        $fingerprint = self::fingerprint();
        if (isset($_SESSION['_fingerprint'])) {
            if ($_SESSION['_fingerprint'] !== $fingerprint) {
                // Fingerprint mudou = possível hijacking, destruir sessão
                self::destroy();
                self::start();
                return;
            }
        } else {
            $_SESSION['_fingerprint'] = $fingerprint;
        }
    }

    /**
     * Gera fingerprint baseado em IP + User-Agent
     */
    private static function fingerprint(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ip . '|' . $ua . '|' . Config::get('APP_SECRET', 'default'));
    }

    /**
     * Define um valor na sessão
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtém um valor da sessão
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verifica se uma chave existe na sessão
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove uma chave da sessão
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Regenera o ID da sessão (para prevenir fixation)
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Destroi a sessão completamente
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
        self::$started = false;
    }

    /**
     * Obtém e remove um valor (flash-like)
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }

    /**
     * Obtém o ID da sessão
     */
    public static function id(): string
    {
        self::start();
        return session_id();
    }
}