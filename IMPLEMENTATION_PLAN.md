# Plano de Implementação — Prospector → Prospec CRM

> Análise completa do Prospector standalone vs Prospec CRM, com plano detalhado para integrar toda funcionalidade faltante.

---

## 1. Visão Geral

### Status Atual
O Prospec CRM já integra com o Prospector API (Flask, porta 8088) via `ProspecService.php`, mas com funcionalidades parciais. O CRM consegue:
- ✅ Buscar empresas (Discovery)
- ✅ Enriquecer (CNPJ + site scraping)
- ✅ Pontuar (scoring)
- ✅ Ver histórico de buscas
- ✅ Importar leads (batch e individual) para o CRM
- ✅ Análise IA por lead (endpoint existe, mas **QUEBRA por timeout**)
- ✅ Polling de status da sessão

### Problemas Críticos
1. **Timeout de 30s na análise IA** — a operação demora ~90s, mas `ProspecService::post()` usa timeout de 30s
2. **Análise IA batch vs individual** — o botão "Analisar com IA" chama `/analyze/{id}` (batch legacy), deveria usar `/analyze-leads?count=1` (incremental)
3. **Falta a etapa "Análise de Mercado"** — o Prospector tem 2 chamadas IA separadas (mercado + leads), o CRM combina tudo em uma
4. **Falta Diagnóstico por Lead** — feature completa do Prospector que gera proposta de vendas
5. **Falta botão "Redescobrir"** — re-run Discovery preservando dados enriquecidos
6. **Falta exportação CSV** — link existe na view mas não tem route/controller
7. **Falta edição inline de leads** — o Prospector permite editar dados do lead
8. **Falta exclusão de leads** — remover leads espúrios da busca
9. **Falta edição da análise IA** — o Prospector permite editar o texto gerado pela IA
10. **Falta "Executar Tudo"** — rodar pipeline completo (enrich→score→market→leads)

---

## 2. Mapeamento Completo de Endpoints

### Prospector API — Todos os Endpoints

| Método | Rota | Descrição | Timeout Ideal | CRM tem? |
|--------|------|-----------|---------------|----------|
| GET | `/api/health` | Health check | 10s | ❌ |
| POST | `/api/search` | Discovery (async, retorna search_id) | 30s | ✅ |
| POST | `/api/search/{id}/rediscover` | Re-run discovery (async, preserva enrich) | 30s | ❌ |
| POST | `/api/search/{id}/enrich` | CNPJ + site scraping (async) | 30s | ✅ |
| POST | `/api/search/{id}/score` | Calculate scores (sync, rápido) | 30s | ✅ |
| POST | `/api/search/{id}/analyze-market` | IA market analysis (sync, ~90s) | 120s | ❌ |
| POST | `/api/search/{id}/analyze-leads` | IA lead analysis (incremental, `?count=N` ou `?all=true`) | 120s | ⚠️ (via `/analyze`) |
| POST | `/api/search/{id}/analyze` | Legacy: market + all leads (sync, MUITO lento) | 120s | ✅ (errado) |
| GET | `/api/search/{id}` | Status completo da busca | 10s | ✅ |
| DELETE | `/api/search/{id}` | Deletar busca | 10s | ❌ |
| PUT | `/api/search/{id}/lead/{leadId}` | Editar campos do lead | 10s | ❌ |
| DELETE | `/api/search/{id}/lead/{leadId}` | Remover lead | 10s | ❌ |
| GET | `/api/search/{id}/lead/{leadId}` | Detalhe de um lead | 10s | ⚠️ (via getStatus) |
| POST | `/api/search/{id}/analyze-leads/{index}` | Re-analisar lead por índice | 120s | ❌ |
| PUT | `/api/search/{id}/lead/{leadId}/analysis` | Editar texto da análise IA | 10s | ❌ |
| POST | `/api/search/{id}/diagnose/{leadId}` | Gerar diagnóstico de vendas IA | 120s | ❌ |
| GET | `/api/history` | Histórico de buscas | 10s | ✅ |

### Endpoints do CRM — Rotas Atuais

| Método | Rota | Controller@Method |
|--------|------|-------------------|
| GET | `/prospec` | ProspecController@index |
| POST | `/prospec/search` | ProspecController@search |
| GET | `/prospec/session/{id}` | ProspecController@session |
| GET | `/prospec/session/{id}/status` | ProspecController@sessionStatus |
| POST | `/prospec/enrich/{id}` | ProspecController@enrich |
| POST | `/prospec/score/{id}` | ProspecController@score |
| POST | `/prospec/analyze/{id}` | ProspecController@analyze (⚠️ BATCH LEGACY) |
| POST | `/prospec/session/{id}/analyze-lead` | ProspecController@analyzeLead |
| POST | `/prospec/import/{id}` | ProspecController@import |
| POST | `/prospec/import-lead/{searchId}/{leadId}` | ProspecController@importLead |
| GET | `/prospec/history` | ProspecController@history |

