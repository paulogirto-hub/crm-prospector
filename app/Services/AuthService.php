<?php
/**
 * AuthService — Lógica de negócio de autenticação
 * MELH-001: Extraído de AuthController
 * 
 * Responsabilidades:
 * - Login com rate limiting
 * - Registro (admin + self-registration)
 * - Password reset com tabela dedicada
 * - Remember me
 */

namespace App\Services;

use App\Core\Model;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Session;
use App\Core\RateLimit;
use App\Core\AuditLog;
use App\Core\Validator;
use App\Models\User;
use AppCoreLogger;

class AuthService
{
    /**
     * Tenta autenticar um usuário com rate limiting
     * Retorna ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public static function attemptLogin(string $email, string $password, bool $remember = false, string $ip = '0.0.0.0'): array
    {
        $loginKey = RateLimit::keyForIp('login');

        // Rate limit: 5 tentativas por IP em 15 minutos
        if (!RateLimit::check($loginKey, 5, 900)) {
            $retryAfter = RateLimit::retryAfter($loginKey);
            return [
                'success' => false,
                'error' => "Muitas tentativas de login. Tente novamente em {$retryAfter} segundos.",
                'rate_limited' => true,
            ];
        }

        if (Auth::attempt($email, $password, $remember)) {
            RateLimit::clear($loginKey);

            // Audit log de login com sucesso
            $user = Auth::user();
            AuditLog::create([
                'user_id' => $user['id'],
                'action' => 'login',
                'entity_type' => 'user',
                'entity_id' => $user['id'],
                'details' => json_encode(['email' => $email]),
                'ip' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'session_id' => Session::id(),
            ]);

            return ['success' => true, 'user' => $user];
        }

        // Incrementa rate limit após falha
        RateLimit::hit($loginKey, 900);

        // Audit log de tentativa falha
        AuditLog::create([
            'user_id' => null,
            'action' => 'login_failed',
            'entity_type' => 'user',
            'entity_id' => null,
            'details' => json_encode(['email' => $email]),
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        return ['success' => false, 'error' => 'Email ou senha incorretos.'];
    }

    /**
     * Registra um novo usuário
     * $isAdmin = true quando admin autenticado cria o usuário
     * $isSelfRegister = true quando via /signup (público)
     */
    public static function register(array $data, bool $isAdmin, bool $isSelfRegister, string $ip = '0.0.0.0'): array
    {
        // Sanitize
        $data['name'] = Validator::sanitize($data['name'] ?? '');
        $data['email'] = Validator::sanitize($data['email'] ?? '');
        $data['role'] = Validator::sanitize($data['role'] ?? '');

        // Self-register: sempre seller
        if ($isSelfRegister) {
            $data['role'] = 'seller';
        }

        // Validar
        $errors = Validator::make($data, [
            'name' => 'required|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required',
            'role' => 'required|in:admin,manager,seller',
        ]);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Verifica confirmação de senha
        if ($data['password'] !== $data['password_confirmation']) {
            return ['success' => false, 'errors' => ['password_confirmation' => 'A senha e a confirmação não conferem.']];
        }

        // Detecção de contas duplicadas por IP
        if (!$isAdmin) {
            $pdo = Model::getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE action = 'user_created' AND ip = :ip AND created_at >= NOW() - INTERVAL '30 days'");
            $stmt->execute(['ip' => $ip]);
            $ipCount = (int)$stmt->fetchColumn();

            if ($ipCount > 0) {
                AuditLog::create([
                    'user_id' => null,
                    'action' => 'duplicate_registration_attempt',
                    'entity_type' => 'user',
                    'entity_id' => null,
                    'details' => json_encode(['email' => $data['email'], 'ip' => $ip]),
                    'ip' => $ip,
                ]);
            }
        }

        try {
            $userId = User::createUser(
                $data['name'],
                $data['email'],
                $data['password'],
                $data['role']
            );

            // Email verification: se admin criou, marcar como verificado
            if ($isAdmin) {
                User::updateById($userId, ['email_verified_at' => date('c')]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'user_created',
                'entity_type' => 'user',
                'entity_id' => $userId,
                'details' => json_encode(['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'ip' => $ip]),
                'ip' => $ip,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);

            // Se self-register, faz login automático
            if ($isSelfRegister) {
                $user = User::findById($userId);
                Auth::login($user);
            }

            return ['success' => true, 'user_id' => $userId];
        } catch (\Throwable $e) {
            Logger::error("AuthService::register error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'errors' => ['general' => 'Erro ao criar usuário. Tente novamente.']];
        }
    }

    /**
     * Gera token de reset de senha e salva na tabela password_resets
     * Retorna ['success' => bool, 'reset_url' => string|null] (dev mode: URL com token)
     */
    public static function createPasswordReset(string $email, string $ip = '0.0.0.0'): array
    {
        $rlKey = RateLimit::keyForIp('forgot');

        // Rate limit: 3 tentativas por IP em 15 minutos
        if (!RateLimit::check($rlKey, 3, 900)) {
            return ['success' => false, 'rate_limited' => true];
        }

        RateLimit::hit($rlKey, 900);

        $user = User::findByEmail($email);
        $resetUrl = null;

        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expires = date('c', time() + 3600); // 1h

                // Invalidar tokens anteriores
                $pdo = Model::getPdo();
                $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE email = :email AND used_at IS NULL");
                $stmt->execute(['email' => $email]);

                // Salvar novo token
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
                $stmt->execute([
                    'email' => $email,
                    'token' => $tokenHash,
                    'expires' => $expires,
                ]);

                // Audit log
                AuditLog::create([
                    'user_id' => $user['id'],
                    'action' => 'password_reset_requested',
                    'entity_type' => 'user',
                    'entity_id' => $user['id'],
                    'details' => json_encode(['email' => $email]),
                    'ip' => $ip,
                ]);

                // DEV MODE: gerar URL com token
                $resetUrl = Config::get('APP_URL', 'https://185.139.1.41:8089') . '/reset-password?token=' . $token . '&email=' . urlencode($email);
                Logger::debug("Password reset URL for {$email}: {$resetUrl}");
            } catch (\Throwable $e) {
                Logger::error("AuthService::createPasswordReset error", ["exception" => $e->getMessage()]);
            }
        }

        return ['success' => true, 'reset_url' => $resetUrl];
    }

    /**
     * Redefine a senha usando token da tabela password_resets
     * Retorna ['success' => bool, 'error' => string|null]
     */
    public static function resetPassword(string $token, string $email, string $password, string $passwordConfirmation, string $ip = '0.0.0.0'): array
    {
        if (empty($token) || empty($email)) {
            return ['success' => false, 'error' => 'Link de recuperação inválido.'];
        }

        // Verificar token na tabela password_resets
        $pdo = Model::getPdo();
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare(
            "SELECT * FROM password_resets 
             WHERE email = :email 
               AND token = :token 
               AND used_at IS NULL 
               AND expires_at > NOW() 
             ORDER BY created_at DESC 
             LIMIT 1"
        );
        $stmt->execute(['email' => $email, 'token' => $tokenHash]);
        $resetRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$resetRecord) {
            return ['success' => false, 'error' => 'Link de recuperação inválido ou expirado. Solicite um novo.'];
        }

        $user = User::findByEmail($email);
        if (!$user) {
            return ['success' => false, 'error' => 'Link de recuperação inválido ou expirado. Solicite um novo.'];
        }

        // Validar senha
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'A senha deve ter no mínimo 8 caracteres.'];
        }

        if ($password !== $passwordConfirmation) {
            return ['success' => false, 'error' => 'A senha e a confirmação não conferem.'];
        }

        try {
            // Marcar token como usado
            $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $resetRecord['id']]);

            // Atualizar senha (NÃO limpa remember_token — BUG-005 fix)
            User::updateById($user['id'], [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $user['id'],
                'action' => 'password_reset_completed',
                'entity_type' => 'user',
                'entity_id' => $user['id'],
                'details' => json_encode(['email' => $email]),
                'ip' => $ip,
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Logger::error("AuthService::resetPassword error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao redefinir senha. Tente novamente.'];
        }
    }

    /**
     * Limpa tokens de reset expirados (chamado por cron)
     */
    public static function cleanupExpiredTokens(): int
    {
        try {
            $pdo = Model::getPdo();
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW() - INTERVAL '7 days'");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            Logger::error("AuthService::cleanupExpiredTokens error", ["exception" => $e->getMessage()]);
            return 0;
        }
    }
}