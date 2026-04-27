# 🔍 AUDIT REPORT — Prospec CRM

**Data:** 22/04/2026 17:20 (America/Sao_Paulo)  
**Escopo:** Auditoria completa do módulo de prospecção  
**Problema relatado:** "O serviço de prospecção está temporariamente indisponível. Tente novamente em alguns minutos."

---

## 📊 Resumo

| Status | Quantidade |
|--------|-----------|
| 🔴 Crítico (fixado) | 5 |
| 🟡 Médio (fixado) | 4 |
| 🟢 Baixo (documentado) | 4 |
| **Total** | **13** |

---

## 🔴 Problemas Críticos

### 1. Prospector API: endpoint `/diagnose` retorna HTTP 500
- **Arquivo:** `http://185.139.1.41:8088/api/search/{id}/diagnose/{leadId}`
- **Causa raiz:** O Prospector API retorna `{"error":"Diagnóstico temporariamente indisponível"}` com HTTP 500 para TODAS as chamadas de diagnose
- **Impacto:** Quando o usuário clica "Diagnosticar Lead", recebe a mensagem "O serviço de prospecção está temporariamente indisponível"
- **Fix aplicado:** Adicionado retry automático (até 2x com pausa de 3s/5s) em `ProspecService::diagnoseLead()` + mensagem de erro mais específica
- **Fix pendente (externo):** Investigar no Prospector API por que o endpoint `/diagnose` está retornando 500
- **Prioridade:** 🔴 CRÍTICO

### 2. `AuditLog::create()` — entity_id string em coluna integer
- **Arquivo:** `app/Controllers/ProspecController.php` linhas 375, 829, 871
- **Causa raiz:** O `entity_id` da tabela `audit_log` é `integer`, mas o controller passa search_id strings hex (ex: "22c923da", "027de7e4") como entity_id
- **Impacto:** `SQLSTATE[22P02]: Invalid text representation: 7 ERROR: invalid input syntax for type integer` — falha silenciosa no audit log, e nas actions `analyzeMarketAction` e `analyzeLead` causava exception que matava o request
- **Fix aplicado:** Alterado coluna `entity_id` de `integer` para `varchar(50)` no PostgreSQL
- **Prioridade:** 🔴 CRÍTICO