---

## 3. Bug Crítico: Timeout de 30s na Análise IA

### Problema
```php
// ProspecService.php — LINHA ATUAL (BUG)
private static int $timeout = 30;

// O analyze() usa postLong() que já usa 120s — CORRETO
private static int $longTimeout = 120;
```

### Análise
O `ProspecService::analyze()` já usa `postLong()` (120s). **MAS** o `ProspecController::analyze()` chama `ProspecService::analyze($id)` sem passar `count`, então ele vai para o endpoint `/analyze` (legacy batch) que roda **TODAS** as análises de uma vez — isso pode demorar 10+ minutos se houver 20 leads.

O `analyzeLead()` (por lead) já funciona e usa `postLong()`. O problema real é:
1. O botão "Analisar com IA" no session view chama `runAction('analyze')` → vai para `ProspecController::analyze()` → `ProspecService::analyze($id)` (sem count) → endpoint `/search/{id}/analyze` (LEGACY, roda TUDO)
2. Esse endpoint legacy faz market analysis + TODAS as lead analyses em série → timeout

### Correção

**ProspecService.php** — adicionar método `analyzeMarket()`:
```php
public static function analyzeMarket(string $searchId): array
{
    return self::postLong("/search/{$searchId}/analyze-market");
}
```

**ProspecService.php** — adicionar método `analyzeNextLead()`:
```php
public static function analyzeNextLead(string $searchId, int $count = 1): array
{
    return self::postLong("/search/{$searchId}/analyze-leads", ['count' => $count]);
}
```

**ProspecService.php** — adicionar método `analyzeAllLeads()`:
```php
public static function analyzeAllLeads(string $searchId): array
{
    return self::postLong("/search/{$searchId}/analyze-leads?all=true");
}
```

**ProspecController.php** — trocar `analyze()` para chamar market + leads incremental:
```php
public function analyze(string $id): void
{
    $this->requireLogin();
    if (!PlanLimits::canUseAI(Auth::userId())) {
        Response::json(['success' => false, 'error' => 'Análise IA disponível apenas no plano Pro.'])->send();
        return;
    }
    try {
        // 1. Market analysis primeiro
        $result = ProspecService::analyzeMarket($id);
        if (!($result['success'] ?? false)) {
            Response::json($result)->send();
            return;
        }
        // 2. Depois iniciar análise de leads (count=1, incremental)
        $leadResult = ProspecService::analyzeNextLead($id, 1);
        Response::json([
            'success' => true,
            'market_done' => true,
            'lead_result' => $leadResult,
        ])->send();
    } catch (\Throwable $e) {
        error_log("ProspecController::analyze error: " . $e->getMessage());
        Response::json(['success' => false, 'error' => 'Erro ao analisar.'])->send();
    }
}
```

---

## 4. Pipeline do Prospector — 6 Etapas Completas

O Prospector tem **6 etapas** no pipeline (não 5 como o CRM mostra):

```
1. Discovery    → Busca Serper (organic + places) + dedup + spam filter
2. Enrich       → CNPJ via BrasilAPI + site scraping (emails, phones, social, CNPJ)
3. Score        → Algoritmo de scoring (capital social, presença digital, tempo, rating)
4. Market IA    → Análise de mercado com IA (1 chamada, ~90s)
5. Leads IA     → Análise por lead com IA (1 lead por vez, ~90s cada)
6. Diagnosis    → Diagnóstico de vendas por lead (1 chamada, ~90s cada)
```

O CRM mostra apenas 5 etapas (Discovery → Enrich → Score → Análise IA → Concluído), combinando Market e Leads em uma etapa, e faltando Diagnosis.

### Correção da Pipeline Visual

Trocar o pipeline de 5 para 6 etapas:
```php
$steps = [
    ['key' => 'discovery',        'label' => 'Descoberta',       'icon' => 'search',       'step' => 1],
    ['key' => 'enriching',       'label' => 'Enriquecer',       'icon' => 'database',     'step' => 2],
    ['key' => 'scoring',         'label' => 'Pontuar',          'icon' => 'bar-chart-2',  'step' => 3],
    ['key' => 'market_analyzed', 'label' => 'Mercado IA',      'icon' => 'trending-up',  'step' => 4],
    ['key' => 'analyzed',        'label' => 'Leads IA',         'icon' => 'sparkles',     'step' => 5],
    ['key' => 'completed',      'label' => 'Concluído',        'icon' => 'check-circle', 'step' => 6],
];
```

