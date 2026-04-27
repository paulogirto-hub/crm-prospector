<?php
/**
 * PipelineController — Kanban board com drag-and-drop
 * 
 * Regras: transições validadas via PipelineRules
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Core\PipelineRules;
use App\Core\AuditLog;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Company;
use App\Models\LeadActivity;
use AppCoreLogger;

class PipelineController extends Controller
{
    /**
     * GET /pipeline — Kanban board
     */
    public function index(): void
    {
        $this->requireLogin();

        $stages = PipelineStage::ordered();
        $pipelineData = [];

        foreach ($stages as $stage) {
            $leads = Lead::query()
                ->where('pipeline_stage_id', $stage['id'])
                ->where('status', 'active')
                ->orderBy('score', 'DESC')
                ->get();

            $enrichedLeads = [];
            foreach ($leads as $lead) {
                $company = Company::findById($lead['company_id']);
                $enrichedLeads[] = array_merge($lead, [
                    'company_name' => $company['name'] ?? 'N/A',
                    'company_niche' => $company['niche'] ?? '',
                    'company_city' => $company['city'] ?? '',
                    'is_final' => PipelineRules::isFinalStage($lead['pipeline_stage_id']),
                ]);
            }

            $pipelineData[] = [
                'id' => $stage['id'],
                'name' => $stage['name'],
                'color' => $stage['color'],
                'position' => $stage['position'],
                'is_default' => $stage['is_default'],
                'leads' => $enrichedLeads,
                'count' => count($enrichedLeads),
                'total_value' => array_sum(array_column($enrichedLeads, 'estimated_value')),
                'valid_transitions' => PipelineRules::getValidTransitions($stage['id']),
            ];
        }

        $this->render('pipeline/index', [
            'title' => 'Pipeline — Prospec CRM',
            'pipeline' => $pipelineData,
            'stages' => $stages,
        ]);
    }

    /**
     * POST /pipeline/move/{leadId} — Mover lead entre stages (AJAX)
     */
    public function move(string $leadId): void
    {
        $this->requireLogin();

        $lead = Lead::findById((int)$leadId);
        if (!$lead) {
            Response::json(['error' => 'Lead não encontrado'], 404)->send();
            return;
        }

        $currentStageId = (int)$lead['pipeline_stage_id'];

        // Verificar se estágio atual é final
        if (PipelineRules::isFinalStage($currentStageId)) {
            Response::json(['error' => "Lead em 'Fechado' é final e não pode ser movido.", 'valid_transitions' => []], 422)->send();
            return;
        }

        $stageId = (int)$this->request->input('pipeline_stage_id', 0);
        $stage = PipelineStage::findById($stageId);
        if (!$stage) {
            Response::json(['error' => 'Stage inválido'], 400)->send();
            return;
        }

        // Verificar se a transição é permitida
        if (!PipelineRules::canTransition($currentStageId, $stageId)) {
            $errorMsg = PipelineRules::getTransitionErrorMessage($currentStageId, $stageId);
            Response::json(['error' => $errorMsg, 'valid_transitions' => PipelineRules::getValidTransitions($currentStageId)], 422)->send();
            return;
        }

        // Exigir motivo
        $reason = trim($this->request->input('reason', ''));
        if (empty($reason)) {
            Response::json(['error' => 'Informe o motivo da mudança de estágio.', 'require_reason' => true], 422)->send();
            return;
        }

        try {
            Lead::updateById((int)$leadId, ['pipeline_stage_id' => $stageId]);

            LeadActivity::create([
                'lead_id' => (int)$leadId,
                'user_id' => Auth::userId(),
                'type' => 'stage_change',
                'description' => "Lead movido de '" . PipelineRules::getStageName($currentStageId) . "' para: {$stage['name']}. Motivo: {$reason}",
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'lead_stage_change',
                'entity_type' => 'lead',
                'entity_id' => (int)$leadId,
                'details' => json_encode(['from' => $currentStageId, 'to' => $stageId, 'reason' => $reason]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Response::json([
                'success' => true,
                'stage' => $stage,
                'lead_id' => (int)$leadId,
            ])->send();
        } catch (\Throwable $e) {
            Logger::error("PipelineController::move error", ["exception" => $e->getMessage()]);
            Response::json(['error' => 'Erro ao mover lead. Tente novamente.'], 500)->send();
        }
    }
}