<?php
/**
 * PipelineService — Lógica de negócio do pipeline/kanban
 * MELH-001: Extraído de PipelineController
 */

namespace App\Services;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\PipelineRules;
use App\Models\Lead;
use App\Models\LeadActivity;
use AppCoreLogger;

class PipelineService
{
    /**
     * Move lead entre estágios com validação
     */
    public static function move(int $leadId, int $newStageId, string $ip = '0.0.0.0'): array
    {
        $lead = Lead::findById($leadId);
        if (!$lead) {
            return ['success' => false, 'error' => 'Lead não encontrado.'];
        }

        $oldStageId = (int)$lead['pipeline_stage_id'];

        // Validar transição
        if (!PipelineRules::canTransition($oldStageId, $newStageId)) {
            return ['success' => false, 'error' => 'Transição de estágio inválida.'];
        }

        try {
            Lead::updateById($leadId, ['pipeline_stage_id' => $newStageId]);

            // Registrar atividade
            LeadActivity::create([
                'lead_id' => $leadId,
                'user_id' => Auth::userId(),
                'type' => 'stage_change',
                'description' => "Estágio alterado de {$oldStageId} para {$newStageId}",
            ]);

            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'pipeline_move',
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'details' => json_encode(['from' => $oldStageId, 'to' => $newStageId]),
                'ip' => $ip,
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Logger::error("PipelineService::move error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao mover lead no pipeline.'];
        }
    }

    /**
     * Valida se uma transição de estágio é permitida
     */
    public static function canMove(int $fromStageId, int $toStageId): bool
    {
        return PipelineRules::canTransition($fromStageId, $toStageId);
    }

    /**
     * Retorna leads agrupados por estágio para o kanban
     */
    public static function getKanbanData(): array
    {
        $pdo = \App\Core\Model::getPdo();

        // Buscar estágios
        $stmt = $pdo->query("SELECT * FROM pipeline_stages ORDER BY position ASC");
        $stages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Buscar leads por estágio
        foreach ($stages as &$stage) {
            $stmt = $pdo->prepare(
                "SELECT l.*, c.name as company_name 
                 FROM leads l 
                 LEFT JOIN companies c ON l.company_id = c.id 
                 WHERE l.pipeline_stage_id = :stage_id 
                   AND l.deleted_at IS NULL 
                 ORDER BY l.created_at DESC"
            );
            $stmt->execute(['stage_id' => $stage['id']]);
            $stage['leads'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        unset($stage);

        return $stages;
    }
}