E mapear os status corretamente:
```php
$pipelineSteps = [
    'discovering'      => 1,
    'discovery'         => 1,
    'enriching'        => 2,
    'enriched'         => 2,
    'scoring'          => 3,
    'scored'           => 3,
    'market_analyzed'  => 4,
    'analyzing_leads'  => 5,
    'analyzed'         => 5,
    'completed'        => 6,
];
```

---

## 5. Funcionalidades Faltando — Detalhado

### 5.1 Análise de Mercado (Market Analysis) — **PRIORIDADE ALTA**

**O que é:** Chamada IA separada que analisa o mercado como um todo (oportunidades, concorrência, ticket médio, pontos fracos).

**O Problema:** O CRM chama `/analyze` (legacy) que faz market + leads de uma vez. Se o market analysis falhar, os leads não são analisados.

**Implementação:**

**Controller** — novo endpoint:
```php
public function analyzeMarket(string $id): void
{
    $this->requireLogin();
    if (!PlanLimits::canUseAI(Auth::userId())) {
        Response::json(['success' => false, 'error' => 'Análise IA disponível apenas no plano Pro.'])->send();
        return;
    }
    try {
        $result = ProspecService::analyzeMarket($id);
        Response::json($result)->send();
    } catch (\Throwable $e) {
        error_log("ProspecController::analyzeMarket error: " . $e->getMessage());
        Response::json(['success' => false, 'error' => 'Erro na análise de mercado.'])->send();
    }
}
```

**Rota:** `POST /prospec/analyze-market/{id}`

**Frontend** — botão "Analisar Mercado" separado na actions bar:
```html
<button @click="runAction('analyze-market')" :disabled="actionLoading || !canUseAI"
        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 ...">
    <i data-lucide="trending-up" class="w-4 h-4"></i>
    Analisar Mercado
</button>
```

### 5.2 Diagnóstico por Lead — **PRIORIDADE ALTA**

**O que é:** Para cada lead, gera um diagnóstico B2B completo com: pontos fracos, pontos fortes, oportunidade principal (serviço a vender, investimento, ROI), mensagem WhatsApp, urgência, estimativa de receita.

**API do Prospector:** `POST /api/search/{id}/diagnose/{leadId}`

**Implementação:**

**ProspecService.php** — novo método:
```php
public static function diagnose(string $searchId, string $leadId): array
{
    return self::postLong("/search/{$searchId}/diagnose/{$leadId}");
}
```

**Controller** — novo endpoint:
```php
public function diagnose(string $id, string $leadId): void
{
    $this->requireLogin();
    if (!PlanLimits::canUseAI(Auth::userId())) {
        Response::json(['success' => false, 'error' => 'Diagnóstico IA disponível apenas no plano Pro.'])->send();
        return;
    }
    $rlKey = 'prospec_diagnose:' . Auth::userId();
    if (!RateLimit::check($rlKey, 10, 60)) {
        Response::json(['success' => false, 'error' => 'Limite: 10 diagnósticos por minuto.'])->send();
        return;
    }
    try {
        RateLimit::hit($rlKey, 60);
        $result = ProspecService::diagnose($id, $leadId);
        Response::json($result)->send();
    } catch (\Throwable $e) {
        error_log("ProspecController::diagnose error: " . $e->getMessage());
        Response::json(['success' => false, 'error' => 'Erro ao gerar diagnóstico.'])->send();
    }
}
```

**Rota:** `POST /prospec/session/{id}/diagnose/{leadId}`

**Frontend** — adicionar botão "🔬 Diagnosticar" em cada lead card + modal de diagnóstico:
- Modal com: pontos fracos (com impacto + solução), pontos fortes, oportunidade principal (serviço, investimento, ROI, prazo), mensagem WhatsApp (com botão de copiar/enviar), urgência, estimativa de receita
- Botão "Re-diagnosticar" quando já tem diagnóstico
- Loading com aviso "pode levar ~90s"

### 5.3 Redescobrir (Rediscover) — **PRIORIDADE MÉDIA**

**O que é:** Re-roda Discovery (Serper searches) mas preserva dados enriquecidos (CNPJ, site scraping, IA) de leads que já existiam. Útil quando a busca inicial teve poucos resultados.

**API:** `POST /api/search/{id}/rediscover`

**Implementação:**

**ProspecService.php:**
```php
public static function rediscover(string $searchId): array
{
    return self::post("/search/{$searchId}/rediscover");
}
```

