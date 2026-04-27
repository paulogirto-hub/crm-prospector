<?php
/**
 * AuthController — Login, registro, logout, perfil
 * 
 * Regras:
 * - Email verification para auto-registro
 * - Detecção de contas duplicadas por IP
 * - LGPD: deletar conta, exportar dados
 * - Try/catch em operações de escrita
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Validator;
use App\Core\Session;
use App\Core\RateLimit;
use App\Core\AuditLog;
use App\Models\User;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Company;
use App\Models\Task;
use AppCoreLogger;

class AuthController extends Controller
{
    /**
     * GET /login — Formulário de login
     */
    public function loginForm(): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard')->send();
            return;
        }

        $this->render('auth/login', [
            'title' => 'Login — Prospec CRM',
        ], 'auth');
    }

    /**
     * POST /login — Autentica usuário
     */
    public function login(): void
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $loginKey = RateLimit::keyForIp('login');

        // Rate limit: 5 tentativas por IP em 15 minutos
        if (!RateLimit::check($loginKey, 5, 900)) {
            $retryAfter = RateLimit::retryAfter($loginKey);
            Flash::error("Muitas tentativas de login. Tente novamente em {$retryAfter} segundos.");
            $this->render('auth/login', [
                'title' => 'Login — Prospec CRM',
            ], 'auth');
            return;
        }

        $email = Validator::sanitize($this->request->input('email', ''));
        $password = $this->request->input('password', '');

        // Validação básica
        $errors = $this->validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );

        if (!empty($errors)) {
            Flash::error('Preencha todos os campos corretamente.');
            $this->render('auth/login', [
                'title' => 'Login — Prospec CRM',
                'errors' => $errors,
                'old' => ['email' => $email],
            ], 'auth');
            return;
        }

        // Tenta autenticar
        if (Auth::attempt($email, $password, (bool)($this->request->input('remember', false)))) {
            RateLimit::clear($loginKey);

            // Verificar se email foi verificado
            $user = Auth::user();
            if (!empty($user['email_verified_at'])) {
                Flash::success('Bem-vindo(a), ' . Auth::user()['name'] . '!');
            } else {
                Flash::warning('Bem-vindo(a), ' . Auth::user()['name'] . '! Seu email ainda não foi verificado. Algumas funcionalidades podem estar limitadas.');
            }

            Response::redirect('/dashboard')->send();
            return;
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
        ]);

        Flash::error('Email ou senha incorretos.');
        $this->render('auth/login', [
            'title' => 'Login — Prospec CRM',
            'old' => ['email' => $email],
        ], 'auth');
    }

    /**
     * GET /register (admin) or /signup (public) — Formulário de registro
     * BUG-010: Suporta self-registration via /signup
     */
    public function registerForm(): void
    {
        // Se já logado e não é admin, bloquear
        if (Auth::check() && !Auth::isAdmin()) {
            Response::abort(403, 'Apenas administradores podem criar novos usuários.');
        }

        // Determinar se é self-register (via /signup) ou admin register
        $isSelfRegister = !Auth::check();

        $this->render('auth/register', [
            'title' => $isSelfRegister ? 'Criar Conta — Prospec CRM' : 'Novo Usuário — Prospec CRM',
            'isSelfRegister' => $isSelfRegister,
        ], 'auth');
    }

    /**
     * POST /register (admin) or /signup (public) — Cria novo usuário
     * BUG-010: Self-registration cria seller com email não verificado
     */
    public function register(): void
    {
        // Se logado, precisa ser admin
        if (Auth::check() && !Auth::isAdmin()) {
            Response::abort(403);
        }

        $isAdmin = Auth::check() && Auth::isAdmin();
        $isSelfRegister = !Auth::check(); // self-register via /signup
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $data = $this->request->only(['name', 'email', 'password', 'password_confirmation', 'role']);

        // Sanitize inputs
        $data['name'] = Validator::sanitize($data['name'] ?? '');
        $data['email'] = Validator::sanitize($data['email'] ?? '');
        $data['role'] = Validator::sanitize($data['role'] ?? '');
        
        // Self-register: sempre seller
        if ($isSelfRegister) {
            $data['role'] = 'seller';
        }

        $errors = $this->validate($data, [
            'name' => 'required|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required',
            'role' => 'required|in:admin,manager,seller',
        ]);

        if (!empty($errors)) {
            Flash::error('Corrija os erros abaixo.');
            $this->render('auth/register', [
                'title' => 'Novo Usuário — Prospec CRM',
                'errors' => $errors,
                'old' => $data,
            ], 'auth');
            return;
        }

        // Verifica confirmação de senha
        if ($data['password'] !== $data['password_confirmation']) {
            Flash::error('A senha e a confirmação não conferem.');
            $this->render('auth/register', [
                'title' => 'Novo Usuário — Prospec CRM',
                'old' => $data,
            ], 'auth');
            return;
        }

        // Detecção de contas duplicadas por IP
        if (!$isAdmin) {
            $pdo = \App\Core\Model::getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE action = 'user_created' AND ip = :ip AND created_at >= NOW() - INTERVAL '30 days'");
            $stmt->execute(['ip' => $ip]);
            $ipCount = (int)$stmt->fetchColumn();

            if ($ipCount > 0) {
                Flash::warning('Já existe uma conta registrada neste endereço nos últimos 30 dias.');
                // Notificar admin via audit log
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
            // Criar o usuário
            $userId = User::createUser(
                $data['name'],
                $data['email'],
                $data['password'],
                $data['role']
            );

            // Email verification: se admin criou, marcar como verificado
            if ($isAdmin) {
                User::updateById($userId, ['email_verified_at' => date('c')]);
            } else {
                // Auto-registro: NÃO verificado (por enquanto só marca como verificado se admin)
                // TODO: enviar email de verificação
                User::updateById($userId, ['email_verified_at' => null]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'user_created',
                'entity_type' => 'user',
                'entity_id' => $userId,
                'details' => json_encode(['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'ip' => $ip]),
                'ip' => $ip,
            ]);

            Flash::success("Usuário '{$data['name']}' criado com sucesso!");

            // Se não está logado (primeiro usuário), faz login automático
            if (!Auth::check()) {
                $user = User::findById($userId);
                Auth::login($user);
                Response::redirect('/dashboard')->send();
                return;
            }

            Response::redirect('/settings/team')->send();
        } catch (\Throwable $e) {
            Logger::error("AuthController::register error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao criar usuário. Tente novamente.');
            Response::redirect('/register')->send();
        }
    }

    /**
     * POST /logout — Destroi sessão
     */
    public function logout(): void
    {
        Auth::logout();
        Flash::success('Você saiu do sistema.');
        Response::redirect('/login')->send();
    }

    /**
     * GET /profile — Editar perfil
     */
    public function profile(): void
    {
        $this->requireLogin();
        $user = Auth::user();

        $this->render('auth/profile', [
            'title' => 'Meu Perfil — Prospec CRM',
            'user' => $user,
        ]);
    }

    /**
     * POST /profile — Atualizar perfil
     */
    public function updateProfile(): void
    {
        $this->requireLogin();
        
        $userId = Auth::userId();
        $data = $this->request->only(['name', 'email', 'password', 'password_confirmation', 'current_password']);

        // Sanitize inputs
        $data['name'] = Validator::sanitize($data['name'] ?? '');
        $data['email'] = Validator::sanitize($data['email'] ?? '');
        
        $errors = $this->validate($data, [
            'name' => 'required|min:2|max:255',
            'email' => "required|email|unique:users,email,{$userId}",
        ]);

        if (!empty($errors)) {
            Flash::error('Corrija os erros abaixo.');
            $this->render('auth/profile', [
                'title' => 'Meu Perfil — Prospec CRM',
                'user' => Auth::user(),
                'errors' => $errors,
            ]);
            return;
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        // Se quer trocar a senha
        if (!empty($data['password'])) {
            $user = Auth::user();
            if (!password_verify($data['current_password'] ?? '', $user['password_hash'])) {
                Flash::error('Senha atual incorreta.');
                $this->render('auth/profile', [
                    'title' => 'Meu Perfil — Prospec CRM',
                    'user' => $user,
                ]);
                return;
            }
            if (strlen($data['password']) < 8) {
                Flash::error('A nova senha deve ter no mínimo 8 caracteres.');
                $this->render('auth/profile', [
                    'title' => 'Meu Perfil — Prospec CRM',
                    'user' => $user,
                ]);
                return;
            }
            if ($data['password'] !== ($data['password_confirmation'] ?? '')) {
                Flash::error('A nova senha e a confirmação não conferem.');
                $this->render('auth/profile', [
                    'title' => 'Meu Perfil — Prospec CRM',
                    'user' => $user,
                ]);
                return;
            }
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        try {
            User::updateById($userId, $updateData);
            
            // Atualiza dados na sessão
            Session::set('user_name', $data['name']);
            Session::set('user_email', $data['email']);

            Flash::success('Perfil atualizado com sucesso!');
            $this->render('auth/profile', [
                'title' => 'Meu Perfil — Prospec CRM',
                'user' => User::findById($userId),
            ]);
        } catch (\Throwable $e) {
            Logger::error("AuthController::updateProfile error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao atualizar perfil. Tente novamente.');
            $this->render('auth/profile', [
                'title' => 'Meu Perfil — Prospec CRM',
                'user' => Auth::user(),
            ]);
        }
    }

    // ══════════════════════════════════════
    // LGPD — Deletar conta e exportar dados
    // ══════════════════════════════════════

    /**
     * GET /account/delete — Página de confirmação de exclusão de conta
     */
    public function deleteAccountForm(): void
    {
        $this->requireLogin();
        $userId = Auth::userId();

        // Contar dependentes
        $leadCount = Lead::count('assigned_to = :uid', ['uid' => $userId]);
        $taskCount = Task::count('user_id = :uid', ['uid' => $userId]);
        $companyCount = Company::count('created_by = :uid', ['uid' => $userId]);

        $this->render('auth/delete_account', [
            'title' => 'Deletar Conta — Prospec CRM',
            'leadCount' => $leadCount,
            'taskCount' => $taskCount,
            'companyCount' => $companyCount,
        ], 'auth');
    }

    /**
     * POST /account/delete — Deleta usuário + dados relacionados + sessão
     */
    public function deleteAccount(): void
    {
        $this->requireLogin();
        $userId = Auth::userId();

        // Confirmar com senha
        $password = $this->request->input('password', '');
        $user = Auth::user();

        if (!password_verify($password, $user['password_hash'])) {
            Flash::error('Senha incorreta. A exclusão da conta não foi realizada.');
            Response::redirect('/account/delete')->send();
            return;
        }

        // Verificar se tem leads — reatribuir ao admin
        $leadCount = Lead::count('assigned_to = :uid', ['uid' => $userId]);
        if ($leadCount > 0) {
            // Encontrar admin para reatribuir
            $admin = User::byRole('admin');
            $adminId = !empty($admin) ? (int)$admin[0]['id'] : 1;

            $pdo = \App\Core\Model::getPdo();
            $stmt = $pdo->prepare("UPDATE leads SET assigned_to = :admin_id WHERE assigned_to = :uid");
            $stmt->execute(['admin_id' => $adminId, 'uid' => $userId]);

            Flash::warning("Seus {$leadCount} leads foram reatribuídos ao administrador.");
        }

        try {
            // Audit log antes de deletar
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'account_deleted',
                'entity_type' => 'user',
                'entity_id' => $userId,
                'details' => json_encode(['name' => $user['name'], 'email' => $user['email']]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            // Deletar tasks do usuário
            $pdo = \App\Core\Model::getPdo();
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE user_id = :uid");
            $stmt->execute(['uid' => $userId]);

            // Deletar atividades do usuário (manter as dos leads)
            $stmt = $pdo->prepare("DELETE FROM lead_activities WHERE user_id = :uid");
            $stmt->execute(['uid' => $userId]);

            // Desativar o usuário (soft delete — LGPD)
            User::updateById($userId, [
                'active' => false,
                'name' => 'Conta Excluída',
                'email' => 'deleted_' . $userId . '@prospec.com.br',
                'password_hash' => '',
            ]);

            // Destruir sessão
            Auth::logout();

            Flash::success('Sua conta foi excluída com sucesso. Todos os dados foram removidos conforme a LGPD.');
            Response::redirect('/login')->send();
        } catch (\Throwable $e) {
            Logger::error("AuthController::deleteAccount error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao excluir conta. Tente novamente ou entre em contato com o suporte.');
            Response::redirect('/account/delete')->send();
        }
    }

    /**
     * GET /account/export — Exportar dados pessoais (LGPD portabilidade)
     */
    public function exportAccount(): void
    {
        $this->requireLogin();
        $userId = Auth::userId();

        try {
            $pdo = \App\Core\Model::getPdo();

            // Dados do perfil
            $user = User::findById($userId);
            $profileData = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'created_at' => $user['created_at'],
            ];

            // Leads do usuário
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE assigned_to = :uid ORDER BY created_at DESC");
            $stmt->execute(['uid' => $userId]);
            $leadsData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Atividades do usuário
            $stmt = $pdo->prepare("SELECT * FROM lead_activities WHERE user_id = :uid ORDER BY created_at DESC");
            $stmt->execute(['uid' => $userId]);
            $activitiesData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Tarefas do usuário
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = :uid ORDER BY created_at DESC");
            $stmt->execute(['uid' => $userId]);
            $tasksData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Gerar CSV
            $filename = 'prospec_crm_dados_' . $userId . '_' . date('Y-m-d_His') . '.csv';
            $filepath = dirname(__DIR__, 2) . '/storage/' . $filename;

            $fp = fopen($filepath, 'w');
            // BOM for UTF-8 in Excel
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

            // Seção: Perfil
            fputcsv($fp, ['=== PERFIL DO USUÁRIO ===']);
            fputcsv($fp, ['Campo', 'Valor']);
            foreach ($profileData as $key => $value) {
                fputcsv($fp, [$key, $value]);
            }
            fputcsv($fp, []);

            // Seção: Leads
            fputcsv($fp, ['=== LEADS ===']);
            if (!empty($leadsData)) {
                fputcsv($fp, array_keys($leadsData[0]));
                foreach ($leadsData as $row) {
                    fputcsv($fp, $row);
                }
            } else {
                fputcsv($fp, ['Nenhum lead encontrado']);
            }
            fputcsv($fp, []);

            // Seção: Atividades
            fputcsv($fp, ['=== ATIVIDADES ===']);
            if (!empty($activitiesData)) {
                fputcsv($fp, array_keys($activitiesData[0]));
                foreach ($activitiesData as $row) {
                    fputcsv($fp, $row);
                }
            } else {
                fputcsv($fp, ['Nenhuma atividade encontrada']);
            }
            fputcsv($fp, []);

            // Seção: Tarefas
            fputcsv($fp, ['=== TAREFAS ===']);
            if (!empty($tasksData)) {
                fputcsv($fp, array_keys($tasksData[0]));
                foreach ($tasksData as $row) {
                    fputcsv($fp, $row);
                }
            } else {
                fputcsv($fp, ['Nenhuma tarefa encontrada']);
            }

            fclose($fp);

            // Audit log
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'export',
                'entity_type' => 'user',
                'entity_id' => $userId,
                'details' => json_encode(['filename' => $filename]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            // Download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            @unlink($filepath);
            exit;
        } catch (\Throwable $e) {
            Logger::error("AuthController::exportAccount error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao exportar dados. Tente novamente.');
            Response::redirect('/profile')->send();
        }
    }

    /**
     * GET /forgot-password — Formulário de recuperação de senha
     */
    public function forgotPasswordForm(): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard')->send();
            return;
        }

        $this->render('auth/forgot-password', [
            'title' => 'Recuperar Senha — Prospec CRM',
        ], 'auth');
    }

    /**
     * POST /forgot-password — Enviar link de recuperação
     * BUG-002/003/005 FIX: Usa tabela password_resets dedicada (não remember_token)
     */
    public function forgotPassword(): void
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rlKey = RateLimit::keyForIp('forgot');

        // Rate limit: 3 tentativas por IP em 15 minutos
        if (!RateLimit::check($rlKey, 3, 900)) {
            Flash::error('Muitas tentativas. Aguarde alguns minutos.');
            $this->render('auth/forgot-password', [
                'title' => 'Recuperar Senha — Prospec CRM',
            ], 'auth');
            return;
        }

        $email = Validator::sanitize($this->request->input('email', ''));

        if (empty($email) || !Validator::email($email)) {
            Flash::error('Informe um e-mail válido.');
            $this->render('auth/forgot-password', [
                'title' => 'Recuperar Senha — Prospec CRM',
                'old' => ['email' => $email],
            ], 'auth');
            return;
        }

        RateLimit::hit($rlKey, 900);

        // Buscar usuário pelo e-mail
        $user = User::findByEmail($email);

        // Sempre mostrar a mesma mensagem (não vazar se e-mail existe)
        Flash::success('Se o e-mail estiver cadastrado, você receberá instruções para redefinir sua senha.');
        $this->render('auth/forgot-password', [
            'title' => 'Recuperar Senha — Prospec CRM',
        ], 'auth');

        // Se usuário existe, gerar token e "enviar" e-mail
        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token); // Armazenar hash, não token plain
                $expires = date('c', time() + 3600); // 1h expiração

                // Invalidar tokens anteriores deste email
                $pdo = \App\Core\Model::getPdo();
                $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE email = :email AND used_at IS NULL");
                $stmt->execute(['email' => $email]);

                // Salvar novo token na tabela dedicada password_resets
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

                // DEV MODE: logar token para debug (remover em produção quando tiver email)
                $resetUrl = Config::get('APP_URL', 'https://185.139.1.41:8089') . '/reset-password?token=' . $token . '&email=' . urlencode($email);
                Logger::debug("Password reset URL for {$email}: {$resetUrl}");
            } catch (\Throwable $e) {
                Logger::error("AuthController::forgotPassword error", ["exception" => $e->getMessage()]);
            }
        }
    }

    /**
     * GET /reset-password — Formulário de redefinição de senha
     */
    public function resetPasswordForm(): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard')->send();
            return;
        }

        $token = $this->request->query('token', '');
        $email = $this->request->query('email', '');

        if (empty($token) || empty($email)) {
            Flash::error('Link de recuperação inválido ou expirado.');
            Response::redirect('/forgot-password')->send();
            return;
        }

        $this->render('auth/reset-password', [
            'title' => 'Redefinir Senha — Prospec CRM',
            'token' => $token,
            'email' => $email,
        ], 'auth');
    }

    /**
     * POST /reset-password — Redefinir senha
     * BUG-002/003/005 FIX: Usa tabela password_resets com expiração
     */
    public function resetPassword(): void
    {
        $token = $this->request->input('token', '');
        $email = $this->request->input('email', '');
        $password = $this->request->input('password', '');
        $passwordConfirmation = $this->request->input('password_confirmation', '');

        if (empty($token) || empty($email)) {
            Flash::error('Link de recuperação inválido.');
            Response::redirect('/forgot-password')->send();
            return;
        }

        // Verificar token na tabela password_resets (não no remember_token)
        $pdo = \App\Core\Model::getPdo();
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
            Flash::error('Link de recuperação inválido ou expirado. Solicite um novo.');
            Response::redirect('/forgot-password')->send();
            return;
        }

        // Verificar se usuário existe
        $user = User::findByEmail($email);
        if (!$user) {
            Flash::error('Link de recuperação inválido ou expirado. Solicite um novo.');
            Response::redirect('/forgot-password')->send();
            return;
        }

        // Validar senha
        if (strlen($password) < 8) {
            Flash::error('A senha deve ter no mínimo 8 caracteres.');
            $this->render('auth/reset-password', [
                'title' => 'Redefinir Senha — Prospec CRM',
                'token' => $token,
                'email' => $email,
            ], 'auth');
            return;
        }

        if ($password !== $passwordConfirmation) {
            Flash::error('A senha e a confirmação não conferem.');
            $this->render('auth/reset-password', [
                'title' => 'Redefinir Senha — Prospec CRM',
                'token' => $token,
                'email' => $email,
            ], 'auth');
            return;
        }

        try {
            // Marcar token como usado
            $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $resetRecord['id']]);

            // Atualizar senha — NÃO limpa remember_token (BUG-005 fix)
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
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Flash::success('Senha redefinida com sucesso! Faça login com sua nova senha.');
            Response::redirect('/login')->send();
        } catch (\Throwable $e) {
            Logger::error("AuthController::resetPassword error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao redefinir senha. Tente novamente.');
            Response::redirect('/forgot-password')->send();
        }
    }
}