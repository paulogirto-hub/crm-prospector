<?php
/**
 * CompanyService — Lógica de negócio de empresas
 * MELH-001: Extraído de CompanyController
 */

namespace App\Services;

use App\Core\Model;
use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Validator;
use App\Models\Company;
use AppCoreLogger;

class CompanyService
{
    /**
     * Lista empresas com filtros
     */
    public static function list(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = Company::query()
            ->where('deleted_at IS NULL');

        if (!empty($filters['search'])) {
            $search = Validator::sanitize($filters['search']);
            $query->where("name ILIKE '%{$search}%' OR cnpj ILIKE '%{$search}%'");
        }

        if (!empty($filters['city'])) {
            $query->where('city', Validator::sanitize($filters['city']));
        }

        $offset = ($page - 1) * $perPage;
        $companies = $query
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->get();

        $total = Company::count();

        return [
            'companies' => $companies,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Cria empresa com validação
     */
    public static function create(array $data, string $ip = '0.0.0.0'): array
    {
        $data['name'] = Validator::sanitize($data['name'] ?? '');
        $data['cnpj'] = Validator::sanitize($data['cnpj'] ?? '');

        $errors = Validator::make($data, [
            'name' => 'required|min:2|max:255',
        ]);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Verificar CNPJ duplicado
        if (!empty($data['cnpj'])) {
            $existing = Company::findByCnpj($data['cnpj']);
            if ($existing) {
                return ['success' => false, 'errors' => ['cnpj' => 'CNPJ já cadastrado.']];
            }
        }

        try {
            $data['created_by'] = Auth::userId();
            $companyId = Company::create($data);

            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'create',
                'entity_type' => 'company',
                'entity_id' => $companyId,
                'details' => json_encode(['name' => $data['name'], 'cnpj' => $data['cnpj'] ?? '']),
                'ip' => $ip,
            ]);

            return ['success' => true, 'company_id' => $companyId];
        } catch (\Throwable $e) {
            Logger::error("CompanyService::create error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'errors' => ['general' => 'Erro ao criar empresa.']];
        }
    }

    /**
     * Enriquece empresa com dados de APIs externas
     */
    public static function enrich(int $companyId, string $ip = '0.0.0.0'): array
    {
        $company = Company::findById($companyId);
        if (!$company) {
            return ['success' => false, 'error' => 'Empresa não encontrada.'];
        }

        try {
            // Delegar enriquecimento ao ProspecService
            $result = \App\Core\ProspecService::enrichCompany($company);

            if ($result['success'] ?? false) {
                Company::updateById($companyId, [
                    'enrichment_status' => 'enriched',
                    'enriched_at' => date('c'),
                ]);

                AuditLog::create([
                    'user_id' => Auth::userId(),
                    'action' => 'enrich',
                    'entity_type' => 'company',
                    'entity_id' => $companyId,
                    'ip' => $ip,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Logger::error("CompanyService::enrich error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao enriquecer empresa.'];
        }
    }

    /**
     * Soft delete de empresa
     */
    public static function delete(int $companyId, string $ip = '0.0.0.0'): array
    {
        $company = Company::findById($companyId);
        if (!$company) {
            return ['success' => false, 'error' => 'Empresa não encontrada.'];
        }

        try {
            Company::updateById($companyId, ['deleted_at' => date('c')]);

            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'delete',
                'entity_type' => 'company',
                'entity_id' => $companyId,
                'details' => json_encode(['name' => $company['name'] ?? '']),
                'ip' => $ip,
            ]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Logger::error("CompanyService::delete error", ["exception" => $e->getMessage()]);
            return ['success' => false, 'error' => 'Erro ao excluir empresa.'];
        }
    }
}