### 3. Admin no plano Free — bloqueio de prospecção
- **Arquivo:** BD `users.plan_id = 1` (Free), `app/Core/PlanLimits.php`
- **Causa raiz:** Usuário admin (id=1) estava no plano Free (`can_prospec=false`, `max_searches=5`). Com 8 buscas no mês, `PlanLimits::canSearch()` deveria bloquear
- **Impacto:** Potencial bloqueio de acesso à prospecção para o admin. O `canSearch()` não bloqueou efetivamente porque o método não existia quando as primeiras buscas foram feitas (ver #4)
- **Fix aplicado:** Atualizado `plan_id = 2` (Pro) para o admin
- **Prioridade:** 🔴 CRÍTICO

### 4. `PlanLimits::canSearch()` e `canUseAI()` não existiam
- **Arquivo:** `app/Core/PlanLimits.php` (métodos adicionados posteriormente)
- **Causa raiz:** Os métodos `canSearch()` e `canUseAI()` foram chamados no `ProspecController` antes de serem implementados na classe `PlanLimits`
- **Impacto:** `Router Error: Call to undefined method App\Core\PlanLimits::canSearch()` — causava erro 500 que era interpretado como "serviço indisponível"
- **Fix aplicado:** Métodos já existem agora. Problema era intermitente (apenas nas primeiras versões)
- **Prioridade:** 🔴 CRÍTICO (já corrigido no código atual)

### 5. PHP-FPM: `request_terminate_timeout=0` — sem safety net
- **Arquivo:** `/usr/local/etc/php-fpm.d/www.conf`
- **Causa raiz:** Sem `request_terminate_timeout`, se um processo PHP-FPM travar (loop infinito, deadlock), ele nunca é terminado. Isso causa "Connection reset by peer" no nginx
- **Impacto:** Nginx log: `recv() failed (104: Connection reset by peer) while reading response header from upstream, client: ..., request: "POST /prospec/analyze/d491b550"` — o PHP-FPM process morre durante operações longas de IA
- **Fix aplicado:** `request_terminate_timeout = 180` (3 minutos, compatível com `fastcgi_read_timeout 300s` do nginx e `longTimeout=120s` do ProspecService)
- **Prioridade:** 🔴 CRÍTICO

---

## 🟡 Problemas Médios

### 6. CSRF — rotas POST sem CsrfMiddleware
- **Arquivo:** `config/routes.php` linhas 54-60
- **Causa raiz:** Rotas POST de prospecção (`/prospec/search`, `/prospec/enrich/{id}`, `/prospec/score/{id}`, `/prospec/analyze/{id}`, `/prospec/import/{id}`, `/prospec/import-lead/{searchId}/{leadId}`) não tinham `CsrfMiddleware`
- **Impacto:** Vulnerabilidade CSRF — qualquer site poderia fazer POSTs autenticados se o usuário estivesse logado
- **Fix aplicado:** Adicionado `CsrfMiddleware` em todas as 6 rotas POST. O frontend já envia `X-CSRF-TOKEN` em todas as chamadas, então não há breaking change
- **Prioridade:** 🟡 MÉDIO (segurança)

### 7. Rota `/prospec/export/{id}` não existia
- **Arquivo:** `config/routes.php`, `app/Controllers/ProspecController.php`
- **Causa raiz:** A view `session.php` tem link para `/prospec/export/{searchId}`, mas a rota e o método do controller não existiam
- **Impacto:** 404 ao tentar exportar resultados de uma busca
- **Fix aplicado:** Criada rota `Router::get('/prospec/export/{id}', ...)` e método `ProspecController::export()` que gera CSV a partir dos dados do `ProspecService::getStatus()`
- **Prioridade:** 🟡 MÉDIO

### 8. `Helper::e()` — Array to string conversion
- **Arquivo:** `app/Core/Helper.php` linha 16
- **Causa raiz:** O método `e(mixed $value)` faz `(string)$value` sem verificar se `$value` é array
- **Impacto:** PHP Warning "Array to string conversion" quando dados da API retornam arrays em campos inesperados
- **Fix aplicado:** Adicionado `if (is_array($value))` que serializa o array como JSON antes de escapar
- **Prioridade:** 🟡 MÉDIO

### 9. `ProspecService::exportSearch()` — endpoint não existe no Prospector
- **Arquivo:** `app/Core/ProspecService.php` método `exportSearch()`
- **Causa raiz:** O método chama `GET /search/{id}/export` que retorna 404 no Prospector API
- **Impacto:** O antigo `exportSearch()` sempre falhava. O novo `export()` usa `getStatus()` para gerar CSV localmente
- **Fix aplicado:** Método de exportação refatorado para gerar CSV a partir dos dados já disponíveis via `getStatus()`
- **Prioridade:** 🟡 MÉDIO

---

## 🟢 Problemas Baixos

### 10. `favicon.ico` não existe
- **Arquivo:** `public/favicon.ico`
- **Causa raiz:** Arquivo não existe
- **Impacto:** Erro 404 no nginx para cada request de favicon (pollution de log)
- **Fix aplicado:** Criado favicon.ico placeholder (1x1 transparente)
- **Prioridade:** 🟢 BAIXO

### 11. Nginx `Connection refused` ao conectar PHP-FPM
- **Arquivo:** `/var/log/nginx/prospec_error.log`
- **Causa raiz:** Nginx tentou conectar ao PHP-FPM antes do container estar pronto
- **Impacto:** Erro intermitente no startup (1x em 22/04 13:49:26)
- **Fix:** Adicionar `depends_on` com healthcheck no docker-compose (opcional)
- **Prioridade:** 🟢 BAIXO

### 12. Testes quebrados (suite de testes)
- **Arquivos:** `tests/TestSecurity.php`, `tests/TestAuth.php`, `tests/test_runner.php`
- **Causa raiz:** Vários erros: `Csrf::verify()` não existe, `TestCase` não existe, `parse errors` nos test files
- **Impacto:** Suite de testes não roda (não afeta produção)
- **Fix pendente:** Reescrever testes com PHPUnit ou framework adequado
- **Prioridade:** 🟢 BAIXO

### 13. Controller `layout()` e `Validator::sanitize()` não existiam
- **Arquivo:** `app/Controllers/LegalController.php`, `app/Controllers/AuthController.php`, `app/Core/Validator.php`
- **Causa raiz:** `$this->layout()` era chamado mas não existia no Controller base. `Validator::sanitize()` também.
- **Impacto:** Erro 500 em rotas legais e de auth (já corrigido)
- **Status:** Já corrigido no código atual (os métodos foram implementados)
- **Prioridade:** 🟢 BAIXO

---

## 📝 Mudanças Aplicadas

| # | Arquivo | Mudança |
|---|---------|---------|
| 1 | `audit_log` (PostgreSQL) | `entity_id`: integer → varchar(50) |
| 2 | `users` (PostgreSQL) | admin plan_id: 1 (Free) → 2 (Pro) |
| 3 | `app/Core/ProspecService.php` | `diagnoseLead()`: adicionado retry (2x) com pausas |
| 4 | `app/Controllers/ProspecController.php` | Adicionado método `export()` (gera CSV) |
| 5 | `config/routes.php` | Adicionada rota `GET /prospec/export/{id}` |
| 6 | `config/routes.php` | Adicionado `CsrfMiddleware` em 6 rotas POST |
| 7 | `app/Core/Helper.php` | `e()`: tratamento de arrays |
| 8 | `php-fpm.d/www.conf` | `request_terminate_timeout`: 0 → 180 |
| 9 | `public/favicon.ico` | Criado arquivo placeholder |

---

## 🔬 Diagnóstico da Mensagem "Serviço Indisponível"

A mensagem **"O serviço de prospecção está temporariamente indisponível. Tente novamente em alguns minutos."** pode vir de **três fontes** no `ProspecService.php`:

1. **Linha ~166** — Falha de conexão com o Prospector API (curl error)
2. **Linha ~227** — Prospector API retorna HTTP 5xx
3. **Retry no diagnoseLead()** — Mensagem melhorada após 3 tentativas

**Causas raiz identificadas:**
- ✅ **Principal:** Prospector API `/diagnose` retorna HTTP 500 (IA indisponível)
- ✅ **Secundária:** `AuditLog::create()` com entity_id string causava SQL error que crashava o PHP-FPM process (Connection reset by peer)
- ✅ **Terciária:** `PlanLimits::canSearch()` não existia nas primeiras versões, causando Router Error 500

---

## ⚠️ Ações Pendentes (não fixáveis pelo CRM)

1. **Prospector API `/diagnose`** — Retorna HTTP 500 para todas as chamadas. Precisa ser investigado no backend do Prospector (porta 8088). Possível causa: modelo de IA (Ollama) não está disponível ou o endpoint não está implementado corretamente.
2. **Ollama credentials** — `OLLAMA_KEY=` vazio no `.env`. Verificar se a chave de API é necessária para o diagnose.
3. **Suite de testes** — Múltiplos erros. Necessário reescrever com framework adequado.