<?php
/**
 * migrate_prospector.php — Migração de dados do Prospector (JSON) para PostgreSQL
 * 
 * Lê os JSON files do Prospector e importa para o CRM.
 * 
 * Uso: docker exec prospec-crm-php php /app/scripts/migrate_prospector.php [data_dir]
 * 
 * Se data_dir não for informado, tenta localizar automaticamente.
 */

require_once __DIR__ . '/../app/Core/Config.php';
$configClass = 'App\Core\Config';
$configClass::load(__DIR__ . '/../.env');

use App\Core\Config;

// Conexão com PostgreSQL
$dsn = "pgsql:host=" . Config::get('DB_HOST', 'postgres') 
     . ";port=" . Config::get('DB_PORT', 5432) 
     . ";dbname=" . Config::get('DB_NAME', 'prospec_crm');

try {
    $pdo = new PDO($dsn, Config::get('DB_USER', 'prospec'), Config::get('DB_PASS', 'prospec123'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    exit(1);
}

$dataDir = $argv[1] ?? null;

// Tenta localizar automaticamente
if (!$dataDir) {
    $candidates = [
        '/app/data',                          // Dentro do container
        '/root/.openclaw/workspace/main/prospector/backend/data',
        __DIR__ . '/../../prospector/backend/data',
        __DIR__ . '/../data',
    ];
    
    foreach ($candidates as $dir) {
        if (is_dir($dir) && count(glob($dir . '/*.json')) > 0) {
            $dataDir = $dir;
            break;
        }
    }
}

if (!$dataDir || !is_dir($dataDir)) {
    echo "⚠️  Diretório de dados do Prospector não encontrado.\n";
    echo "   Uso: php scripts/migrate_prospector.php /caminho/para/data/\n";
    echo "   O diretório deve conter os arquivos .json do Prospector.\n\n";
    echo "📝 Criando tabela de controle de migração...\n";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS _migration_prospector (
        id SERIAL PRIMARY KEY,
        source_file VARCHAR(255) NOT NULL UNIQUE,
        sessions_imported INTEGER DEFAULT 0,
        companies_imported INTEGER DEFAULT 0,
        leads_imported INTEGER DEFAULT 0,
        errors TEXT,
        imported_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
    )");
    
    echo "✅ Tabela de controle criada. Execute novamente com o diretório de dados.\n";
    exit(0);
}

$files = glob($dataDir . '/*.json');

if (empty($files)) {
    echo "⚠️  Nenhum arquivo JSON encontrado em {$dataDir}\n";
    exit(0);
}

echo "📂 Diretório: {$dataDir}\n";
echo "📋 Encontrados " . count($files) . " arquivos JSON\n\n";

// Cria tabela de controle
$pdo->exec("CREATE TABLE IF NOT EXISTS _migration_prospector (
    id SERIAL PRIMARY KEY,
    source_file VARCHAR(255) NOT NULL UNIQUE,
    sessions_imported INTEGER DEFAULT 0,
    companies_imported INTEGER DEFAULT 0,
    leads_imported INTEGER DEFAULT 0,
    errors TEXT,
    imported_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
)");

// Busca default pipeline stage
$defaultStageId = $pdo->query("SELECT id FROM pipeline_stages WHERE is_default = true LIMIT 1")->fetchColumn();
if (!$defaultStageId) {
    $defaultStageId = $pdo->query("SELECT id FROM pipeline_stages ORDER BY position LIMIT 1")->fetchColumn();
}

// Busca admin user ID
$adminId = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetchColumn();
if (!$adminId) {
    $adminId = 1;
}

// Contadores
$totalSessions = 0;
$totalCompanies = 0;
$totalLeads = 0;
$totalErrors = 0;

foreach ($files as $file) {
    $filename = basename($file);
    
    // Verifica se já foi importado
    $stmt = $pdo->prepare("SELECT id FROM _migration_prospector WHERE source_file = :file");
    $stmt->execute(['file' => $filename]);
    if ($stmt->fetch()) {
        echo "  ⏭️  {$filename} (já importado)\n";
        continue;
    }
    
    $json = json_decode(file_get_contents($file), true);
    if (!$json) {
        echo "  ❌ {$filename} — JSON inválido\n";
        $totalErrors++;
        continue;
    }
    
    echo "  ▶️  Processando {$filename}...\n";
    
    $fileSessions = 0;
    $fileCompanies = 0;
    $fileLeads = 0;
    $errorLog = [];
    
    try {
        $pdo->beginTransaction();
        
        $summary = $json['summary'] ?? [];
        $leads = $json['leads'] ?? [];
        $status = $json['status'] ?? 'discovery';
        
        // 1. Criar search_session
        $stmt = $pdo->prepare("
            INSERT INTO search_sessions (
                user_id, niche, city, state, query, query_variations,
                raw_results_count, total_results, com_site, com_instagram,
                com_maps, com_ads, com_cnpj, com_site_email, com_site_phone,
                com_youtube, com_tiktok, ia_market_analysis, status,
                analyzed_count, total_to_analyze, created_at
            ) VALUES (
                :user_id, :niche, :city, :state, :query, :query_variations,
                :raw_results, :total_results, :com_site, :com_instagram,
                :com_maps, :com_ads, :com_cnpj, :com_site_email, :com_site_phone,
                :com_youtube, :com_tiktok, :ia_market, :status,
                :analyzed_count, :total_to_analyze, :created_at
            ) RETURNING id
        ");
        
        $stmt->execute([
            'user_id' => $adminId,
            'niche' => $summary['niche'] ?? '',
            'city' => $summary['city'] ?? '',
            'state' => $summary['state'] ?? '',
            'query' => $summary['query'] ?? null,
            'query_variations' => json_encode($summary['query_variations'] ?? []),
            'raw_results' => $summary['raw_results'] ?? 0,
            'total_results' => $summary['total_results'] ?? count($leads),
            'com_site' => $summary['com_site'] ?? 0,
            'com_instagram' => $summary['com_instagram'] ?? 0,
            'com_maps' => $summary['com_maps'] ?? 0,
            'com_ads' => $summary['com_ads'] ?? 0,
            'com_cnpj' => $summary['com_cnpj'] ?? 0,
            'com_site_email' => $summary['com_site_email'] ?? 0,
            'com_site_phone' => $summary['com_site_phone'] ?? 0,
            'com_youtube' => $summary['com_youtube'] ?? 0,
            'com_tiktok' => $summary['com_tiktok'] ?? 0,
            'ia_market' => $summary['ia_market_analysis'] ?? null,
            'status' => $status,
            'analyzed_count' => $summary['analyzed_count'] ?? 0,
            'total_to_analyze' => $summary['total_to_analyze'] ?? 0,
            'created_at' => $summary['timestamp'] ?? date('c'),
        ]);
        
        $sessionId = $stmt->fetchColumn();
        $fileSessions = 1;
        
        // 2. Processar cada lead → company + lead + search_lead + activity
        foreach ($leads as $idx => $leadData) {
            $title = $leadData['title'] ?? '';
            $cnpj = $leadData['cnpj'] ?? '';
            
            if (empty($title) && empty($cnpj)) {
                continue; // Pula sem nome
            }
            
            $companyId = null;
            
            // Dedup por CNPJ
            if (!empty($cnpj)) {
                $stmt = $pdo->prepare("SELECT id FROM companies WHERE cnpj = :cnpj LIMIT 1");
                $stmt->execute(['cnpj' => $cnpj]);
                $existing = $stmt->fetchColumn();
                if ($existing) {
                    $companyId = $existing;
                }
            }
            
            // Dedup por nome (se não achou por CNPJ)
            if (!$companyId && !empty($title)) {
                $stmt = $pdo->prepare("SELECT id FROM companies WHERE name = :name LIMIT 1");
                $stmt->execute(['name' => $title]);
                $existing = $stmt->fetchColumn();
                if ($existing) {
                    $companyId = $existing;
                }
            }
            
            // Criar company se não existe
            if (!$companyId) {
                $stmt = $pdo->prepare("
                    INSERT INTO companies (
                        name, cnpj, niche, city, state, site_url, instagram,
                        phone, email, maps_rating, maps_reviews, maps_address,
                        maps_phone, maps_category, maps_lat, maps_lng, score,
                        razao_social, situacao, capital_social, data_inicio,
                        opcao_pelo_mei, opcao_pelo_simples, cnae_descricao,
                        natureza_juridica, porte, email_receita, telefone_receita,
                        socios, site_emails, site_phones, site_instagram,
                        site_facebook, site_youtube, site_tiktok,
                        cnpj_source, enrichment_status,
                        tem_site, tem_instagram, tem_facebook, tem_maps, tem_ads,
                        created_by
                    ) VALUES (
                        :name, :cnpj, :niche, :city, :state, :site_url, :instagram,
                        :phone, :email, :maps_rating, :maps_reviews, :maps_address,
                        :maps_phone, :maps_category, :maps_lat, :maps_lng, :score,
                        :razao_social, :situacao, :capital_social, :data_inicio,
                        :opcao_mei, :opcao_simples, :cnae_descricao,
                        :natureza_juridica, :porte, :email_receita, :telefone_receita,
                        :socios, :site_emails, :site_phones, :site_instagram,
                        :site_facebook, :site_youtube, :site_tiktok,
                        :cnpj_source, :enrichment_status,
                        :tem_site, :tem_instagram, :tem_facebook, :tem_maps, :tem_ads,
                        :created_by
                    ) RETURNING id
                ");
                
                $stmt->execute([
                    'name' => $title,
                    'cnpj' => $cnpj ?: null,
                    'niche' => $summary['niche'] ?? null,
                    'city' => $summary['city'] ?? null,
                    'state' => $summary['state'] ?? null,
                    'site_url' => $leadData['site_url'] ?? $leadData['link'] ?? null,
                    'instagram' => $leadData['instagram_url'] ?? $leadData['instagram'] ?? null,
                    'phone' => $leadData['phone'] ?? $leadData['maps_phone'] ?? null,
                    'email' => $leadData['email'] ?? $leadData['email_receita'] ?? null,
                    'maps_rating' => $leadData['maps_rating'] ?? null,
                    'maps_reviews' => $leadData['maps_reviews'] ?? 0,
                    'maps_address' => $leadData['maps_address'] ?? null,
                    'maps_phone' => $leadData['maps_phone'] ?? null,
                    'maps_category' => $leadData['maps_category'] ?? null,
                    'maps_lat' => $leadData['maps_lat'] ?? null,
                    'maps_lng' => $leadData['maps_lng'] ?? null,
                    'score' => $leadData['score'] ?? 0,
                    'razao_social' => $leadData['razao_social'] ?? null,
                    'situacao' => $leadData['situacao'] ?? null,
                    'capital_social' => $leadData['capital_social'] ?? null,
                    'data_inicio' => $leadData['data_inicio'] ?? null,
                    'opcao_mei' => $leadData['opcao_pelo_mei'] ?? false,
                    'opcao_simples' => $leadData['opcao_pelo_simples'] ?? false,
                    'cnae_descricao' => $leadData['cnae_descricao'] ?? null,
                    'natureza_juridica' => $leadData['natureza_juridica'] ?? null,
                    'porte' => $leadData['porte'] ?? null,
                    'email_receita' => $leadData['email_receita'] ?? null,
                    'telefone_receita' => $leadData['telefone_receita'] ?? null,
                    'socios' => isset($leadData['socios']) ? json_encode($leadData['socios']) : null,
                    'site_emails' => json_encode($leadData['site_emails'] ?? []),
                    'site_phones' => json_encode($leadData['site_phones'] ?? []),
                    'site_instagram' => $leadData['site_instagram'] ?? null,
                    'site_facebook' => $leadData['site_facebook'] ?? $leadData['facebook_url'] ?? null,
                    'site_youtube' => $leadData['site_youtube'] ?? $leadData['youtube_url'] ?? null,
                    'site_tiktok' => $leadData['site_tiktok'] ?? $leadData['tiktok_url'] ?? null,
                    'cnpj_source' => $leadData['cnpj_source'] ?? null,
                    'enrichment_status' => !empty($leadData['cnpj']) ? 'partial' : 'pending',
                    'tem_site' => $leadData['tem_site'] ?? !empty($leadData['site_url']),
                    'tem_instagram' => $leadData['tem_instagram'] ?? !empty($leadData['instagram_url']),
                    'tem_facebook' => $leadData['tem_facebook'] ?? !empty($leadData['facebook_url']),
                    'tem_maps' => $leadData['tem_maps'] ?? false,
                    'tem_ads' => $leadData['tem_ads'] ?? false,
                    'created_by' => $adminId,
                ]);
                
                $companyId = $stmt->fetchColumn();
                $fileCompanies++;
            }
            
            // Criar lead
            $stmt = $pdo->prepare("
                INSERT INTO leads (
                    company_id, pipeline_stage_id, assigned_to, score,
                    source, status, ia_analise, created_at
                ) VALUES (
                    :company_id, :stage_id, :assigned_to, :score,
                    'prospecção', 'active', :ia_analise, :created_at
                ) RETURNING id
            ");
            
            $stmt->execute([
                'company_id' => $companyId,
                'stage_id' => $defaultStageId,
                'assigned_to' => $adminId,
                'score' => $leadData['score'] ?? 0,
                'ia_analise' => $leadData['ia_analise'] ?? null,
                'created_at' => $summary['timestamp'] ?? date('c'),
            ]);
            
            $leadId = $stmt->fetchColumn();
            $fileLeads++;
            
            // Criar search_lead
            $stmt = $pdo->prepare("
                INSERT INTO search_leads (search_id, company_id, position, is_place)
                VALUES (:search_id, :company_id, :position, :is_place)
            ");
            
            $stmt->execute([
                'search_id' => $sessionId,
                'company_id' => $companyId,
                'position' => $idx,
                'is_place' => $leadData['is_place'] ?? false,
            ]);
            
            // Criar atividade de IA se existe
            if (!empty($leadData['ia_analise'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO lead_activities (lead_id, user_id, type, description, metadata)
                    VALUES (:lead_id, :user_id, 'ia_analysis', :description, :metadata)
                ");
                
                $stmt->execute([
                    'lead_id' => $leadId,
                    'user_id' => $adminId,
                    'description' => 'Análise IA importada do Prospector',
                    'metadata' => json_encode(['ia_analise' => $leadData['ia_analise']]),
                ]);
            }
            
            // Criar atividade de diagnóstico se existe
            if (!empty($leadData['diagnostico'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO lead_activities (lead_id, user_id, type, description, metadata)
                    VALUES (:lead_id, :user_id, 'diagnosis', :description, :metadata)
                ");
                
                $stmt->execute([
                    'lead_id' => $leadId,
                    'user_id' => $adminId,
                    'description' => 'Diagnóstico importado do Prospector',
                    'metadata' => json_encode(['diagnostico' => $leadData['diagnostico']]),
                ]);
            }
        }
        
        $pdo->commit();
        
        // Registra importação
        $stmt = $pdo->prepare("
            INSERT INTO _migration_prospector (source_file, sessions_imported, companies_imported, leads_imported)
            VALUES (:file, :sessions, :companies, :leads)
        ");
        $stmt->execute([
            'file' => $filename,
            'sessions' => $fileSessions,
            'companies' => $fileCompanies,
            'leads' => $fileLeads,
        ]);
        
        $totalSessions += $fileSessions;
        $totalCompanies += $fileCompanies;
        $totalLeads += $fileLeads;
        
        echo "    ✅ {$fileSessions} sessão, {$fileCompanies} empresas, {$fileLeads} leads\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorLog[] = $e->getMessage();
        $totalErrors++;
        echo "    ❌ Erro: " . $e->getMessage() . "\n";
    }
}

echo "\n═══════════════════════════════════════\n";
echo "📊 Resultado da Migração:\n";
echo "   Sessões:  {$totalSessions}\n";
echo "   Empresas: {$totalCompanies}\n";
echo "   Leads:    {$totalLeads}\n";
echo "   Erros:    {$totalErrors}\n";
echo "═══════════════════════════════════════\n";

if ($totalErrors === 0) {
    echo "\n✨ Migração concluída com sucesso!\n";
} else {
    echo "\n⚠️  Migração concluída com {$totalErrors} erro(s).\n";
}