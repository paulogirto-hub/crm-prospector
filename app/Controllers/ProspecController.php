<?php
/**
 * ProspecController — Dashboard de prospecção integrado com Prospector API
 * 
 * Regras:
 * - Transações DB no import
 * - Try/catch em todas as operações de escrita
 * - Rate limiting (3 imports/hora, 20 searches/hora)
 * - Fallback para erros da API
 * - Verificação de limites do plano
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Response;
use App\Core\ProspecService;
use App\Core\PlanLimits;
use App\Core\RateLimit;
use App\Core\AuditLog;
use App\Models\SearchSession;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadActivity;
use AppCoreLogger;

class ProspecController extends Controller
{
    /**
     * GET /prospec — Dashboard com form de busca + histórico recente
     */
    public function index(): void
    {
        $this->requireLogin();

        // Busca histórico do Prospector API
        $history = ProspecService::getHistory();
        $recentSearches = $history['success'] ?? false ? array_slice($history, 0, 8) : [];

        // Stats do CRM
        $totalCompanies = Company::count();
        $activeLeads    = Lead::countActive();

        $this->render('prospec/index', [
            'title'         => 'Prospecção — Prospec CRM',
            'recentSearches'=> $recentSearches,
            'totalCompanies'=> $totalCompanies,
            'activeLeads'   => $activeLeads,
        ]);
    }

    /**
     * POST /prospec/search — AJAX: cria nova busca no Prospector
     */
    public function search(): void
    {
        $this->requireLogin();

        // Rate limit: 20 buscas/hora por usuário
        $rlKey = 'prospec_search:' . Auth::userId();
        if (!RateLimit::check($rlKey, 20, 3600)) {
            Response::json(['success' => false, 'error' => 'Limite atingido: máximo de 20 buscas por hora.'], 429)->send();
            return;
        }

        // Verificar limite do plano
        if (!PlanLimits::canSearch(Auth::userId())) {
            $remaining = PlanLimits::getRemaining('searches', Auth::userId());
            Response::json(['success' => false, 'error' => "Limite do plano atingido (restam {$remaining} buscas este mês). Considere fazer upgrade."], 403)->send();
            return;
        }

        $niche = trim($this->request->input('niche', ''));
        $city  = trim($this->request->input('city', ''));
        $state = trim($this->request->input('state', ''));

        if (!$niche || !$city) {
            Response::json(['success' => false, 'error' => 'Nicho e cidade são obrigatórios'], 422)->send();
            return;
        }

        try {
            $result = ProspecService::search($niche, $city, $state ?: 'PR');

            // Tratar erros da API com mensagens amigáveis
            if (!($result['success'] ?? false)) {
                $httpCode = $result['http_code'] ?? 0;
                $errorMsg = $this->getApiErrorMessage($result, $httpCode);
                
                RateLimit::hit($rlKey, 3600);

                Response::json([
                    'success' => false,
                    'error'   => $errorMsg,
                ], $httpCode >= 400 ? $httpCode : 502)->send();
                return;
            }

            RateLimit::hit($rlKey, 3600);

            // Registrar busca no audit log
            AuditLog::create([
                'user_id' => Auth::userId(),
                'action' => 'prospec_search',
                'entity_type' => 'search',
                'entity_id' => null,
                'details' => json_encode(['niche' => $niche, 'city' => $city, 'state' => $state]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            // Retorna o search_id pra redirecionar via JS
            $searchId = $result['search_id'] ?? null;

            Response::json([
                'success'   => true,
                'search_id' => $searchId,
            ])->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::search error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao processar busca. Tente novamente.'], 500)->send();
        }
    }

    /**
     * GET /prospec/session/{id} — Detalhe da busca
     */
    public function session(string $id): void
    {
        $this->requireLogin();

        $data = ProspecService::getStatus($id);

        if (!($data['success'] ?? false)) {
            $httpCode = $data['http_code'] ?? 0;
            $errorMsg = $this->getApiErrorMessage($data, $httpCode);
            Flash::error($errorMsg);
            Response::redirect('/prospec')->send();
            return;
        }

        $summary = $data['summary'] ?? [];
        $leads   = $data['leads'] ?? [];
        $status  = $summary['status'] ?? $data['status'] ?? 'unknown';

        // Mapeia status do Prospector pra status visuais
        $pipelineSteps = [
            'discovering'     => 1,
            'discovery'       => 1,
            'enriching'       => 2,
            'enriched'        => 2,
            'scoring'         => 3,
            'scored'          => 3,
            'analyzing_leads' => 4,
            'market_analyzed' => 4,
            'analyzed'        => 5,
            'completed'       => 5,
        ];
        $currentStep = $pipelineSteps[$status] ?? 0;

        // Adiciona lead_status em cada lead para o frontend
        foreach ($leads as &$lead) {
            $lead['lead_status'] = !empty($lead['ia_analise']) ? 'analyzed'
                : (($lead['score'] ?? 0) > 0 ? 'scored'
                : (!empty($lead['site_url']) || !empty($lead['maps_phone']) ? 'enriched'
                : 'discovered'));
        }
        unset($lead);

        // Verificar se pode usar IA
        $canUseAI = PlanLimits::canUseAI(Auth::userId());

        $this->render('prospec/session', [
            'title'       => 'Busca ' . $id . ' — Prospec CRM',
            'searchId'    => $id,
            'summary'     => $summary,
            'leads'       => $leads,
            'status'      => $status,
            'currentStep' => $currentStep,
            'canUseAI'    => $canUseAI,
        ]);
    }

    /**
     * GET /prospec/session/{id}/status — AJAX: retorna JSON com status atualizado da sessão
     * Usado pelo polling do frontend para atualizar pipeline, leads e contadores
     */
    public function sessionStatus(string $id): void
    {
        $this->requireLogin();

        $data = ProspecService::getStatus($id);

        if (!($data['success'] ?? false)) {
            Response::json([
                'success' => false,
                'error'   => $data['error'] ?? 'Erro ao buscar status',
            ])->send();
            return;
        }

        $summary = $data['summary'] ?? [];
        $leads   = $data['leads'] ?? [];
        $status  = $summary['status'] ?? $data['status'] ?? 'unknown';

        // Mapear leads para formato frontend-friendly
        $mappedLeads = array_map(function ($lead) use ($summary) {
            return [
                'id'              => $lead['id'] ?? '',
                'position'        => $lead['position'] ?? 0,
                'title'           => $lead['title'] ?? $lead['maps_title'] ?? '—',
                'maps_title'      => $lead['maps_title'] ?? '',
                'maps_address'    => $lead['maps_address'] ?? '',
                'maps_phone'     => $lead['maps_phone'] ?? '',
                'maps_rating'    => $lead['maps_rating'] ?? null,
                'maps_reviews'   => $lead['maps_reviews'] ?? null,
                'maps_category'  => $lead['maps_category'] ?? $summary['niche'] ?? '',
                'site_url'       => $lead['site_url'] ?? $lead['link'] ?? '',
                'instagram_url'  => $lead['instagram_url'] ?? '',
                'facebook_url'   => $lead['facebook_url'] ?? '',
                'score'          => $lead['score'] ?? 0,
                'tem_site'       => (bool)($lead['tem_site'] ?? false),
                'tem_instagram'  => (bool)($lead['tem_instagram'] ?? false),
                'tem_facebook'   => (bool)($lead['tem_facebook'] ?? false),
                'tem_maps'       => (bool)($lead['tem_maps'] ?? false),
                'tem_ads'        => (bool)($lead['tem_ads'] ?? false),
                'ia_analise'     => $lead['ia_analise'] ?? '',
                'snippet'        => $lead['snippet'] ?? '',
                // ─── Campos CNPJ (enriquecimento) ───
                'cnpj'            => $lead['cnpj'] ?? null,
                'razao_social'    => $lead['razao_social'] ?? null,
                'situacao'        => $lead['situacao'] ?? null,
                'capital_social'  => $lead['capital_social'] ?? null,
                'opcao_pelo_mei'  => (bool)($lead['opcao_pelo_mei'] ?? false),
                'opcao_pelo_simples' => (bool)($lead['opcao_pelo_simples'] ?? false),
                'porte'           => $lead['porte'] ?? null,
                'email_receita'   => $lead['email_receita'] ?? null,
                'telefone_receita'=> $lead['telefone_receita'] ?? null,
                'cnae_descricao'  => $lead['cnae_descricao'] ?? null,
                // ─── Campos Site scraping ───
                'site_instagram'  => $lead['site_instagram'] ?? null,
                'site_facebook'   => $lead['site_facebook'] ?? null,
                'site_youtube'    => $lead['site_youtube'] ?? null,
                'site_tiktok'     => $lead['site_tiktok'] ?? null,
                'site_emails'     => $lead['site_emails'] ?? [],
                'site_phones'     => $lead['site_phones'] ?? [],
                // Lead status badge logic
                'lead_status'    => !empty($lead['ia_analise']) ? 'analyzed' : (($lead['score'] ?? 0) > 0 ? 'scored' : (!empty($lead['site_url']) || !empty($lead['maps_phone']) ? 'enriched' : 'discovered')),
            ];
        }, $leads);

        // Pipeline step
        $pipelineSteps = [
            'discovering'     => 1,
            'discovery'       => 1,
            'enriching'       => 2,
            'enriched'        => 2,
            'scoring'         => 3,
            'scored'          => 3,
            'analyzing_leads' => 4,
            'market_analyzed' => 4,
            'analyzed'        => 5,
            'completed'       => 5,
        ];
        $currentStep = $pipelineSteps[$status] ?? 0;

        // Contadores rápidos
        $scoreHigh   = count(array_filter($mappedLeads, fn($l) => $l['score'] >= 70));
        $scoreMedium = count(array_filter($mappedLeads, fn($l) => $l['score'] >= 40 && $l['score'] < 70));
        $scoreLow    = count(array_filter($mappedLeads, fn($l) => $l['score'] > 0 && $l['score'] < 40));

        Response::json([
            'success'      => true,
            'status'       => $status,
            'currentStep'  => $currentStep,
            'summary'      => $summary,
            'leads'        => $mappedLeads,
            'counts'       => [
                'total'        => count($mappedLeads),
                'score_high'   => $scoreHigh,
                'score_medium' => $scoreMedium,
                'score_low'    => $scoreLow,
                'enriched'     => count(array_filter($mappedLeads, fn($l) => in_array($l['lead_status'], ['enriched', 'scored', 'analyzed']))),
                'scored'       => count(array_filter($mappedLeads, fn($l) => in_array($l['lead_status'], ['scored', 'analyzed']))),
                'analyzed'     => count(array_filter($mappedLeads, fn($l) => $l['lead_status'] === 'analyzed')),
            ],
        ])->send();
    }

    /**
     * POST /prospec/enrich/{id} — AJAX: dispara enriquecimento
     */
    public function enrich(string $id): void
    {
        $this->requireLogin();
        try {
            $result = ProspecService::enrich($id);
            Response::json($result)->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::enrich error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao enriquecer. Tente novamente.'])->send();
        }
    }

    /**
     * POST /prospec/score/{id} — AJAX: dispara scoring
     */
    public function score(string $id): void
    {
        $this->requireLogin();
        try {
            $result = ProspecService::score($id);
            Response::json($result)->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::score error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao pontuar. Tente novamente.'])->send();
        }
    }

    /**
     * POST /prospec/analyze/{id} — AJAX: dispara análise IA em lote
     */
    public function analyze(string $id): void
    {
        $this->requireLogin();

        // Verificar se pode usar IA
        if (!PlanLimits::canUseAI(Auth::userId())) {
            Response::json(['success' => false, 'error' => 'Análise IA disponível apenas no plano Pro ou superior. Considere fazer upgrade.'])->send();
            return;
        }

        try {
            $result = ProspecService::analyze($id);
            Response::json($result)->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::analyze error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao analisar. Tente novamente.'])->send();
        }
    }

    /**
     * POST /prospec/session/{id}/analyze-lead — AJAX: analisa UM lead específico com IA
     * Usa o endpoint /search/{id}/analyze-leads com count=1 para análise individual
     */
    public function analyzeLead(string $id): void
    {
        $this->requireLogin();

        // Verificar se pode usar IA
        if (!PlanLimits::canUseAI(Auth::userId())) {
            Response::json(['success' => false, 'error' => 'Análise IA disponível apenas no plano Pro ou superior. Considere fazer upgrade.'])->send();
            return;
        }

        // Rate limit: 10 análises IA/minuto por usuário
        $rlKey = 'prospec_analyze_lead:' . Auth::userId();
        if (!RateLimit::check($rlKey, 10, 60)) {
            Response::json(['success' => false, 'error' => 'Limite atingido: máximo de 10 análises IA por minuto.'])->send();
            return;
        }

        try {
            RateLimit::hit($rlKey, 60);

            // Análise individual (count=1)
            $result = ProspecService::analyze($id, 1);

            if ($result['success'] ?? false) {
                // Auditoria
                AuditLog::create([
                    'user_id' => Auth::userId(),
                    'action' => 'prospec_analyze_lead',
                    'entity_type' => 'search',
                    'entity_id' => $id,
                    'details' => json_encode(['search_id' => $id, 'count' => 1]),
                    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]);
            }

            Response::json($result)->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::analyzeLead error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao analisar lead. Tente novamente.'])->send();
        }
    }

    /**
     * POST /prospec/import/{id} — Importa leads do Prospector pro CRM
     */
    public function import(string $id): void
    {
        $this->requireLogin();
        $userId = Auth::userId();

        // Rate limit: 3 imports/hora por usuário
        $rlKey = 'prospec_import:' . $userId;
        if (!RateLimit::check($rlKey, 3, 3600)) {
            Response::json(['success' => false, 'error' => 'Limite atingido: máximo de 3 importações por hora.'])->send();
            return;
        }

        // Verificar limite do plano para leads
        if (!PlanLimits::canCreateLead($userId)) {
            $remaining = PlanLimits::getRemaining('leads', $userId);
            Response::json(['success' => false, 'error' => "Limite do plano atingido (restam {$remaining} leads). Considere fazer upgrade."])->send();
            return;
        }

        // Busca dados completos no Prospector
        $data = ProspecService::getStatus($id);

        if (!($data['success'] ?? false)) {
            $httpCode = $data['http_code'] ?? 0;
            $errorMsg = $this->getApiErrorMessage($data, $httpCode);
            Response::json(['success' => false, 'error' => $errorMsg])->send();
            return;
        }

        $leads   = $data['leads'] ?? [];
        $summary = $data['summary'] ?? [];
        $niche   = $summary['niche'] ?? '';
        $city    = $summary['city'] ?? '';
        $state   = $summary['state'] ?? '';

        $imported = 0;
        $skipped  = 0;
        $errors   = 0;

        $pdo = \App\Core\Model::getPdo();

        // Usar transação para garantir integridade
        $pdo->beginTransaction();

        try {
            RateLimit::hit($rlKey, 3600);

            foreach ($leads as $leadData) {
                try {
                    $companyName = $leadData['title'] ?? $leadData['maps_title'] ?? '';
                    if (!$companyName) {
                        $skipped++;
                        continue;
                    }

                    // Verificar se pode criar mais leads
                    if (!PlanLimits::canCreateLead($userId) && $imported > 0) {
                        $skipped += (count($leads) - $imported - $skipped - $errors);
                        break;
                    }

                    // Verifica se empresa já existe por nome ou site_url
                    $existing = null;
                    $siteUrl  = $leadData['site_url'] ?? $leadData['link'] ?? '';

                    if ($siteUrl) {
                        $existing = Company::findWhere('site_url', $siteUrl);
                    }
                    if (!$existing && $companyName) {
                        $existing = Company::query()
                            ->where('name', $companyName)
                            ->first();
                    }

                    // Converte booleans do Prospector para strings aceitas pelo PostgreSQL
                    $toBool = fn($val) => $val ? 'true' : 'false';

                    $companyFields = [
                        'name'              => $companyName,
                        'niche'             => $niche ?: ($leadData['maps_category'] ?? ''),
                        'city'              => $city ?: '',
                        'state'             => $state,
                        'site_url'          => $siteUrl,
                        'instagram'         => $leadData['instagram_url'] ?? '',
                        'facebook'          => $leadData['facebook_url'] ?? '',
                        // ─── Campos CNPJ (enriquecimento) ───
                        'cnpj'              => $leadData['cnpj'] ?? null,
                        'razao_social'      => $leadData['razao_social'] ?? null,
                        'situacao_cnpj'     => $leadData['situacao'] ?? null,
                        'capital_social'    => !empty($leadData['capital_social']) ? $leadData['capital_social'] : null,
                        'data_inicio'       => !empty($leadData['data_inicio']) ? $leadData['data_inicio'] : null,
                        'opcao_pelo_mei'    => $toBool($leadData['opcao_pelo_mei'] ?? false),
                        'opcao_pelo_simples'=> $toBool($leadData['opcao_pelo_simples'] ?? false),
                        'cnae_descricao'    => $leadData['cnae_descricao'] ?? null,
                        'natureza_juridica' => $leadData['natureza_juridica'] ?? null,
                        'porte'             => $leadData['porte'] ?? null,
                        'email_receita'     => $leadData['email_receita'] ?? null,
                        'telefone_receita'  => $leadData['telefone_receita'] ?? null,
                        'socios'            => !empty($leadData['socios']) ? json_encode($leadData['socios'], JSON_UNESCAPED_UNICODE) : null,
                        // ─── Campos Site scraping (enriquecimento) ───
                        'site_emails'       => !empty($leadData['site_emails']) ? json_encode($leadData['site_emails'], JSON_UNESCAPED_UNICODE) : null,
                        'site_phones'       => !empty($leadData['site_phones']) ? json_encode($leadData['site_phones'], JSON_UNESCAPED_UNICODE) : null,
                        'site_instagram'    => $leadData['site_instagram'] ?? null,
                        'site_facebook'    => $leadData['site_facebook'] ?? null,
                        'site_youtube'     => $leadData['site_youtube'] ?? null,
                        'site_tiktok'      => $leadData['site_tiktok'] ?? null,
                        'cnpj_source'       => $leadData['cnpj_source'] ?? null,
                        'snippet'           => $leadData['snippet'] ?? null,
                        // ─── Campos Maps ───
                        'maps_rating'       => $leadData['maps_rating'] ?? null,
                        'maps_reviews'      => is_null($leadData['maps_reviews'] ?? null) ? 0 : (int)$leadData['maps_reviews'],
                        'maps_address'      => $leadData['maps_address'] ?? '',
                        'maps_phone'        => $leadData['maps_phone'] ?? '',
                        'maps_category'     => $leadData['maps_category'] ?? '',
                        'maps_lat'          => $leadData['maps_lat'] ?? null,
                        'maps_lng'          => $leadData['maps_lng'] ?? null,
                        // ─── Flags e Score ───
                        'score'             => $leadData['score'] ?? 0,
                        'tem_site'          => $toBool($leadData['tem_site'] ?? false),
                        'tem_instagram'     => $toBool($leadData['tem_instagram'] ?? false),
                        'tem_facebook'      => $toBool($leadData['tem_facebook'] ?? false),
                        'tem_maps'          => $toBool($leadData['tem_maps'] ?? false),
                        'tem_ads'           => $toBool($leadData['tem_ads'] ?? false),
                        'enrichment_status' => !empty($leadData['ia_analise']) ? 'analyzed' : 'pending',
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ];

                    if ($existing) {
                        // Atualiza empresa existente — só sobrescreve campos não-vazios
                        $updateFields = array_filter($companyFields, function($v, $k) {
                            // Sempre atualizar: score, flags, enrichment_status, updated_at
                            if (in_array($k, ['score', 'tem_site', 'tem_instagram', 'tem_facebook', 'tem_maps', 'tem_ads', 'enrichment_status', 'updated_at'])) return true;
                            // Para demais campos, só atualizar se tiver valor
                            return $v !== null && $v !== '' && $v !== '[]';
                        }, ARRAY_FILTER_USE_BOTH);
                        Company::updateById($existing['id'], $updateFields);
                        $companyId = $existing['id'];

                        // Verifica se já tem lead ativo pra essa empresa + vendedor
                        $existingLead = Lead::query()
                            ->where('company_id', $companyId)
                            ->where('assigned_to', $userId)
                            ->where('status', 'active')
                            ->first();

                        if ($existingLead) {
                            Lead::updateById($existingLead['id'], [
                                'score'             => $leadData['score'] ?? $existingLead['score'],
                                'ia_analise'        => $leadData['ia_analise'] ?? $existingLead['ia_analise'],
                                'ia_market_analysis'=> $summary['ia_market_analysis'] ?? $existingLead['ia_market_analysis'],
                                'source'            => 'prospecção',
                                'updated_at'        => date('Y-m-d H:i:s'),
                            ]);
                            $skipped++;
                            continue;
                        }
                    } else {
                        // Cria nova empresa
                        $companyFields['created_by'] = $userId;
                        $companyFields['created_at'] = date('Y-m-d H:i:s');
                        $createFields = array_filter($companyFields, fn($v) => $v !== null);
                        $companyId = Company::create($createFields);
                    }

                    // Cria lead vinculado à empresa
                    $leadId = Lead::create([
                        'company_id'        => $companyId,
                        'pipeline_stage_id' => 1, // Novo
                        'assigned_to'       => $userId,
                        'score'             => $leadData['score'] ?? 0,
                        'source'            => 'prospecção',
                        'status'            => 'active',
                        'ia_analise'        => $leadData['ia_analise'] ?? '',
                        'ia_market_analysis'=> $summary['ia_market_analysis'] ?? '',
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ]);

                    // Criar atividade inicial
                    LeadActivity::create([
                        'lead_id' => $leadId,
                        'user_id' => $userId,
                        'type' => 'created',
                        'description' => 'Lead importado da prospecção',
                    ]);

                    $imported++;
                } catch (\Throwable $e) {
                    Logger::error("ProspecController::import item error", ["exception" => $e->getMessage()]);
                    $errors++;
                }
            }

            $pdo->commit();

            // Audit log
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'prospec_import',
                'entity_type' => 'search',
                'entity_id' => null,
                'details' => json_encode(['search_id' => $id, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Response::json([
                'success'  => true,
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
                'total'    => count($leads),
            ])->send();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Logger::error("ProspecController::import error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao importar leads. Nenhum dado foi alterado.'])->send();
        }
    }

    /**
     * POST /prospec/import-lead/{searchId}/{leadId} — Importa lead individual do Prospector pro CRM
     */
    public function importLead(string $searchId, string $leadId): void
    {
        $this->requireLogin();
        $userId = Auth::userId();

        // Verificar limite do plano
        if (!PlanLimits::canCreateLead($userId)) {
            $remaining = PlanLimits::getRemaining('leads', $userId);
            Response::json(['success' => false, 'error' => "Limite do plano atingido (restam {$remaining} leads)."])->send();
            return;
        }

        // Busca dados da busca no Prospector
        $data = ProspecService::getStatus($searchId);

        if (!($data['success'] ?? false)) {
            Response::json(['success' => false, 'error' => 'Erro ao buscar dados da prospecção.'])->send();
            return;
        }

        $leads   = $data['leads'] ?? [];
        $summary = $data['summary'] ?? [];
        $leadData = null;

        // Encontrar o lead específico pelo ID
        foreach ($leads as $l) {
            if (($l['id'] ?? '') === $leadId) {
                $leadData = $l;
                break;
            }
        }

        if (!$leadData) {
            Response::json(['success' => false, 'error' => 'Lead não encontrado nesta busca.'])->send();
            return;
        }

        $companyName = $leadData['title'] ?? $leadData['maps_title'] ?? '';
        if (!$companyName) {
            Response::json(['success' => false, 'error' => 'Lead sem nome de empresa.'])->send();
            return;
        }

        $niche   = $summary['niche'] ?? '';
        $city    = $summary['city'] ?? '';
        $state   = $summary['state'] ?? '';
        $siteUrl = $leadData['site_url'] ?? $leadData['link'] ?? '';
        $toBool  = fn($val) => $val ? 'true' : 'false';

        $pdo = \App\Core\Model::getPdo();
        $pdo->beginTransaction();

        try {
            // Verifica se empresa já existe
            $existing = null;
            if ($siteUrl) {
                $existing = Company::findWhere('site_url', $siteUrl);
            }
            if (!$existing && $companyName) {
                $existing = Company::query()->where('name', $companyName)->first();
            }

            $companyFields = [
                'name'              => $companyName,
                'niche'             => $niche ?: ($leadData['maps_category'] ?? ''),
                'city'              => $city ?: '',
                'state'             => $state,
                'site_url'          => $siteUrl,
                'instagram'         => $leadData['instagram_url'] ?? '',
                'facebook'          => $leadData['facebook_url'] ?? '',
                // ─── Campos CNPJ (enriquecimento) ───
                'cnpj'              => $leadData['cnpj'] ?? null,
                'razao_social'      => $leadData['razao_social'] ?? null,
                'situacao_cnpj'     => $leadData['situacao'] ?? null,
                'capital_social'    => !empty($leadData['capital_social']) ? $leadData['capital_social'] : null,
                'data_inicio'       => !empty($leadData['data_inicio']) ? $leadData['data_inicio'] : null,
                'opcao_pelo_mei'    => $toBool($leadData['opcao_pelo_mei'] ?? false),
                'opcao_pelo_simples'=> $toBool($leadData['opcao_pelo_simples'] ?? false),
                'cnae_descricao'    => $leadData['cnae_descricao'] ?? null,
                'natureza_juridica' => $leadData['natureza_juridica'] ?? null,
                'porte'             => $leadData['porte'] ?? null,
                'email_receita'     => $leadData['email_receita'] ?? null,
                'telefone_receita'  => $leadData['telefone_receita'] ?? null,
                'socios'            => !empty($leadData['socios']) ? json_encode($leadData['socios'], JSON_UNESCAPED_UNICODE) : null,
                // ─── Campos Site scraping (enriquecimento) ───
                'site_emails'       => !empty($leadData['site_emails']) ? json_encode($leadData['site_emails'], JSON_UNESCAPED_UNICODE) : null,
                'site_phones'       => !empty($leadData['site_phones']) ? json_encode($leadData['site_phones'], JSON_UNESCAPED_UNICODE) : null,
                'site_instagram'    => $leadData['site_instagram'] ?? null,
                'site_facebook'    => $leadData['site_facebook'] ?? null,
                'site_youtube'     => $leadData['site_youtube'] ?? null,
                'site_tiktok'      => $leadData['site_tiktok'] ?? null,
                'cnpj_source'       => $leadData['cnpj_source'] ?? null,
                'snippet'           => $leadData['snippet'] ?? null,
                // ─── Campos Maps ───
                'maps_rating'       => $leadData['maps_rating'] ?? null,
                'maps_reviews'      => is_null($leadData['maps_reviews'] ?? null) ? 0 : (int)$leadData['maps_reviews'],
                'maps_address'      => $leadData['maps_address'] ?? '',
                'maps_phone'        => $leadData['maps_phone'] ?? '',
                'maps_category'     => $leadData['maps_category'] ?? '',
                'maps_lat'          => $leadData['maps_lat'] ?? null,
                'maps_lng'          => $leadData['maps_lng'] ?? null,
                // ─── Flags e Score ───
                'score'             => $leadData['score'] ?? 0,
                'tem_site'          => $toBool($leadData['tem_site'] ?? false),
                'tem_instagram'     => $toBool($leadData['tem_instagram'] ?? false),
                'tem_facebook'      => $toBool($leadData['tem_facebook'] ?? false),
                'tem_maps'          => $toBool($leadData['tem_maps'] ?? false),
                'tem_ads'           => $toBool($leadData['tem_ads'] ?? false),
                'enrichment_status' => !empty($leadData['ia_analise']) ? 'analyzed' : 'pending',
                'updated_at'        => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                // Atualiza empresa existente — só sobrescreve campos não-vazios
                $updateFields = array_filter($companyFields, function($v, $k) {
                    if (in_array($k, ['score', 'tem_site', 'tem_instagram', 'tem_facebook', 'tem_maps', 'tem_ads', 'enrichment_status', 'updated_at'])) return true;
                    return $v !== null && $v !== '' && $v !== '[]';
                }, ARRAY_FILTER_USE_BOTH);
                Company::updateById($existing['id'], $updateFields);
                $companyId = $existing['id'];

                // Verifica se já tem lead ativo
                $existingLead = Lead::query()
                    ->where('company_id', $companyId)
                    ->where('assigned_to', $userId)
                    ->where('status', 'active')
                    ->first();

                if ($existingLead) {
                    Lead::updateById($existingLead['id'], [
                        'score'              => $leadData['score'] ?? $existingLead['score'],
                        'ia_analise'         => $leadData['ia_analise'] ?? $existingLead['ia_analise'],
                        'ia_market_analysis' => $summary['ia_market_analysis'] ?? $existingLead['ia_market_analysis'],
                        'source'             => 'prospecção',
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ]);
                    $pdo->commit();
                    Response::json(['success' => true, 'action' => 'updated', 'message' => 'Lead atualizado com sucesso!'])->send();
                    return;
                }
            } else {
                $companyFields['created_by'] = $userId;
                $companyFields['created_at'] = date('Y-m-d H:i:s');
                $createFields = array_filter($companyFields, fn($v) => $v !== null);
                $companyId = Company::create($createFields);
            }

            // Cria lead
            $leadIdNew = Lead::create([
                'company_id'         => $companyId,
                'pipeline_stage_id'  => 1,
                'assigned_to'        => $userId,
                'score'              => $leadData['score'] ?? 0,
                'source'             => 'prospecção',
                'status'             => 'active',
                'ia_analise'         => $leadData['ia_analise'] ?? '',
                'ia_market_analysis' => $summary['ia_market_analysis'] ?? '',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

            LeadActivity::create([
                'lead_id' => $leadIdNew,
                'user_id' => $userId,
                'type' => 'created',
                'description' => 'Lead importado da prospecção',
            ]);

            $pdo->commit();

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'prospec_import_lead',
                'entity_type' => 'lead',
                'entity_id' => $leadIdNew,
                'details' => json_encode(['search_id' => $searchId, 'lead_id' => $leadId, 'company' => $companyName]),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

            Response::json(['success' => true, 'action' => 'created', 'message' => 'Lead importado com sucesso!'])->send();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Logger::error("ProspecController::importLead error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao importar lead.'])->send();
        }
    }

    /**
     * POST /prospec/session/{id}/diagnose/{leadId} — AJAX: diagnóstico IA por lead
     */
    public function diagnose(string $id, string $leadId): void
    {
        $this->requireLogin();

        // Verificar se pode usar IA
        if (!PlanLimits::canUseAI(Auth::userId())) {
            Response::json(['success' => false, 'error' => 'Diagnóstico IA disponível apenas no plano Pro ou superior.'])->send();
            return;
        }

        // Rate limit: 10 diagnósticos/minuto por usuário
        $rlKey = 'prospec_diagnose:' . Auth::userId();
        if (!RateLimit::check($rlKey, 10, 60)) {
            Response::json(['success' => false, 'error' => 'Limite atingido: máximo de 10 diagnósticos por minuto.'])->send();
            return;
        }

        try {
            RateLimit::hit($rlKey, 60);
            $result = ProspecService::diagnoseLead($id, $leadId);

            if ($result['success'] ?? false) {
                AuditLog::create([
                    'user_id' => Auth::userId(),
                    'action' => 'prospec_diagnose',
                    'entity_type' => 'search',
                    'entity_id' => $id,
                    'details' => json_encode(['search_id' => $id, 'lead_id' => $leadId]),
                    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]);
            }

            Response::json($result)->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::diagnose error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao gerar diagnóstico. Tente novamente.'])->send();
        }
    }

    /**
     * POST /prospec/session/{id}/analyze-market — AJAX: análise de mercado com IA
     */
    public function analyzeMarketAction(string $id): void
    {
        $this->requireLogin();

        // Verificar se pode usar IA
        if (!PlanLimits::canUseAI(Auth::userId())) {
            Response::json(['success' => false, 'error' => 'Análise IA disponível apenas no plano Pro ou superior.'])->send();
            return;
        }

        // Rate limit: 5 análises de mercado/minuto por usuário
        $rlKey = 'prospec_analyze_market:' . Auth::userId();
        if (!RateLimit::check($rlKey, 5, 60)) {
            Response::json(['success' => false, 'error' => 'Limite atingido: máximo de 5 análises de mercado por minuto.'])->send();
            return;
        }

        try {
            RateLimit::hit($rlKey, 60);
            $result = ProspecService::analyzeMarket($id);

            if ($result['success'] ?? false) {
                AuditLog::create([
                    'user_id' => Auth::userId(),
                    'action' => 'prospec_analyze_market',
                    'entity_type' => 'search',
                    'entity_id' => $id,
                    'details' => json_encode(['search_id' => $id]),
                    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]);
            }

            Response::json($result)->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::analyzeMarketAction error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro na análise de mercado. Tente novamente.'])->send();
        }
    }

    /**
     * GET /prospec/session/{id}/lead/{leadId} — AJAX: detalhe de um lead
     */
    public function leadDetail(string $id, string $leadId): void
    {
        $this->requireLogin();

        try {
            $result = ProspecService::getLeadDetail($id, $leadId);
            Response::json($result)->send();
        } catch (\Throwable $e) {
            Logger::error("ProspecController::leadDetail error", ["exception" => $e->getMessage()]);
            Response::json(['success' => false, 'error' => 'Erro ao buscar detalhes do lead.'])->send();
        }
    }

    /**
     * GET /prospec/export/{id} — Exporta resultados de uma busca (CSV)
     */
    public function export(string $id): void
    {
        $this->requireLogin();

        $data = ProspecService::getStatus($id);

        if (!($data['success'] ?? false)) {
            $httpCode = $data['http_code'] ?? 0;
            $errorMsg = $this->getApiErrorMessage($data, $httpCode);
            Flash::error($errorMsg);
            Response::redirect('/prospec/session/' . $id)->send();
            return;
        }

        $leads   = $data['leads'] ?? [];
        $summary = $data['summary'] ?? [];
        $niche   = $summary['niche'] ?? '';
        $city    = $summary['city'] ?? '';

        // Gerar CSV a partir dos dados disponíveis
        $filename = 'prospec_' . $id . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // BOM para Excel ler UTF-8
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeçalho
        fputcsv($out, ['Empresa', 'Nicho', 'Cidade', 'Site', 'Instagram', 'Telefone', 'Email', 'Score', 'CNPJ', 'Endereço', 'Avaliação', 'Reviews', 'Status'], ';');

        foreach ($leads as $lead) {
            fputcsv($out, [
                $lead['title'] ?? $lead['maps_title'] ?? '',
                $lead['maps_category'] ?? $niche,
                $city,
                $lead['site_url'] ?? $lead['link'] ?? '',
                $lead['instagram_url'] ?? '',
                $lead['maps_phone'] ?? '',
                $lead['company_email'] ?? '',
                $lead['score'] ?? 0,
                $lead['cnpj'] ?? '',
                $lead['maps_address'] ?? '',
                $lead['maps_rating'] ?? '',
                $lead['maps_reviews'] ?? '',
                !empty($lead['ia_analise']) ? 'Analisado' : (($lead['score'] ?? 0) > 0 ? 'Pontuado' : 'Descoberto'),
            ], ';');
        }

        fclose($out);
        exit;
    }

    /**
     * GET /prospec/history — Histórico completo de buscas
     */
    public function history(): void
    {
        $this->requireLogin();

        $history = ProspecService::getHistory();
        $searches = $history['success'] ?? false ? $history : [];

        $this->render('prospec/history', [
            'title'   => 'Histórico de Prospecção — Prospec CRM',
            'searches'=> $searches,
        ]);
    }

    /**
     * Retorna mensagem de erro amigável para erros da API
     */
    private function getApiErrorMessage(array $result, int $httpCode): string
    {
        $curlError = $result['error'] ?? '';

        // cURL errors (timeout, connection)
        if (str_starts_with($curlError, 'cURL error')) {
            if (str_contains($curlError, 'timeout') || str_contains($curlError, 'timed out')) {
                return 'Serviço de prospecção indisponível no momento. Tente novamente em alguns minutos.';
            }
            if (str_contains($curlError, 'connect') || str_contains($curlError, 'Connection refused')) {
                return 'Serviço de prospecção indisponível no momento. Tente novamente em alguns minutos.';
            }
            return 'Erro de conexão com o serviço de prospecção. Tente novamente.';
        }

        // HTTP status errors
        if ($httpCode === 429) {
            return 'Muitas buscas. Aguarde um momento antes de tentar novamente.';
        }
        if ($httpCode >= 500) {
            return 'Erro interno no serviço de prospecção. Nossos dados foram preservados. Tente novamente mais tarde.';
        }
        if ($httpCode === 404) {
            return 'Recurso não encontrado no serviço de prospecção.';
        }
        if ($httpCode === 0) {
            return 'Serviço de prospecção indisponível no momento. Tente novamente em alguns minutos.';
        }

        return $result['error'] ?? 'Erro desconhecido ao comunicar com o serviço de prospecção.';
    }
}