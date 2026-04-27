<?php
/**
 * LeadController — CRUD e gestão de leads
 * 
 * Regras de negócio:
 * - Pipeline transitions validadas via PipelineRules
 * - Anti-duplicação (mesma empresa + mesmo vendedor)
 * - Verificação de limites do plano
 * - Try/catch em todas as operações de escrita
 * - Rate limiting por ação
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Validator;
use App\Core\AuditLog;
use App\Core\PipelineRules;
use App\Core\PlanLimits;
use App\Core\RateLimit;
use App\Models\Lead;
use App\Models\Company;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use App\Models\Task;
use AppCoreLogger;

class LeadController extends Controller
{
    /**
     * GET /leads — Lista leads com filtros e paginação
     */
    public function index(): void
    {
        $this->requireLogin();

        $page = max(1, (int)($this->request->query('page', 1)));
        $perPage = 20;
        $search = $this->request->query('search', '');
        $status = $this->request->query('status', '');
        $stageId = $this->request->query('stage', '');

        $query = Lead::query();

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'active');
        }

        if ($stageId) {
            $query->where('pipeline_stage_id', (int)$stageId);
        }

        $query->orderBy('created_at', 'DESC');
        $pagination = $query->paginate($page, $perPage);

        // Se tem busca, filtra por nome da empresa
        if ($search) {
            $leads = [];
            foreach ($pagination['data'] as $lead) {
                $company = Company::findById($lead['company_id']);
                if ($company && (stripos($company['name'], $search) !== false || stripos($company['email'] ?? '', $search) !== false)) {
                    $leads[] = $lead;
                }
            }
            $pagination['data'] = $leads;
        }

        // Enrich com dados da empresa e stage
        $leads = $pagination['data'];
        $enrichedLeads = [];
        foreach ($leads as $lead) {
            $company = Company::findById($lead['company_id']);
            $stage = PipelineStage::findById($lead['pipeline_stage_id']);
            $enrichedLeads[] = array_merge($lead, [
                'company_name' => $company['name'] ?? 'N/A',
                'company_niche' => $company['niche'] ?? '',
                'company_city' => $company['city'] ?? '',
                'company_state' => $company['state'] ?? '',
                'stage_name' => $stage['name'] ?? 'N/A',
                'stage_color' => $stage['color'] ?? '#6c5ce7',
                'is_final' => PipelineRules::isFinalStage($lead['pipeline_stage_id']),
            ]);
        }
        $pagination['data'] = $enrichedLeads;

        $stages = PipelineStage::ordered();
        $totalLeads = Lead::countActive();

        $this->render('leads/index', [
            'title' => 'Leads — Prospec CRM',
            'leads' => $pagination,
            'stages' => $stages,
            'totalLeads' => $totalLeads,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'stage' => $stageId,
            ],
        ]);
    }

    /**
     * GET /leads/{id} — Detalhe do lead
     */
    public function show(string $id): void
    {
        $this->requireLogin();

        $lead = Lead::findById((int)$id);
        if (!$lead) {
            Flash::error('Lead não encontrado.');
            Response::redirect('/leads')->send();
            return;
        }

        $company = Company::findById($lead['company_id']);
        $stage = PipelineStage::findById($lead['pipeline_stage_id']);
        $activities = LeadActivity::findByLead((int)$id);
        $tasks = Task::query()->where('lead_id', (int)$id)->orderBy('due_date', 'ASC')->get();
        $stages = PipelineStage::ordered();

        // Estágios válidos para transição (para o select no frontend)
        $validTargetStages = PipelineRules::getValidTargetStages((int)$lead['pipeline_stage_id']);
        $isFinal = PipelineRules::isFinalStage((int)$lead['pipeline_stage_id']);

        $this->render('leads/show', [
            'title' => ($company['name'] ?? 'Lead') . ' — Prospec CRM',
            'lead' => $lead,
            'company' => $company,
            'stage' => $stage,
            'stages' => $stages,
            'activities' => $activities,
            'tasks' => $tasks,
            'validTargetStages' => $validTargetStages,
            'isFinal' => $isFinal,
        ]);
    }

    /**
     * GET /leads/create — Form novo lead
     */
    public function create(): void
    {
        $this->requireLogin();

        $stages = PipelineStage::ordered();
        $companies = Company::query()->orderBy('name', 'ASC')->limit(100)->get();

        $this->render('leads/create', [
            'title' => 'Novo Lead — Prospec CRM',
            'stages' => $stages,
            'companies' => $companies,
        ]);
    }

    /**
     * POST /leads — Salvar lead
     */
    public function store(): void
    {
        $this->requireLogin();

        // Rate limit: 20 leads/hora por usuário
        $rlKey = 'lead_create:' . Auth::userId();
        if (!RateLimit::check($rlKey, 20, 3600)) {
            Flash::error('Limite atingido: máximo de 20 leads por hora.');
            Response::redirect('/leads/create')->send();
            return;
        }
        RateLimit::hit($rlKey, 3600);

        // Verificar limite do plano
        if (!PlanLimits::canCreateLead(Auth::userId())) {
            $remaining = PlanLimits::getRemaining('leads', Auth::userId());
            Flash::error("Limite do plano atingido. Você já usou todos os leads disponíveis (restam {$remaining}). Considere fazer upgrade do seu plano.");
            Response::redirect('/leads/create')->send();
            return;
        }

        $data = $this->request->only([
            'company_id', 'pipeline_stage_id', 'assigned_to', 'score',
            'source', 'status', 'estimated_value', 'ia_analise', 'ia_market_analysis'
        ]);

        // Sanitize string inputs
        $data['source'] = Validator::sanitize($data['source'] ?? '');
        $data['status'] = Validator::sanitize($data['status'] ?? 'active');
        $data['ia_analise'] = Validator::sanitize($data['ia_analise'] ?? '');
        $data['ia_market_analysis'] = Validator::sanitize($data['ia_market_analysis'] ?? '');

        // Validate integer fields
        if (!empty($data['company_id']) && $data['company_id'] !== 'new' && !Validator::integer($data['company_id'])) {
            Flash::error('Empresa inválida.');
            Response::redirect('/leads/create')->send();
            return;
        }
        if (!empty($data['assigned_to']) && !Validator::integer($data['assigned_to'])) {
            $data['assigned_to'] = Auth::userId();
        }
        if (!empty($data['score']) && !Validator::integer($data['score'])) {
            $data['score'] = 0;
        }

        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : Auth::userId();

        // Se company_id é "new", criar empresa nova
        $newCompanyData = $this->request->only(['new_company_name', 'new_company_niche', 'new_company_city', 'new_company_state', 'new_company_phone', 'new_company_email']);
        if (empty($data['company_id']) || $data['company_id'] === 'new') {
            if (!empty($newCompanyData['new_company_name'])) {
                try {
                    $companyId = Company::create([
                        'name' => $newCompanyData['new_company_name'],
                        'niche' => $newCompanyData['new_company_niche'] ?? '',
                        'city' => $newCompanyData['new_company_city'] ?? '',
                        'state' => $newCompanyData['new_company_state'] ?? '',
                        'phone' => $newCompanyData['new_company_phone'] ?? '',
                        'email' => $newCompanyData['new_company_email'] ?? '',
                        'created_by' => Auth::userId(),
                    ]);
                    $data['company_id'] = $companyId;
                } catch (\Throwable $e) {
                    Logger::error("LeadController::store create company error", ["exception" => $e->getMessage()]);
                    Flash::error('Erro ao criar empresa. Tente novamente.');
                    Response::redirect('/leads/create')->send();
                    return;
                }
            } else {
                Flash::error('Selecione uma empresa existente ou forneça o nome da nova.');
                $stages = PipelineStage::ordered();
                $companies = Company::query()->orderBy('name', 'ASC')->limit(100)->get();
                $this->render('leads/create', [
                    'title' => 'Novo Lead — Prospec CRM',
                    'stages' => $stages,
                    'companies' => $companies,
                    'old' => $data,
                ]);
                return;
            }
        }

        if (empty($data['pipeline_stage_id'])) {
            $defaultStage = PipelineStage::defaultStage();
            $data['pipeline_stage_id'] = $defaultStage['id'] ?? 1;
        }

        $data['score'] = (int)($data['score'] ?? 0);
        $data['estimated_value'] = floatval($data['estimated_value'] ?? 0);
        $data['assigned_to'] = $assignedTo;

        // Anti-duplicação: verificar se já existe lead ativo com mesma empresa + mesmo vendedor
        $existingLead = Lead::query()
            ->where('company_id', (int)$data['company_id'])
            ->where('assigned_to', $assignedTo)
            ->where('status', 'active')
            ->first();

        if ($existingLead) {
            Flash::warning("Já existe um lead ativo (#{$existingLead['id']}) para essa empresa atribuído a você.");
            Response::redirect('/leads/' . $existingLead['id'])->send();
            return;
        }

        try {
            $leadId = Lead::create($data);

            // Criar atividade inicial
            LeadActivity::create([
                'lead_id' => $leadId,
                'user_id' => Auth::userId(),
                'type' => 'created',
                'description' => 'Lead criado',
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'lead_created',
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'details' => json_encode(['company_id' => $data['company_id']]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Flash::success('Lead criado com sucesso!');
            Response::redirect('/leads/' . $leadId)->send();
        } catch (\Throwable $e) {
            Logger::error("LeadController::store error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao criar lead. Tente novamente.');
            Response::redirect('/leads/create')->send();
        }
    }

    /**
     * GET /leads/{id}/edit — Editar lead
     */
    public function edit(string $id): void
    {
        $this->requireLogin();

        $lead = Lead::findById((int)$id);
        if (!$lead) {
            Flash::error('Lead não encontrado.');
            Response::redirect('/leads')->send();
            return;
        }

        $company = Company::findById($lead['company_id']);
        $stages = PipelineStage::ordered();
        $companies = Company::query()->orderBy('name', 'ASC')->limit(100)->get();

        // Estágios válidos para transição
        $validTargetStages = PipelineRules::getValidTargetStages((int)$lead['pipeline_stage_id']);
        $isFinal = PipelineRules::isFinalStage((int)$lead['pipeline_stage_id']);

        $this->render('leads/edit', [
            'title' => 'Editar Lead — Prospec CRM',
            'lead' => $lead,
            'company' => $company,
            'stages' => $stages,
            'companies' => $companies,
            'validTargetStages' => $validTargetStages,
            'isFinal' => $isFinal,
        ]);
    }

    /**
     * POST /leads/{id} — Atualizar lead (method spoofing PUT)
     */
    public function update(string $id): void
    {
        $this->requireLogin();

        $lead = Lead::findById((int)$id);
        if (!$lead) {
            Flash::error('Lead não encontrado.');
            Response::redirect('/leads')->send();
            return;
        }

        $data = $this->request->only([
            'company_id', 'pipeline_stage_id', 'assigned_to', 'score',
            'source', 'status', 'estimated_value', 'ia_analise', 'ia_market_analysis', 'last_contact_at'
        ]);

        // Verificar se está tentando mudar o estágio do pipeline
        $newStageId = isset($data['pipeline_stage_id']) && $data['pipeline_stage_id'] !== '' 
            ? (int)$data['pipeline_stage_id'] 
            : (int)$lead['pipeline_stage_id'];
        $currentStageId = (int)$lead['pipeline_stage_id'];

        if ($newStageId !== $currentStageId) {
            // Verificar regras de transição do pipeline
            if (!PipelineRules::canTransition($currentStageId, $newStageId)) {
                $errorMsg = PipelineRules::getTransitionErrorMessage($currentStageId, $newStageId);
                if ($this->request->isAjax()) {
                    Response::json(['error' => $errorMsg], 422)->send();
                    return;
                }
                Flash::error($errorMsg);
                Response::redirect('/leads/' . $id . '/edit')->send();
                return;
            }

            // Registra atividade de mudança de estágio
            $stage = PipelineStage::findById($newStageId);
            LeadActivity::create([
                'lead_id' => (int)$id,
                'user_id' => Auth::userId(),
                'type' => 'stage_change',
                'description' => "Lead movido de '" . PipelineRules::getStageName($currentStageId) . "' para: " . ($stage['name'] ?? 'Desconhecido'),
            ]);
        }

        // Remove empty values to avoid overwriting with blanks
        $data = array_filter($data, fn($v) => $v !== '');

        $data['score'] = (int)($data['score'] ?? $lead['score']);
        $data['estimated_value'] = floatval($data['estimated_value'] ?? $lead['estimated_value']);

        try {
            Lead::updateById((int)$id, $data);

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'lead_updated',
                'entity_type' => 'lead',
                'entity_id' => (int)$id,
                'details' => json_encode($data),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Flash::success('Lead atualizado com sucesso!');
            Response::redirect('/leads/' . $id)->send();
        } catch (\Throwable $e) {
            Logger::error("LeadController::update error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao atualizar lead. Tente novamente.');
            Response::redirect('/leads/' . $id . '/edit')->send();
        }
    }

    /**
     * POST /leads/{id}/stage — Mover lead de stage no pipeline
     */
    public function moveStage(string $id): void
    {
        $this->requireLogin();

        $lead = Lead::findById((int)$id);
        if (!$lead) {
            if ($this->request->isAjax()) {
                Response::json(['error' => 'Lead não encontrado'], 404)->send();
                return;
            }
            Flash::error('Lead não encontrado.');
            Response::redirect('/leads')->send();
            return;
        }

        $currentStageId = (int)$lead['pipeline_stage_id'];

        // Verificar se estágio atual é final
        if (PipelineRules::isFinalStage($currentStageId)) {
            $errorMsg = "Lead em 'Fechado' é final e não pode ser movido.";
            if ($this->request->isAjax()) {
                Response::json(['error' => $errorMsg], 422)->send();
                return;
            }
            Flash::error($errorMsg);
            Response::redirect('/leads/' . $id)->send();
            return;
        }

        $stageId = (int)$this->request->input('pipeline_stage_id', 0);
        $stage = PipelineStage::findById($stageId);
        if (!$stage) {
            if ($this->request->isAjax()) {
                Response::json(['error' => 'Stage inválido'], 400)->send();
                return;
            }
            Flash::error('Stage inválido.');
            Response::redirect('/leads/' . $id)->send();
            return;
        }

        // Verificar se a transição é permitida
        if (!PipelineRules::canTransition($currentStageId, $stageId)) {
            $errorMsg = PipelineRules::getTransitionErrorMessage($currentStageId, $stageId);
            if ($this->request->isAjax()) {
                Response::json(['error' => $errorMsg, 'valid_transitions' => PipelineRules::getValidTransitions($currentStageId)], 422)->send();
                return;
            }
            Flash::error($errorMsg);
            Response::redirect('/leads/' . $id)->send();
            return;
        }

        // Exigir motivo/nota ao mover de estágio
        $reason = trim($this->request->input('reason', ''));
        if (empty($reason)) {
            // Motivo é obrigatório para mudança de estágio
            if ($this->request->isAjax()) {
                Response::json(['error' => 'Informe o motivo da mudança de estágio.', 'require_reason' => true], 422)->send();
                return;
            }
            Flash::error('Informe o motivo da mudança de estágio.');
            Response::redirect('/leads/' . $id)->send();
            return;
        }

        try {
            Lead::updateById((int)$id, ['pipeline_stage_id' => $stageId]);

            LeadActivity::create([
                'lead_id' => (int)$id,
                'user_id' => Auth::userId(),
                'type' => 'stage_change',
                'description' => "Lead movido de '" . PipelineRules::getStageName($currentStageId) . "' para: {$stage['name']}. Motivo: {$reason}",
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'lead_stage_change',
                'entity_type' => 'lead',
                'entity_id' => (int)$id,
                'details' => json_encode(['from' => $currentStageId, 'to' => $stageId, 'reason' => $reason]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            if ($this->request->isAjax()) {
                Response::json(['success' => true, 'stage' => $stage])->send();
                return;
            }

            Flash::success("Lead movido para: {$stage['name']}");
            Response::redirect('/leads/' . $id)->send();
        } catch (\Throwable $e) {
            Logger::error("LeadController::moveStage error", ["exception" => $e->getMessage()]);
            if ($this->request->isAjax()) {
                Response::json(['error' => 'Erro ao mover lead. Tente novamente.'], 500)->send();
                return;
            }
            Flash::error('Erro ao mover lead. Tente novamente.');
            Response::redirect('/leads/' . $id)->send();
        }
    }

    /**
     * POST /leads/{id}/activity — Adicionar atividade ao lead
     */
    public function addActivity(string $id): void
    {
        $this->requireLogin();

        $lead = Lead::findById((int)$id);
        if (!$lead) {
            Flash::error('Lead não encontrado.');
            Response::redirect('/leads')->send();
            return;
        }

        $type = $this->request->input('type', 'note');
        $description = $this->request->input('description', '');

        if (empty($description)) {
            Flash::error('Descreva a atividade.');
            Response::redirect('/leads/' . $id)->send();
            return;
        }

        try {
            LeadActivity::create([
                'lead_id' => (int)$id,
                'user_id' => Auth::userId(),
                'type' => $type,
                'description' => $description,
            ]);

            // Atualizar last_contact_at se for contato
            if (in_array($type, ['call', 'email', 'whatsapp', 'meeting'])) {
                Lead::updateById((int)$id, ['last_contact_at' => date('c')]);
            }

            Flash::success('Atividade registrada!');
            Response::redirect('/leads/' . $id)->send();
        } catch (\Throwable $e) {
            Logger::error("LeadController::addActivity error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao registrar atividade. Tente novamente.');
            Response::redirect('/leads/' . $id)->send();
        }
    }

    /**
     * POST /leads/{id}/delete — Deletar lead (method spoofing DELETE)
     */
    public function delete(string $id): void
    {
        $this->requireLogin();

        $lead = Lead::findById((int)$id);
        if (!$lead) {
            Flash::error('Lead não encontrado.');
            Response::redirect('/leads')->send();
            return;
        }

        // Proteger: não deletar lead com tasks vinculadas
        $pendingTasks = Task::query()->where('lead_id', (int)$id)->where('completed_at', null)->get();
        $totalTaskCount = Task::count('lead_id = :lid', ['lid' => (int)$id]);

        if (!empty($pendingTasks)) {
            Flash::error('Não é possível remover este lead — existem tarefas pendentes vinculadas. Conclua ou remova as tarefas primeiro.');
            Response::redirect('/leads/' . $id)->send();
            return;
        }

        if ($totalTaskCount > 0) {
            // Lead tem tasks concluídas — deletar em cascade antes de remover
            Task::raw('DELETE FROM tasks WHERE lead_id = :lid', ['lid' => (int)$id]);
        }

        try {
            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'lead_deleted',
                'entity_type' => 'lead',
                'entity_id' => (int)$id,
                'details' => json_encode(['lead_id' => (int)$id]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Lead::updateById((int)$id, ['status' => 'deleted']);
            Flash::success('Lead removido com sucesso.');
            Response::redirect('/leads')->send();
        } catch (\Throwable $e) {
            Logger::error("LeadController::delete error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao remover lead. Tente novamente.');
            Response::redirect('/leads/' . $id)->send();
        }
    }
}