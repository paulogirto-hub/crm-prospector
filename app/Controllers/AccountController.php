<?php
/**
 * AccountController — LGPD compliance (deletar conta, exportar dados)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Models\Lead;
use App\Models\Task;
use App\Models\Company;

class AccountController extends Controller
{
    /**
     * GET /account/delete — Página de confirmação de exclusão de conta
     */
    public function deleteForm(): void
    {
        $this->requireLogin();

        $userId = Auth::userId();
        $leadCount = Lead::count('assigned_to = :uid', ['uid' => $userId]);
        $taskCount = Task::count('user_id = :uid', ['uid' => $userId]);
        $companyCount = Company::count('created_by = :uid', ['uid' => $userId]);

        $this->render('account/delete', [
            'title' => 'Excluir Conta — Prospec CRM',
            'leadCount' => $leadCount,
            'taskCount' => $taskCount,
            'companyCount' => $companyCount,
        ], 'auth');
    }

    /**
     * POST /account/delete — Deleta conta
     */
    public function delete(): void
    {
        $this->requireLogin();
        
        $auth = new AuthController();
        $auth->deleteAccount();
    }

    /**
     * GET /account/export — Página de exportação de dados (LGPD portabilidade)
     */
    public function export(): void
    {
        $this->requireLogin();
        
        $userId = Auth::userId();
        $leadCount = Lead::count('assigned_to = :uid', ['uid' => $userId]);
        $taskCount = Task::count('user_id = :uid', ['uid' => $userId]);
        $companyCount = Company::count('created_by = :uid', ['uid' => $userId]);

        $this->render('account/export', [
            'title' => 'Exportar Dados — Prospec CRM',
            'leadCount' => $leadCount,
            'taskCount' => $taskCount,
            'companyCount' => $companyCount,
        ], 'auth');
    }

    /**
     * POST /account/export — Download do arquivo CSV com dados pessoais
     */
    public function exportDownload(): void
    {
        $this->requireLogin();
        
        $auth = new AuthController();
        $auth->exportAccount();
    }
}