**Controller:**
```php
public function rediscover(string $id): void
{
    $this->requireLogin();
    try {
        $result = ProspecService::rediscover($id);
        Response::json($result)->send();
    } catch (\Throwable $e) {
        Response::json(['success' => false, 'error' => 'Erro ao redescobrir.'])->send();
    }
}
```

**Rota:** `POST /prospec/rediscover/{id}`

**Frontend** — botão "🔍 Redescobrir" na actions bar (antes de "Enriquecer")

### 5.4 Análise IA Incremental por Lead — **PRIORIDADE ALTA (CORREÇÃO)**

**Problema atual:** O botão "Analisar com IA" chama `runAction('analyze')` → `POST /prospec/analyze/{id}` → `ProspecService::analyze($id)` → `POST /search/{id}/analyze` (LEGACY batch).

**Correção:** O fluxo correto deve ser:
1. "Analisar Mercado" → chama `/analyze-market/{id}` (1 chamada, ~90s)
2. "Analisar Próximo Lead" → chama `/analyze-leads/{id}?count=1` (1 lead, ~90s)
3. "Analisar Todos" → chama `/analyze-leads/{id}?all=true` (async, polling)

**Frontend** — trocar o botão "Analisar com IA" por 3 botões:
```html
<!-- Market analysis -->
<button @click="runAction('analyze-market')" :disabled="actionLoading || !canUseAI"
        class="px-4 py-2 bg-purple-600 ...">
    📊 Analisar Mercado
</button>

<!-- Next lead -->
<button @click="analyzeNextLead()" :disabled="actionLoading || !canUseAI"
        class="px-4 py-2 bg-green-600 ...">
    🧠 Analisar Próximo Lead
</button>

<!-- All leads (async with polling) -->
<button @click="analyzeAllLeads()" :disabled="actionLoading || !canUseAI"
        class="px-4 py-2 bg-teal-600 ...">
    🚀 Analisar Todos
</button>
```

**JS** — `analyzeNextLead()`:
```javascript
async analyzeNextLead() {
    this.actionLoading = true;
    this.actionMsg = 'Analisando próximo lead com IA... (~90s)';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        const res = await fetch('/prospec/analyze-next-lead/' + this.searchId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const data = await res.json();
        if (data.success !== false) {
            this.actionSuccess = true;
            this.actionMsg = data.message || 'Lead analisado com sucesso!';
            this.refreshStatus();
        } else {
            this.actionSuccess = false;
            this.actionMsg = data.error || 'Erro na análise';
        }
    } catch (e) {
        this.actionSuccess = false;
        this.actionMsg = 'Erro de conexão. A análise pode demorar até 2 minutos.';
    }
    this.actionLoading = false;
}
```

**Controller** — novo método `analyzeNextLead`:
```php
public function analyzeNextLead(string $id): void
{
    $this->requireLogin();
    if (!PlanLimits::canUseAI(Auth::userId())) {
        Response::json(['success' => false, 'error' => 'Plano Pro necessário.'])->send();
        return;
    }
    try {
        $result = ProspecService::analyzeNextLead($id, 1);
        Response::json($result)->send();
    } catch (\Throwable $e) {
        Response::json(['success' => false, 'error' => 'Erro ao analisar lead.'])->send();
    }
}
```

**Controller** — novo método `analyzeAllLeads`:
```php
public function analyzeAllLeads(string $id): void
{
    $this->requireLogin();
    if (!PlanLimits::canUseAI(Auth::userId())) {
        Response::json(['success' => false, 'error' => 'Plano Pro necessário.'])->send();
        return;
    }
    try {
        // Chama analyze-leads?all=true — o Prospector roda em background
        $result = ProspecService::analyzeAllLeads($id);
        Response::json($result)->send();
    } catch (\Throwable $e) {
        Response::json(['success' => false, 'error' => 'Erro ao iniciar análise.'])->send();
    }
}
```

### 5.5 Edição de Leads — **PRIORIDADE MÉDIA**

**API:** `PUT /api/search/{id}/lead/{leadId}` — atualiza campos do lead
**API:** `PUT /api/search/{id}/lead/{leadId}/analysis` — edita texto da análise IA

**ProspecService.php:**
```php
public static function updateLead(string $searchId, string $leadId, array $fields): array
{
    return self::request('PUT', "/search/{$searchId}/lead/{$leadId}", $fields);
}

public static function editAnalysis(string $searchId, string $leadId, string $text): array
{
    return self::request('PUT', "/search/{$searchId}/lead/{$leadId}/analysis", ['ia_analise' => $text]);
}
```

**Nota:** `ProspecService::request()` precisa suportar método PUT (atualmente só GET e POST).

