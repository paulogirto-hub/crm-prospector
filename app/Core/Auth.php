<?php
/**
 * Auth — Autenticação e autorização
 * 
 * Login/logout, session management, remember me, password verify
 */

namespace App\Core;

class Auth
{
    private static ?\PDO $pdo = null;

    /**
     * Seta a conexão PDO
     */
    public static function setPdo(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Tenta autenticar um usuário
     */
    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = self::findByEmail($email);

        if (!$user) return false;
        if (!password_verify($password, $user['password_hash'])) return false;
        if (!$user['active']) return false;

        self::login($user);

        if ($remember) {
            self::setRememberCookie($user);
        }

        Session::regenerate();

        AuditLog::create([
            'user_id' => $user['id'],
            'action' => 'login',
            'entity_type' => 'user',
            'entity_id' => $user['id'],
            'details' => json_encode(['method' => 'password']),
            'ip' => self::currentIp(),
        ]);

        return true;
    }

    /**
     * Faz login do usuário na sessão
     */
    public static function login(array $user): void
    {
        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);
    }

    /**
     * Faz logout
     */
    public static function logout(): void
    {
        if (self::check()) {
            AuditLog::create([
                'user_id' => self::userId(),
                'action' => 'logout',
                'entity_type' => 'user',
                'entity_id' => self::userId(),
                'details' => json_encode([]),
                'ip' => self::currentIp(),
            ]);
        }

        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            unset($_COOKIE['remember_token']);
        }

        Session::destroy();
    }

    /**
     * Verifica se está autenticado
     */
    public static function check(): bool
    {
        if (Session::has('user_id')) return true;
        return self::viaRemember();
    }

    /**
     * Retorna o usuário autenticado completo
     */
    public static function user(): ?array
    {
        if (!self::check()) return null;
        $userId = Session::get('user_id');
        if (!$userId) return null;
        return self::findById($userId);
    }

    /**
     * Retorna o ID do usuário autenticado
     */
    public static function userId(): ?int
    {
        return Session::get('user_id');
    }

    /**
     * Verifica se o usuário tem uma role específica
     */
    public static function hasRole(string $role): bool
    {
        return Session::get('user_role') === $role;
    }

    /**
     * Verifica se o usuário é admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Verifica se o usuário é admin ou manager
     */
    public static function isManagerOrAbove(): bool
    {
        return in_array(Session::get('user_role'), ['admin', 'manager']);
    }

    /**
     * Tenta autenticar via cookie "remember me"
     */
    private static function viaRemember(): bool
    {
        $token = $_COOKIE['remember_token'] ?? null;
        if (!$token) return false;

        $user = self::findByRememberToken($token);
        if (!$user) return false;

        self::login($user);
        Session::regenerate();
        return true;
    }

    /**
     * Seta o cookie de remember me
     */
    private static function setRememberCookie(array $user): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        
        self::updateRememberToken($user['id'], $hash);
        
        setcookie('remember_token', $token, [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'secure' => true, // HTTPS ativo
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Busca usuário por email
     */
    private static function findByEmail(string $email): ?array
    {
        if (!self::$pdo) return null;
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE email = :email AND active = true LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca usuário por ID
     */
    private static function findById(int $id): ?array
    {
        if (!self::$pdo) return null;
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca usuário por remember token
     */
    private static function findByRememberToken(string $token): ?array
    {
        if (!self::$pdo) return null;
        $hash = hash('sha256', $token);
        $stmt = self::$pdo->prepare("SELECT * FROM users WHERE remember_token = :token AND active = true LIMIT 1");
        $stmt->execute(['token' => $hash]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Atualiza remember token do usuário
     */
    private static function updateRememberToken(int $userId, string $hash): void
    {
        if (!self::$pdo) return;
        $stmt = self::$pdo->prepare("UPDATE users SET remember_token = :token, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['token' => $hash, 'id' => $userId]);
    }

    /**
     * IP atual
     */
    private static function currentIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_X_REAL_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }
}