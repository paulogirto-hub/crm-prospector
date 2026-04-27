<?php
/**
 * PasswordReset — Serviço de recuperação de senha
 * 
 * Gerencia tokens de reset com expiração (tabela dedicada)
 */

namespace App\Core;

class PasswordReset
{
    private static ?\PDO $pdo = null;

    public static function setPdo(\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Cria token de reset e retorna o token
     */
    public static function createToken(string $email, int $expiresIn = 3600): ?string
    {
        if (!self::$pdo) return null;

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('c', time() + $expiresIn);
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = self::$pdo->prepare("
            INSERT INTO password_resets (email, token, expires_at, ip_address, user_agent) 
            VALUES (:email, :token, :expires, :ip, :ua)
        ");
        $stmt->execute([
            'email' => $email,
            'token' => $token,
            'expires' => $expiresAt,
            'ip' => $ip,
            'ua' => $ua,
        ]);

        return $token;
    }

    /**
     * Verifica se token é válido (existe, não expirou, não usado)
     */
    public static function verifyToken(string $email, string $token): bool
    {
        if (!self::$pdo) return false;

        $stmt = self::$pdo->prepare("
            SELECT id FROM password_resets 
            WHERE email = :email 
              AND token = :token 
              AND expires_at > NOW() 
              AND used_at IS NULL
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute(['email' => $email, 'token' => $token]);
        return !empty($stmt->fetch());
    }

    /**
     * Marca token como usado
     */
    public static function consumeToken(string $email, string $token): bool
    {
        if (!self::$pdo) return false;

        $stmt = self::$pdo->prepare("
            UPDATE password_resets 
            SET used_at = NOW() 
            WHERE email = :email 
              AND token = :token 
              AND used_at IS NULL
        ");
        $stmt->execute(['email' => $email, 'token' => $token]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Limpa tokens expirados (manutenção)
     */
    public static function cleanup(): int
    {
        if (!self::$pdo) return 0;

        $stmt = self::$pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