**Controller** — `updateLead` e `editAnalysis`:
```php
public function updateLead(string $id, string $leadId): void
{
    $this->requireLogin();
    $fields = $this->request->all();
    // Validar campos permitidos
    $allowed = ['title', 'site_url', 'instagram_url', 'maps_phone', 'maps_rating', ...];
    $filtered = array_intersect_key($fields, array_flip($allowed));
    try {
        $result = ProspecService::updateLead($id, $leadId, $filtered);
        Response::json($result)->send();
    } catch (\Throwable $e) {
        Response::json(['success' => false, 'error' => 'Erro ao atualizar lead.'])->send();
    }
}
```

**Rotas:**
- `POST /prospec/session/{id}/lead/{leadId}/update` (POST por causa de CSRF)
- `POST /prospec/session/{id}/lead/{leadId}/edit-analysis`

**Frontend** — formulário de edição inline no lead card (nome, site, instagram, telefone, etc.)

### 5.6 Exclusão de Leads — **PRIORIDADE MÉDIA**

**API:** `DELETE /api/search/{id}/lead/{leadId}`

**ProspecService.php:**
```php
public static function deleteLead(string $searchId, string $leadId): array
{
    return self::request('DELETE', "/search/{$searchId}/lead/{$leadId}");
}
```

**Nota:** `ProspecService::request()` precisa suportar método DELETE.

**Controller:**
```php
public function deleteLead(string $id, string $leadId): void
{
    $this->requireLogin();
    try {
        $result = ProspecService::deleteLead($id, $leadId);
        Response::json($result)->send();
    } catch (\Throwable $e) {
        Response::json(['success' => false, 'error' => 'Erro ao excluir lead.'])->send();
    }
}
```

**Rota:** `POST /prospec/session/{id}/lead/{leadId}/delete`

**Frontend** — botão 🗑️ no lead card (com confirmação)

### 5.7 Exportação CSV/JSON — **PRIORIDADE BAIXA**

**Situação:** O Prospector standalone NÃO tem endpoint de export. O CRM tem link para `/prospec/export/{id}` mas não tem a route.

**Implementação:** Gerar CSV no próprio CRM (sem depender da API do Prospector), usando os dados já obtidos via `getStatus()`.

**Controller:**
```php
public function export(string $id): void
{
    $this->requireLogin();
    $data = ProspecService::getStatus($id);
    if (!($data['success'] ?? false)) {
        Flash::error('Erro ao exportar');
        Response::redirect('/prospec')->send();
        return;
    }
    $leads = $data['leads'] ?? [];
    $summary = $data['summary'] ?? [];
    
    // Gerar CSV
    $filename = "prospec_{$id}_" . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // BOM para Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['Nome', 'Site', 'Instagram', 'Telefone', 'CNPJ', 'Rating', 'Reviews', 'Score', 'Endereço', 'Email Site', 'Análise IA']);
    
    foreach ($leads as $lead) {
        fputcsv($output, [
            $lead['title'] ?? $lead['maps_title'] ?? '',
            $lead['site_url'] ?? '',
            $lead['instagram_url'] ?? '',
            $lead['maps_phone'] ?? '',
            $lead['cnpj'] ?? '',
            $lead['maps_rating'] ?? '',
            $lead['maps_reviews'] ?? '',
            $lead['score'] ?? 0,
            $lead['maps_address'] ?? '',
            implode(', ', $lead['site_emails'] ?? []),
            is_string($lead['ia_analise'] ?? null) ? $lead['ia_analise'] : json_encode($lead['ia_analise'] ?? ''),
        ]);
    }
    fclose($output);
    exit;
}
```

**Rota:** `GET /prospec/export/{id}`

### 5.8 "Executar Tudo" (Run All Pipeline) — **PRIORIDADE MÉDIA**

**O que é:** Roda o pipeline completo automaticamente: Enrich → Score → Market IA → Leads IA

**Implementação:** No frontend, criar botão que executa as etapas em sequência com polling:
```javascript
async runAllPipeline() {
    const steps = ['enrich', 'score', 'analyze-market', 'analyze-next-lead'];
    for (const step of steps) {
        // Verificar status atual e pular etapas já feitas
        const status = await this.getCurrentStatus();
        const nextStep = this.getNextNeededStep(status);
        if (!nextStep) break;
        
        // Executar etapa
        await this.runAction(nextStep);
        
        // Esperar completar (polling)
        await this.waitForStepComplete();
    }
}
```

**Não precisa de endpoint novo** — é lógica pura do frontend.

### 5.9 Excluir Busca — **PRIORIDADE BAIXA**

**API:** `DELETE /api/search/{id}`

