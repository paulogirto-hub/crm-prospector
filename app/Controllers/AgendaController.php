<?php
/**
 * AgendaController — CRUD de tarefas
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Task;
use App\Models\Lead;
use App\Models\Company;
use AppCoreLogger;

class AgendaController extends Controller
{
    /**
     * GET /agenda — Lista tarefas do usuário
     */
    public function index(): void
    {
        $this->requireLogin();

        $userId = Auth::userId();
        $filter = $this->request->query('filter', 'pending'); // pending, completed, all

        if ($filter === 'completed') {
            $tasks = Task::raw(
                "SELECT t.*, c.name as company_name 
                 FROM tasks t 
                 LEFT JOIN leads l ON l.id = t.lead_id 
                 LEFT JOIN companies c ON c.id = l.company_id 
                 WHERE t.user_id = :uid AND t.completed_at IS NOT NULL 
                 ORDER BY t.completed_at DESC",
                ['uid' => $userId]
            )->fetchAll(\PDO::FETCH_ASSOC);
        } elseif ($filter === 'all') {
            $tasks = Task::raw(
                "SELECT t.*, c.name as company_name 
                 FROM tasks t 
                 LEFT JOIN leads l ON l.id = t.lead_id 
                 LEFT JOIN companies c ON c.id = l.company_id 
                 WHERE t.user_id = :uid 
                 ORDER BY t.completed_at ASC NULLS FIRST, t.due_date ASC NULLS LAST",
                ['uid' => $userId]
            )->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $tasks = Task::raw(
                "SELECT t.*, c.name as company_name 
                 FROM tasks t 
                 LEFT JOIN leads l ON l.id = t.lead_id 
                 LEFT JOIN companies c ON c.id = l.company_id 
                 WHERE t.user_id = :uid AND t.completed_at IS NULL 
                 ORDER BY t.due_date ASC NULLS LAST",
                ['uid' => $userId]
            )->fetchAll(\PDO::FETCH_ASSOC);
        }

        $pendingCount = Task::countPending($userId);
        $completedCount = Task::count("user_id = :uid AND completed_at IS NOT NULL", ['uid' => $userId]);

        $this->render('agenda/index', [
            'title' => 'Agenda — Prospec CRM',
            'tasks' => $tasks,
            'filter' => $filter,
            'pendingCount' => $pendingCount,
            'completedCount' => $completedCount,
        ]);
    }

    /**
     * GET /agenda/create — Form nova tarefa
     */
    public function create(): void
    {
        $this->requireLogin();

        // Busca leads ativos para associar
        $leads = Lead::query()
            ->where('status', 'active')
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get();

        $enrichedLeads = [];
        foreach ($leads as $lead) {
            $company = Company::findById($lead['company_id']);
            $enrichedLeads[] = array_merge($lead, [
                'company_name' => $company['name'] ?? 'N/A',
            ]);
        }

        $this->render('agenda/create', [
            'title' => 'Nova Tarefa — Prospec CRM',
            'leads' => $enrichedLeads,
        ]);
    }

    /**
     * POST /agenda — Salvar tarefa
     */
    public function store(): void
    {
        $this->requireLogin();

        $data = $this->request->only(['lead_id', 'title', 'description', 'due_date']);

        // Sanitize inputs
        $data['title'] = Validator::sanitize($data['title'] ?? '');
        $data['description'] = Validator::sanitize($data['description'] ?? '');

        $errors = $this->validate($data, [
            'title' => 'required|min:2|max:255',
        ]);

        if (!empty($errors)) {
            Flash::error('Corrija os erros abaixo.');
            $leads = Lead::query()->where('status', 'active')->orderBy('created_at', 'DESC')->limit(50)->get();
            $enrichedLeads = [];
            foreach ($leads as $lead) {
                $company = Company::findById($lead['company_id']);
                $enrichedLeads[] = array_merge($lead, ['company_name' => $company['name'] ?? 'N/A']);
            }
            $this->render('agenda/create', [
                'title' => 'Nova Tarefa — Prospec CRM',
                'leads' => $enrichedLeads,
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        try {
            $data['user_id'] = Auth::userId();
            $data['lead_id'] = !empty($data['lead_id']) ? (int)$data['lead_id'] : null;
            $data['due_date'] = !empty($data['due_date']) ? $data['due_date'] : null;

            Task::create($data);

            Flash::success('Tarefa criada com sucesso!');
            Response::redirect('/agenda')->send();
        } catch (\Throwable $e) {
            Logger::error("AgendaController::store error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao criar tarefa. Tente novamente.');
            Response::redirect('/agenda/create')->send();
        }
    }

    /**
     * POST /agenda/{id}/complete — Marcar como completa
     */
    public function complete(string $id): void
    {
        $this->requireLogin();

        $task = Task::findById((int)$id);
        if (!$task || $task['user_id'] !== Auth::userId()) {
            Flash::error('Tarefa não encontrada.');
            Response::redirect('/agenda')->send();
            return;
        }

        try {
            Task::complete((int)$id);

            Flash::success('Tarefa concluída!');
            Response::redirect('/agenda')->send();
        } catch (\Throwable $e) {
            Logger::error("AgendaController::complete error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao concluir tarefa. Tente novamente.');
            Response::redirect('/agenda')->send();
        }
    }

    /**
     * POST /agenda/{id}/delete — Deletar tarefa
     */
    public function delete(string $id): void
    {
        $this->requireLogin();

        $task = Task::findById((int)$id);
        if (!$task || $task['user_id'] !== Auth::userId()) {
            Flash::error('Tarefa não encontrada.');
            Response::redirect('/agenda')->send();
            return;
        }

        try {
            Task::deleteById((int)$id);

            Flash::success('Tarefa removida.');
            Response::redirect('/agenda')->send();
        } catch (\Throwable $e) {
            Logger::error("AgendaController::delete error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao remover tarefa. Tente novamente.');
            Response::redirect('/agenda')->send();
        }
    }
}