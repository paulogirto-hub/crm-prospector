<?php
/**
 * LeadService — Lógica de negócio de leads
 * MELH-001: Extraído de LeadController
 */

namespace App\Services;

use App\Core\Model;
use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Validator;
use App\Core\Flash;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Company;
use AppCoreLogger;

class LeadService
{
    /**
     * Lista leads com filtros e paginação
     */
    public static function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = Lead::query()
            ->where('deleted_at IS NULL');

        // Filtros
        if (!empty($filters['stage'])) {
            $query->where('pipeline_stage_id', (int)$filters['stage']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', (int)$filters['assigned_to']);
        }
        if (!empty($filters['search'])) {
            $search = Validator::sanitize($filters['search']);
            $query->where("contact_name ILIKE '%{$search}%' OR email ILIKE '%{$search}%'");
        }

        $offset = ($page - 1) * $perPage;
        $leads = $query
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        $total = Lead::count();

        return [
            'leads' => $leads,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Cria um novo lead com validação e audit
     */
    public static function create(array $data, string $ip = '0.0.0.0'): array
    {
        // Sanitize
        $data['contact_name'] = Validator::sanitize($data['contact_name'] ?? '');
        $data['email'] = Validator::sanitize($data['email'] ?? '');
        $data['phone'] = Validator::sanitize($data['phone'] ?? '');
        $data['source'] = Validator::sanitize($data['source'] ?? 'manual');

        // Validar
        $errors = Validator::make($data, [
            'contact_name' => 'required|min:2|max:255',
            'email' => 'email',
        ]);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $data['assigned_to'] = $data['assigned_to'] ?? Auth::userId();
            $data['pipeline_stage_id'] = $data['pipeline_stage_id'] ?? 1;

            $leadId = Lead::create($data);

            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'create',
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'details' => json_encode(['name' => $data['contact_name'], 'email' => $data['email']]),
                'ip' => $ip,
            ]);

            return ['success' => true, 'lead_id' => $leadId];
        } catch (\Throwable $e) {
            Logger::error("LeadService::create error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'errors' => ['general' => 'Erro ao criar lead.']];
        }
    }

    /**
     * Move lead para outro estágio com validação de transição
     */
    public static function moveStage(int $leadId, int $newStageId, string $ip = '0.0.0.0'): array
    {
        $lead = Lead::findById($leadId);
        if (!$lead) {
            return ['success' => false, 'error' => 'Lead não encontrado.'];
        }

        $oldStageId = (int)$lead['pipeline_stage_id'];

        // Validar transição
        if (!\App\Core\PipelineRules::canTransition($oldStageId, $newStageId)) {
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
                'action' => 'update',
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'details' => json_encode(['from_stage' => $oldStageId, 'to_stage' => $newStageId]),
                'ip' => $ip,
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Logger::error("LeadService::moveStage error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao mover lead.'];
        }
    }

    /**
     * Adiciona atividade a um lead
     */
    public static function addActivity(int $leadId, string $type, string $description, string $ip = '0.0.0.0'): array
    {
        $lead = Lead::findById($leadId);
        if (!$lead) {
            return ['success' => false, 'error' => 'Lead não encontrado.'];
        }

        try {
            LeadActivity::create([
                'lead_id' => $leadId,
                'user_id' => Auth::userId(),
                'type' => $type,
                'description' => Validator::sanitize($description),
            ]);

            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'activity',
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'details' => json_encode(['type' => $type]),
                'ip' => $ip,
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Logger::error("LeadService::addActivity error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao adicionar atividade.'];
        }
    }

    /**
     * Soft delete de um lead
     */
    public static function delete(int $leadId, string $ip = '0.0.0.0'): array
    {
        $lead = Lead::findById($leadId);
        if (!$lead) {
            return ['success' => false, 'error' => 'Lead não encontrado.'];
        }

        try {
            Lead::updateById($leadId, ['deleted_at' => date('c')]);

            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'delete',
                'entity_type' => 'lead',
                'entity_id' => $leadId,
                'details' => json_encode(['name' => $lead['contact_name'] ?? '']),
                'ip' => $ip,
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Logger::error("LeadService::delete error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao excluir lead.'];
        }
    }

    /**
     * Importa leads de dados do Prospector
     */
    public static function importFromProspector(array $leadsData, int $userId, string $ip = '0.0.0.0'): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($leadsData as $leadData) {
            try {
                // Verificar se empresa já existe
                $company = null;
                if (!empty($leadData['cnpj'])) {
                    $company = Company::findByCnpj($leadData['cnpj']);
                }

                if (!$company && !empty($leadData['title'])) {
                    $company = Company::findByName($leadData['title']);
                }

                // Criar empresa se não existe
                if (!$company) {
                    $companyId = Company::createFromProspector($leadData);
                    $company = ['id' => $companyId];
                }

                // Criar lead
                $leadId = Lead::create([
                    'company_id' => $company['id'],
                    'assigned_to' => $userId,
                    'pipeline_stage_id' => 1,
                    'source' => 'prospec',
                    'contact_name' => $leadData['title'] ?? $leadData['maps_title'] ?? 'Sem nome',
                    'email' => $leadData['site_emails'][0] ?? $leadData['email'] ?? null,
                    'phone' => $leadData['site_phones'][0] ?? $leadData['maps_phone'] ?? null,
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = $e->getMessage();
            }
        }

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'import',
            'entity_type' => 'lead',
            'entity_id' => null,
            'details' => json_encode(['imported' => $imported, 'skipped' => $skipped, 'errors' => count($errors)]),
            'ip' => $ip,
        ]);

        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}