**ProspecService.php:**
```php
public static function deleteSearch(string $searchId): array
{
    return self::request('DELETE', "/search/{$searchId}");
}
```

**Rota:** `POST /prospec/search/{id}/delete`

---

## 6. ProspecService.php — Mudanças Necessárias

### 6.1 Suportar métodos PUT e DELETE

Atualmente `request()` só suporta GET e POST. Precisa adicionar:

```php
private static function put(string $path, array $data = []): array
{
    return self::request('PUT', $path, $data);
}

private static function delete(string $path): array
{
    return self::request('DELETE', $path);
}
```

E no `request()`, adicionar suporte para PUT e DELETE:
```php
if ($method === 'PUT') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
}

if ($method === 'DELETE') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
}
```

### 6.2 Novos Métodos a Adicionar

```php
// Análise de mercado (separada)
public static function analyzeMarket(string $searchId): array

// Análise incremental de leads (count=1 por padrão)
public static function analyzeNextLead(string $searchId, int $count = 1): array

// Análise de todos os leads (async)
public static function analyzeAllLeads(string $searchId): array

// Redescobrir (preserva dados enriquecidos)
public static function rediscover(string $searchId): array

// Diagnóstico de vendas por lead
public static function diagnose(string $searchId, string $leadId): array

// Editar campos do lead
public static function updateLead(string $searchId, string $leadId, array $fields): array

// Editar análise IA do lead
public static function editAnalysis(string $searchId, string $leadId, string $text): array

// Excluir lead da busca
public static function deleteLead(string $searchId, string $leadId): array

// Excluir busca
public static function deleteSearch(string $searchId): array

// Re-analisar lead por índice
public static function reanalyzeLeadByIndex(string $searchId, int $leadIndex): array
```

### 6.3 Marcar endpoint `/analyze` como DEPRECATED

O método `analyze()` atual chama o endpoint legacy que roda market + todos os leads. Manter para compatibilidade, mas adicionar comentário:

```php
/**
 * @deprecated Usar analyzeMarket() + analyzeNextLead() em vez deste.
 * Este endpoint roda market + ALL leads em uma única chamada (MUITO lento).
 */
public static function analyze(string $searchId, int $count = 0): array
```

---

## 7. Rotas Novas a Adicionar

```php
// config/routes.php — adicionar após as rotas prospec existentes

// Market analysis (separado)
Router::post('/prospec/analyze-market/{id}', 'ProspecController@analyzeMarket', ['AuthMiddleware', 'RateLimitMiddleware']);

// Análise incremental de leads
Router::post('/prospec/analyze-next-lead/{id}', 'ProspecController@analyzeNextLead', ['AuthMiddleware', 'RateLimitMiddleware']);

// Análise de todos os leads (async)
Router::post('/prospec/analyze-all-leads/{id}', 'ProspecController@analyzeAllLeads', ['AuthMiddleware', 'RateLimitMiddleware']);

// Redescobrir
Router::post('/prospec/rediscover/{id}', 'ProspecController@rediscover', ['AuthMiddleware', 'RateLimitMiddleware']);

// Diagnóstico por lead
Router::post('/prospec/session/{id}/diagnose/{leadId}', 'ProspecController@diagnose', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// Editar lead
Router::post('/prospec/session/{id}/lead/{leadId}/update', 'ProspecController@updateLead', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// Editar análise IA
Router::post('/prospec/session/{id}/lead/{leadId}/edit-analysis', 'ProspecController@editAnalysis', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// Excluir lead da busca
Router::post('/prospec/session/{id}/lead/{leadId}/delete', 'ProspecController@deleteLead', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// Exportar CSV
Router::get('/prospec/export/{id}', 'ProspecController@export', ['AuthMiddleware']);

// Excluir busca
Router::post('/prospec/search/{id}/delete', 'ProspecController@deleteSearch', ['AuthMiddleware', 'CsrfMiddleware']);

// Re-analisar lead por índice
Router::post('/prospec/session/{id}/reanalyze-lead/{leadIndex}', 'ProspecController@reanalyzeLead', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
```

---

## 8. Mudanças no Frontend (session.php)

### 8.1 Actions Bar — Reorganizar Botões

**Antes:**
```
[Enriquecer Todos] [Pontuar Todos] [Analisar com IA] [Importar para CRM] [Exportar]
```

**Depois:**
```
[🔍 Redescobrir] [📋 Enriquecer] [⭐ Pontuar] [📊 Mercado IA] [🧠 Próximo Lead] [🚀 Todos IA] [Importar CRM] [Exportar]
```

### 8.2 Lead Card — Adicionar Ações

