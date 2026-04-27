<?php
/**
 * CompanyController — CRUD de empresas
 * 
 * Regras:
 * - Anti-duplicação por nome ou CNPJ
 * - Não deletar empresa com leads ativos
 * - Try/catch em operações de escrita
 * - Verificação de limites do plano
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Validator;
use App\Core\PlanLimits;
use App\Core\AuditLog;
use App\Models\Company;
use App\Models\Lead;
use AppCoreLogger;

class CompanyController extends Controller
{
    /**
     * GET /companies — Lista empresas com busca/filtros
     */
    public function index(): void
    {
        $this->requireLogin();

        $page = max(1, (int)($this->request->query('page', 1)));
        $perPage = 20;
        $search = $this->request->query('search', '');
        $niche = $this->request->query('niche', '');
        $city = $this->request->query('city', '');

        $query = Company::query();

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }
        if ($niche) {
            $query->where('niche', $niche);
        }
        if ($city) {
            $query->where('city', $city);
        }

        $query->orderBy('score', 'DESC');
        $pagination = $query->paginate($page, $perPage);

        // Niches e cidades para filtros
        $niches = Company::raw("SELECT DISTINCT niche FROM companies WHERE niche IS NOT NULL AND niche != '' ORDER BY niche")->fetchAll(\PDO::FETCH_COLUMN);
        $cities = Company::raw("SELECT DISTINCT city FROM companies WHERE city IS NOT NULL AND city != '' ORDER BY city LIMIT 50")->fetchAll(\PDO::FETCH_COLUMN);

        $this->render('companies/index', [
            'title' => 'Empresas — Prospec CRM',
            'companies' => $pagination,
            'niches' => $niches,
            'cities' => $cities,
            'filters' => ['search' => $search, 'niche' => $niche, 'city' => $city],
        ]);
    }

    /**
     * GET /companies/{id} — Detalhe da empresa
     */
    public function show(string $id): void
    {
        $this->requireLogin();

        $company = Company::findById((int)$id);
        if (!$company) {
            Flash::error('Empresa não encontrada.');
            Response::redirect('/companies')->send();
            return;
        }

        // Leads vinculados
        $leads = Lead::query()->where('company_id', (int)$id)->orderBy('created_at', 'DESC')->get();

        $this->render('companies/show', [
            'title' => e($company['name']) . ' — Prospec CRM',
            'company' => $company,
            'leads' => $leads,
        ]);
    }

    /**
     * GET /companies/create — Form nova empresa
     */
    public function create(): void
    {
        $this->requireLogin();

        $this->render('companies/create', [
            'title' => 'Nova Empresa — Prospec CRM',
        ]);
    }

    /**
     * POST /companies — Salvar empresa
     */
    public function store(): void
    {
        $this->requireLogin();

        // Verificar limite do plano
        if (!PlanLimits::canCreateCompany(Auth::userId())) {
            $remaining = PlanLimits::getRemaining('companies', Auth::userId());
            Flash::error("Limite do plano atingido. Você já usou todas as empresas disponíveis (restam {$remaining}). Considere fazer upgrade do seu plano.");
            Response::redirect('/companies/create')->send();
            return;
        }

        $data = $this->request->only([
            'name', 'cnpj', 'niche', 'city', 'state', 'phone', 'email',
            'site_url', 'instagram', 'facebook', 'youtube', 'tiktok',
            'maps_rating', 'maps_reviews', 'maps_address', 'maps_phone', 'maps_category',
            'maps_lat', 'maps_lng', 'notes',
            'razao_social', 'situacao', 'capital_social', 'data_inicio',
            'opcao_pelo_mei', 'opcao_pelo_simples', 'cnae_descricao', 'natureza_juridica',
            'porte', 'email_receita', 'telefone_receita',
        ]);

        // Sanitize string inputs
        foreach (['name', 'cnpj', 'niche', 'city', 'state', 'phone', 'email',
                  'site_url', 'instagram', 'facebook', 'youtube', 'tiktok',
                  'maps_address', 'maps_phone', 'maps_category', 'notes',
                  'razao_social', 'situacao', 'cnae_descricao', 'natureza_juridica',
                  'porte', 'email_receita', 'telefone_receita'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = Validator::sanitize($data[$field]);
            }
        }

        // Validate email if provided
        if (!empty($data['email']) && !Validator::email($data['email'])) {
            unset($data['email']);
        }

        $errors = $this->validate($data, [
            'name' => 'required|min:2|max:500',
        ]);

        if (!empty($errors)) {
            Flash::error('Corrija os erros abaixo.');
            $this->render('companies/create', [
                'title' => 'Nova Empresa — Prospec CRM',
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        // Anti-duplicação: se CNPJ foi preenchido, verificar por CNPJ; senão, verificar por nome + cidade
        if (!empty($data['cnpj'])) {
            $existingByCnpj = Company::findWhere('cnpj', $data['cnpj']);
            if ($existingByCnpj) {
                Flash::warning("Já existe uma empresa com o CNPJ '{$data['cnpj']}'. Redirecionando para a edição.");
                Response::redirect('/companies/' . $existingByCnpj['id'] . '/edit')->send();
                return;
            }
        } elseif (!empty($data['name'])) {
            $query = Company::query()->where('name', $data['name']);
            if (!empty($data['city'])) {
                $query->where('city', $data['city']);
            }
            $existingByName = $query->first();
            if ($existingByName) {
                $label = !empty($data['city']) ? "{$data['name']} em {$data['city']}" : $data['name'];
                Flash::warning("Já existe uma empresa cadastrada como '{$label}'. Redirecionando para a edição.");
                Response::redirect('/companies/' . $existingByName['id'] . '/edit')->send();
                return;
            }
        }

        $data['created_by'] = Auth::userId();
        $data['score'] = (int)($data['score'] ?? 0);
        $data['maps_rating'] = !empty($data['maps_rating']) ? floatval($data['maps_rating']) : null;
        $data['maps_reviews'] = !empty($data['maps_reviews']) ? (int)$data['maps_reviews'] : 0;
        $data['capital_social'] = !empty($data['capital_social']) ? floatval($data['capital_social']) : null;

        // Flags (boolean fields — use '0'/'1' for PDO+PostgreSQL compatibility)
        $data['tem_site'] = !empty($data['site_url']) ? '1' : '0';
        $data['tem_instagram'] = !empty($data['instagram']) ? '1' : '0';
        $data['tem_facebook'] = !empty($data['facebook']) ? '1' : '0';
        $data['tem_maps'] = !empty($data['maps_address']) ? '1' : '0';
        $data['tem_ads'] = '0';
        $data['enrichment_status'] = 'manual';

        // Remove empty strings from nullable fields that expect specific types
        $booleanFields = ['opcao_pelo_mei', 'opcao_pelo_simples'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                if ($data[$field] === '' || $data[$field] === null) {
                    $data[$field] = null;
                } else {
                    $data[$field] = $data[$field] ? '1' : '0';
                }
            }
        }
        $numericFields = ['maps_rating', 'maps_reviews', 'maps_lat', 'maps_lng', 'capital_social', 'score'];
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
        $dateFields = ['data_inicio'];
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // Remove all empty string fields that aren't varchar/text
        $varcharFields = ['name', 'cnpj', 'niche', 'city', 'state', 'phone', 'email',
            'site_url', 'instagram', 'facebook', 'youtube', 'tiktok',
            'maps_address', 'maps_phone', 'maps_category', 'notes',
            'razao_social', 'situacao', 'cnae_descricao', 'natureza_juridica',
            'porte', 'email_receita', 'telefone_receita', 'enrichment_status'];
        foreach ($data as $key => $value) {
            if ($value === '' && !in_array($key, $varcharFields)) {
                unset($data[$key]);
            }
        }

        try {
            Company::create($data);

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'company_created',
                'entity_type' => 'company',
                'entity_id' => null,
                'details' => json_encode(['name' => $data['name']]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Flash::success('Empresa criada com sucesso!');
            Response::redirect('/companies')->send();
        } catch (\Throwable $e) {
            Logger::error("CompanyController::store error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao criar empresa. Tente novamente.');
            Response::redirect('/companies/create')->send();
        }
    }

    /**
     * GET /companies/{id}/edit — Editar empresa
     */
    public function edit(string $id): void
    {
        $this->requireLogin();

        $company = Company::findById((int)$id);
        if (!$company) {
            Flash::error('Empresa não encontrada.');
            Response::redirect('/companies')->send();
            return;
        }

        $this->render('companies/edit', [
            'title' => 'Editar Empresa — Prospec CRM',
            'company' => $company,
        ]);
    }

    /**
     * POST /companies/{id} — Atualizar empresa
     */
    public function update(string $id): void
    {
        $this->requireLogin();

        $company = Company::findById((int)$id);
        if (!$company) {
            Flash::error('Empresa não encontrada.');
            Response::redirect('/companies')->send();
            return;
        }

        $data = $this->request->only([
            'name', 'cnpj', 'niche', 'city', 'state', 'phone', 'email',
            'site_url', 'instagram', 'facebook', 'youtube', 'tiktok',
            'maps_rating', 'maps_reviews', 'maps_address', 'maps_phone', 'maps_category',
            'maps_lat', 'maps_lng', 'notes',
            'razao_social', 'situacao', 'capital_social', 'data_inicio',
            'opcao_pelo_mei', 'opcao_pelo_simples', 'cnae_descricao', 'natureza_juridica',
            'porte', 'email_receita', 'telefone_receita', 'score',
        ]);

        if (empty($data['name'])) {
            Flash::error('Nome da empresa é obrigatório.');
            $this->render('companies/edit', [
                'title' => 'Editar Empresa — Prospec CRM',
                'company' => $company,
                'old' => $data,
            ]);
            return;
        }

        // Anti-duplicação ao atualizar: verificar se outra empresa já tem esse nome
        $existingByName = Company::query()->where('name', $data['name'])->first();
        if ($existingByName && (int)$existingByName['id'] !== (int)$id) {
            Flash::warning("Já existe outra empresa com o nome '{$data['name']}'.");
            Response::redirect('/companies/' . $id . '/edit')->send();
            return;
        }

        // Anti-duplicação ao atualizar: verificar se outra empresa já tem esse CNPJ
        if (!empty($data['cnpj'])) {
            $existingByCnpj = Company::findWhere('cnpj', $data['cnpj']);
            if ($existingByCnpj && (int)$existingByCnpj['id'] !== (int)$id) {
                Flash::warning("Já existe outra empresa com o CNPJ '{$data['cnpj']}'.");
                Response::redirect('/companies/' . $id . '/edit')->send();
                return;
            }
        }

        $data['score'] = (int)($data['score'] ?? $company['score'] ?? 0);
        $data['tem_site'] = !empty($data['site_url']) ? '1' : '0';
        $data['tem_instagram'] = !empty($data['instagram']) ? '1' : '0';
        $data['tem_facebook'] = !empty($data['facebook']) ? '1' : '0';
        $data['tem_maps'] = !empty($data['maps_address']) ? '1' : '0';

        // Clean empty strings for nullable/non-string columns
        $nullableBool = ['opcao_pelo_mei', 'opcao_pelo_simples'];
        foreach ($nullableBool as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
        $nullableNumeric = ['maps_lat', 'maps_lng'];
        foreach ($nullableNumeric as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        try {
            Company::updateById((int)$id, $data);

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'company_updated',
                'entity_type' => 'company',
                'entity_id' => (int)$id,
                'details' => json_encode(['name' => $data['name']]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Flash::success('Empresa atualizada com sucesso!');
            Response::redirect('/companies/' . $id)->send();
        } catch (\Throwable $e) {
            Logger::error("CompanyController::update error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao atualizar empresa. Tente novamente.');
            Response::redirect('/companies/' . $id . '/edit')->send();
        }
    }

    /**
     * POST /companies/{id}/archive — Arquivar empresa (marca como inativa)
     */
    public function archive(string $id): void
    {
        $this->requireLogin();

        $company = Company::findById((int)$id);
        if (!$company) {
            Flash::error('Empresa não encontrada.');
            Response::redirect('/companies')->send();
            return;
        }

        try {
            Company::updateById((int)$id, ['archived' => '1']);

            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'company_archived',
                'entity_type' => 'company',
                'entity_id' => (int)$id,
                'details' => json_encode(['name' => $company['name']]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Flash::success('Empresa arquivada com sucesso.');
            Response::redirect('/companies')->send();
        } catch (\Throwable $e) {
            Logger::error("CompanyController::archive error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao arquivar empresa. Tente novamente.');
            Response::redirect('/companies/' . $id)->send();
        }
    }

    /**
     * POST /companies/{id}/delete — Deletar empresa
     */
    public function delete(string $id): void
    {
        $this->requireLogin();

        $company = Company::findById((int)$id);
        if (!$company) {
            Flash::error('Empresa não encontrada.');
            Response::redirect('/companies')->send();
            return;
        }

        // Verificar se a empresa tem leads ativos
        $activeLeadCount = Lead::count('company_id = :cid AND status = :status', ['cid' => (int)$id, 'status' => 'active']);
        if ($activeLeadCount > 0) {
            Flash::error("Empresa tem {$activeLeadCount} lead(s) ativo(s). Mova os leads antes de deletar, ou use a opção 'Arquivar empresa'.");
            Response::redirect('/companies/' . $id)->send();
            return;
        }

        try {
            // Audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'company_deleted',
                'entity_type' => 'company',
                'entity_id' => (int)$id,
                'details' => json_encode(['name' => $company['name']]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Company::deleteById((int)$id);
            Flash::success('Empresa deletada com sucesso.');
            Response::redirect('/companies')->send();
        } catch (\Throwable $e) {
            Logger::error("CompanyController::delete error", ["exception" => $e->getMessage()]);
            Flash::error('Erro ao deletar empresa. Tente novamente.');
            Response::redirect('/companies/' . $id)->send();
        }
    }
}