Para cada lead card, adicionar:
- **Botão "🔬 Diagnosticar"** → abre modal de diagnóstico (se não tem diagnóstico)
- **Botão "🔄 Re-analisar"** → re-roda IA analysis para aquele lead
- **Botão ✏️ Editar** → formulário inline para editar campos
- **Botão 🗑️ Excluir** → remove lead (com confirmação)

### 8.3 Modal de Diagnóstico

Criar modal (ou seção expansível no lead card) com:
```
┌─────────────────────────────────────────┐
│ Diagnóstico: [Nome da Empresa]          │
├─────────────────────────────────────────┤
│ ⚠️ Pontos Fracos                        │
│   • Problema 1 — Impacto → Solução      │
│   • Problema 2 — Impacto → Solução      │
├─────────────────────────────────────────┤
│ ✅ Pontos Fortes                         │
│   • Ponto → Como aproveitar             │
├─────────────────────────────────────────┤
│ 🎯 Oportunidade Principal               │
│   Serviço: Criação de Site              │
│   Investimento: R$ 2.500                │
│   Retorno: R$ 5.000/mês                 │
│   Prazo: 4 semanas                      │
├─────────────────────────────────────────┤
│ 💬 Abordagem WhatsApp                   │
│   "Olá [nome], notei que..."            │
│   [Copiar] [Enviar no WhatsApp]         │
├─────────────────────────────────────────┤
│ 🔥 Urgência: Alta                       │
│    Motivo: Sem presença digital          │
├─────────────────────────────────────────┤
│ 💰 Estimativa Receita: R$ 3.000/mês    │
│                                         │
│ [🔄 Re-diagnosticar]                    │
└─────────────────────────────────────────┘
```

### 8.4 Seção de Análise IA no Lead Card — Melhorar Renderização

Atualmente a análise IA é mostrada como texto simples. Precisa renderizar como o Prospector faz:
- Se for JSON → mostrar seções formatadas (resumo, presença digital, posição mercado, público, observações)
- Se for string → mostrar como texto corrido
- Botão "✏️ Editar texto" para correção manual
- Botão "🔄 Re-analisar" para re-rodar a IA

### 8.5 Pipeline Progress — Adicionar Contadores

Mostrar progresso da análise de leads:
```
Análise IA: 5/20 leads analisados
```

Isso já está no `summary.analyzed_count` e `summary.total_to_analyze`.

### 8.6 Polling Inteligente para Análise

Quando `analyzeAllLeads` é chamado, o Prospector roda em background (status `analyzing_leads`). O CRM precisa:
1. Chamar o endpoint
2. Iniciar polling automático a cada 5s
3. Atualizar a interface com progresso
4. Parar polling quando status for `analyzed` ou `completed`

---

## 9. Ordem de Prioridade de Implementação

### Fase 1 — Correção Crítica (1-2h)

1. **Corrigir timeout** — `ProspecService::request()` suportar PUT/DELETE, confirmar que `postLong()` é usado corretamente
2. **Corrigir botão "Analisar com IA"** — trocar de `/analyze` (legacy batch) para `/analyze-market` + `/analyze-next-lead` (incremental)
3. **Adicionar `analyzeMarket()` no ProspecService e Controller**
4. **Adicionar `analyzeNextLead()` e `analyzeAllLeads()` no ProspecService e Controller**
5. **Adicionar rotas novas** para market + incremental leads
6. **Atualizar pipeline visual** para 6 etapas (separar Mercado IA e Leads IA)

### Fase 2 — Funcionalidades Core (3-4h)

7. **Diagnóstico por Lead** — Service + Controller + Rota + Frontend (modal)
8. **Análise IA incremental no frontend** — botões "Próximo Lead" + "Todos" + polling
9. **Redescobrir** — Service + Controller + Rota + Botão no frontend
10. **Exportar CSV** — Controller + Rota (link já existe no frontend)

### Fase 3 — Funcionalidades Auxiliares (2-3h)

11. **Edição de leads** — Service + Controller + Rota + Formulário inline
12. **Edição de análise IA** — Service + Controller + Rota + Editor de texto
13. **Exclusão de leads** — Service + Controller + Rota + Botão com confirmação
14. **Excluir busca** — Service + Controller + Rota + Botão no histórico
15. **"Executar Tudo"** — Lógica frontend de pipeline sequencial

### Fase 4 — Polimento (1-2h)

16. **Melhorar renderização de análise IA** — parsear JSON e mostrar seções
17. **Progress indicators** — mostrar "5/20 leads analisados"
18. **WhatsApp link** — botão para enviar diagnóstico via WhatsApp (usando dados do maps_phone)
19. **Depracar endpoint `/analyze` legacy** — manter para compat, mas não expor no frontend

---

## 10. Detalhes Técnicos Importantes

### 10.1 Timeout por Tipo de Operação

| Operação | Timeout cURL | Explicação |
|----------|-------------|------------|
| search, getStatus, history | 30s | Rápido, síncrono |
| enrich | 30s | Async (retorna imediatamente, roda em background) |
| score | 30s | Rápido, cálculo local |
| analyze-market | 120s | IA, ~90s |
| analyze-leads (count=1) | 120s | IA, ~90s por lead |
| analyze-leads (all=true) | 30s | Async (retorna imediatamente, roda em background) |
| diagnose | 120s | IA, ~90s |
| updateLead, editAnalysis, deleteLead | 30s | CRUD, rápido |

### 10.2 Padrão Async vs Sync no Prospector

O Prospector usa `threading.Thread` para operações longas:
- `/api/search` → async (discovery em background)
- `/api/search/{id}/enrich` → async (enrich em background)
- `/api/search/{id}/analyze-leads?all=true` → async (análise em background)
- `/api/search/{id}/analyze-leads?count=1` → **SYNC** (um lead, espera completar)
- `/api/search/{id}/analyze-market` → **SYNC** (espera completar)
- `/api/search/{id}/diagnose/{leadId}` → **SYNC** (espera completar)

Para operações sync longas (90s+), o CRM DEVE usar `postLong()` (120s timeout).

Para operações async, o CRM deve:
1. Chamar o endpoint (retorna 200 imediatamente com status inicial)
2. Iniciar polling via `getStatus()` a cada 3-5s
3. Parar polling quando status mudar para o próximo estágio

### 10.3 Campos de Busca — Análise

O Prospector standalone aceita:
```json
{
  "niche": "restaurante",
  "city": "São Paulo",
  "state": "SP",
  "max_results": 50
}
```

O CRM atual envia:
```json
{
  "niche": "...",
  "city": "...",
  "state": "PR"
}
```

**Falta:** campo `max_results` (default 50). Recomendado adicionar como campo opcional no formulário, com default 50 e máximo 200.

### 10.4 Rate Limits — Respeitar

O Prospector API tem rate limits implícitos:
- Serper API: ~60 req/min (o backend faz 2s delay entre queries)
- BrasilAPI: rate limit 3 req/min (o backend faz 0.3s throttle a cada 5 calls)
- Ollama/IA: rate limit do provider (fallback models se um falhar)

O CRM não precisa se preocupar com rate limits da API do Prospector — o backend do Prospector gerencia isso internamente. Só precisa respeitar os rate limits do próprio CRM (20 buscas/hora, 10 análises/min, 3 imports/hora).

### 10.5 Sanitização de Input

O Prospector já tem `sanitize_prompt_input()` que filtra injection patterns. O CRM deve sanitizar o input do usuário (niche, city, state) antes de enviar para a API, mas a sanitização principal acontece no backend do Prospector.

---

## 11. Resumo das Mudanças por Arquivo

| Arquivo | Mudanças |
|---------|----------|
| `app/Core/ProspecService.php` | +10 métodos novos, suporte PUT/DELETE, deprecated analyze() |
| `app/Controllers/ProspecController.php` | +10 actions novas, corrigir analyze(), atualizar analyzeLead() |
| `config/routes.php` | +11 rotas novas |
| `app/Views/prospec/session.php` | Pipeline 6 etapas, botões reorganizados, diagnóstico modal, edição/exclusão de leads, polling melhorado |
| `app/Views/prospec/index.php` | Adicionar campo max_results opcional |
| `app/Views/prospec/history.php` | Botão excluir busca |

---

## 12. Checklist de Verificação

Após implementação, verificar:

- [ ] "Analisar com IA" não dá mais timeout (usa postLong 120s)
- [ ] "Analisar Mercado" funciona como etapa separada
- [ ] "Analisar Próximo Lead" analisa 1 lead por vez (~90s, sync)
- [ ] "Analisar Todos" dispara em background com polling
- [ ] Diagnóstico por lead gera relatório B2B completo
- [ ] Botão WhatsApp funciona com a mensagem de abordagem
- [ ] Exportar CSV gera arquivo com BOM para Excel
- [ ] Editar lead funciona (PUT via POST + CSRF)
- [ ] Excluir lead funciona com confirmação
- [ ] Redescobrir preserva dados enriquecidos
- [ ] Pipeline visual mostra 6 etapas
- [ ] Progresso de análise é visível (5/20 leads)
- [ ] Rate limits do CRM são respeitados
- [ ] Erros da API do Prospector mostram mensagens amigáveis
- [ ] Campo max_results opcional no formulário de busca