# 🔍 Meta-Framework Gap Analysis — ProspecCRM

> **Gerado em:** 23/04/2026  
> **Projeto:** ProspecCRM (PHP 8.2+ MVC Manual, PostgreSQL 16, Redis 7)  
> **Referência:** Meta-Framework Universal v1.0 (71 módulos, 101 arquivos)

---

## 1. Resumo Executivo

### Score Geral de Conformidade: **22%**

O ProspecCRM implementou com sucesso o core funcional de um CRM de prospecção B2B — autenticação, CRUD de leads/companies, pipeline Kanban, prospecção via APIs externas, templates, agenda e relatórios básicos. No entanto, está **significativamente distante** do padrão Meta-Framework em dimensões críticas: segurança enterprise, observabilidade, testes, API REST versionada, disaster recovery, pagamentos e governança de dados.

| Dimensão | Score | Status |
|----------|-------|--------|
| **Core** (Regras, Modelagem, Arquitetura) | 35% | ⚠️ Parcial |
| **Backend** (API, Segurança, Testes, Erros) | 12% | ❌ Crítico |
| **Infra** (Deploy, DR, Migrations, SLO) | 15% | ❌ Crítico |
| **Business** (Pagamentos, CRM, Métricas) | 28% | ⚠️ Parcial |
| **AI** (Gestão APIs, Segurança IA) | 20% | ❌ Crítico |
| **Ops** (Observabilidade, Incidentes, Performance) | 5% | ❌ Ausente |
| **Advanced** (Multi-tenant, Feature Flags, DLQ, Notificações) | 8% | ❌ Ausente |
| **Shared** (LGPD, Threat Model, ADRs, Anti-patterns) | 15% | ❌ Crítico |
| **Frontend** (Design, UX, Onboarding, SEO) | 25% | ⚠️ Parcial |

---

## 2. Análise por Módulo

### 🏗️ CORE

#### CORE-01 — Regras de Negócio
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Regras centralizadas | ⚠️ Parcial | Não | Regras espalhadas nos Controllers (LeadController, CompanyController) em vez de Service dedicado |
| RBAC granular | ❌ Ausente | — | Apenas 3 roles hardcoded (admin/manager/seller), sem permissões granulares por recurso |
| Validação de regras | ⚠️ Parcial | Parcial | PipelineRules existe mas só valida movimentação no pipeline, não regras de negócio gerais |
| PlanLimits | ✅ Implementado | Parcial | PlanLimits.php existe mas não tem tabela `plans` no DB, limites hardcoded |
| Workspace/Org isolation | ❌ Ausente | — | Sem conceito de organização/workspace, dados não isolados por tenant |

**Gaps específicos:**
- Sem `BusinessRulesService` centralizado — regras de anti-duplicação, limites e validações estão inline nos controllers
- Sem permissões granulares (ex: `leads.delete`, `reports.export`) — apenas role check (`Auth::isAdmin()`)
- PlanLimits não tem persistência — limites são definidos em código, não no banco
- Sem sistema de workspace/organização (CORE-31)

**Prioridade:** P0 | **Effort:** L (40h)

---

#### CORE-02 — Modelagem de Dados
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Schema PostgreSQL | ✅ Implementado | Sim | 10 migrations SQL bem estruturadas com índices e FKs |
| JSONB para dados flexíveis | ✅ Implementado | Sim | companies.socios, companies.site_emails, templates.variables, etc. |
| Índices adequados | ✅ Implementado | Sim | Índices compostos (niche+city), índices em FKs e status |
| Relacionamentos | ✅ Implementado | Sim | FKs com CASCADE/RESTRICT/SET NULL corretos |
| ORM-like pattern (Prisma) | ❌ Ausente | N/A | Meta-Framework sugere Prisma; em PHP puro, PDO é aceitável |
| Soft delete | ⚠️ Parcial | Não | Leads usam `status='archived'` mas companies usam `archived` field separado; users usam `active` toggle |
| UUIDs como PK | ❌ Ausente | Não | Todas as PKs são SERIAL (auto-increment) — Meta-Framework recomenda UUID |
| tenant_id em todas as tabelas | ❌ Ausente | — | Sem isolamento multi-tenant |
| Audit columns padronizadas | ⚠️ Parcial | Parcial | created_at/updated_at na maioria das tabelas, mas não em todas (pipeline_stages, templates sem updated_at) |

**Gaps específicos:**
- Migration `009_create_audit_log` tem `action VARCHAR(100)` em vez de enum/CONSTRAINT — sem validação no DB
- Campo `score` em companies e leads é `INTEGER DEFAULT 0` mas sem constraint CHECK (0-100)
- `email_verified_at` referenciado no users model fillable mas não existe na migration 001
- `plan_id` no User model fillable mas sem tabela `plans` e sem FK na migration
- Sem coluna `deleted_at` padronizada — soft delete inconsistente

**Prioridade:** P1 | **Effort:** M (24h)

---

#### CORE-03 — Arquitetura
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| MVC manual | ✅ Implementado | Sim | Core/Controller, Core/Model, Views PHP nativas |
| Router | ✅ Implementado | Parcial | Router funcional mas sem grupos, middleware pipeline, ou named routes |
| Middleware | ⚠️ Parcial | Parcial | 4 middlewares (Auth, Admin, CSRF, RateLimit) mas sem pipeline encadeado |
| Request/Response objects | ⚠️ Parcial | Não | Request é array access bruto (`$this->request`), Response mistura redirect/json/abort |
| Service Layer | ❌ Ausente | — | Sem Services (ScoringService, EnrichmentService, etc.) — lógica nos Controllers |
| Dependency Injection | ❌ Ausente | — | Sem container DI — Models usam `static::` e PDO global |
| Event System | ❌ Ausente | — | Sem eventos desacoplados (ex: `LeadMoved`, `CompanyEnriched`) |

**Gaps específicos:**
- Router não suporta grupos (`$router->group(['prefix' => '/api/v1', 'middleware' => 'auth'])`) como o PLAN.md descreveu
- Middleware executa inline no Router, sem pipeline formal — não é possível composição
- ProspecController tem 350+ linhas misturando orquestração de APIs, regras de negócio e resposta HTTP
- Sem Service para lógica de scoring — `Company::topByScore()` no Model em vez de `ScoringService`
- Auth::check() faz query ao DB a cada request (sem cache de sessão no Redis)

**Prioridade:** P0 | **Effort:** XL (80h)

---

#### CORE-07 — Fluxos de Usuário
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Cadastro com verificação email | ❌ Ausente | — | Registro cria conta ativa direto, sem verificação de email |
| Login com rate limit | ✅ Implementado | Sim | 5 tentativas / 15 min, lockout 30 min via Redis |
| Login com JWT/Token | ❌ Ausente | Não | Sessões PHP nativas, sem JWT access+refresh tokens |
| Reset de senha | ⚠️ Parcial | Não | View `forgot-password.php` e `reset-password.php` existem mas fluxo não visível no AuthController |
| Deleção de conta (LGPD) | ⚠️ Parcial | Parcial | AccountController.delete() delega para AuthController.deleteAccount(), mas sem grace period de 30 dias |
| Exportação de dados | ⚠️ Parcial | Parcial | AccountController.export() delega para AuthController.exportAccount() — formato não confirmado |
| Fluxo de erro padronizado | ❌ Ausente | — | Cada controller trata erros de forma diferente |

**Gaps específicos:**
- Sem verificação de email no registro — CORE-07 requer email verification flow
- Sem refresh token — sessão PHP expira, sem renovação automática
- Reset de senha: views existem mas não há confirmação de implementação completa do token
- Deleção de conta: sem soft delete + grace period de 30 dias como CORE-07 e SHRD-61 exigem
- Sem limite de 3 cadastros por IP/dia como CORE-07 recomenda

**Prioridade:** P0 | **Effort:** M (24h)

---

#### CORE-31 — Workspaces e Teams
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Hierarquia Org > Workspace > Team | ❌ Ausente | — | Apenas roles flat (admin/manager/seller) |
| RBAC granular | ❌ Ausente | — | Sem permissões por recurso/ação |
| Sistema de convites | ❌ Ausente | — | Admin cria usuários manualmente em Settings/team |
| Switch workspace | ❌ Ausente | — | Sem conceito de workspace |
| Billing compartilhado | ❌ Ausente | — | Sem billing |

**Prioridade:** P1 | **Effort:** XL (80h)

---

#### CORE-34 — Arquitetura Estratégica
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Estratégia modular | ⚠️ Parcial | Não | Código monolítico sem separação em módulos/domínios |
| Bounded contexts | ❌ Ausente | — | Sem separação de contextos (Auth, CRM, Billing, Prospecção) |

**Prioridade:** P2 | **Effort:** L (40h)

---

#### CORE-47 — Resiliência Web3
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Resiliência/anti-fragilidade | ❌ Ausente | N/A | Módulo focado em Web3/descentralização — não aplicável ao CRM B2B |

**Prioridade:** N/A | **Effort:** N/A

---

#### CORE-52 — Analytics & Experiments
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| A/B testing | ❌ Ausente | — | Sem framework de experimentos |
| Analytics de uso | ❌ Ausente | — | Sem tracking de uso de features (quais rotas são mais usadas, etc.) |
| Métricas de produto | ❌ Ausente | — | Sem funil de ativação, retenção, etc. |

**Prioridade:** P2 | **Effort:** M (24h)

---

### 🔧 BACKEND

#### BACK-04 — API REST
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| API REST versionada | ❌ Ausente | — | Sem `/api/v1/` — rotas são `/leads`, `/companies`, etc. |
| JSON responses padronizadas | ⚠️ Parcial | Não | Alguns endpoints retornam JSON (ProspecController), mas sem envelope `{success, data, meta}` |
| Paginação padronizada | ❌ Ausente | — | Sem paginação na listagem de leads/companies |
| Filtros e sorting | ⚠️ Parcial | Não | LeadController.index() tem filtros básicos via query params mas não padronizados |
| HATEOAS / links | ❌ Ausente | — | Sem links de navegação nas responses |

**Gaps específicos:**
- Nenhuma rota `/api/v1/*` existe — todas as rotas são server-rendered HTML
- ProspecController retorna JSON para AJAX mas formato inconsistente (sem `success`/`data`/`meta`)
- Sem paginação — `Lead::countActive()` conta tudo mas listagem não tem LIMIT/OFFSET
- API de exportação (BIZ-56) não existe — sem `/api/v1/leads/export`

**Prioridade:** P0 | **Effort:** L (40h)

---

#### BACK-05 — Segurança
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Autenticação session-based | ✅ Implementado | Não | Meta-Framework requer JWT RS256; CRM usa PHP sessions |
| bcrypt password hashing | ✅ Implementado | Sim | `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` |
| CSRF protection | ✅ Implementado | Sim | Csrf.php + CsrfMiddleware — gera e valida tokens |
| Rate limiting | ✅ Implementado | Parcial | RateLimit.php via Redis, mas apenas por IP — sem rate limiting por usuário/rota |
| HTTPS enforcement | ❌ Ausente | — | Sem middleware de redirect HTTP→HTTPS |
| Security headers | ❌ Ausente | — | Sem headers X-Frame-Options, X-Content-Type-Options, CSP, HSTS |
| Input sanitization | ⚠️ Parcial | Parcial | Validator::sanitize() existe mas não aplicado consistentemente em todos os inputs |
| SQL injection prevention | ✅ Implementado | Sim | Prepared statements em todo o Model base |
| XSS prevention | ⚠️ Parcial | Não | Sem htmlspecialchars automático nas views — depende do dev lembrar |
| CORS configuration | ❌ Ausente | — | Sem configuração CORS para API |
| Password policy | ⚠️ Parcial | Não | Validator rejeita `min:8` mas sem exigir maiúscula, número, símbolo |
| Account lockout | ⚠️ Parcial | Parcial | RateLimit no login existe mas sem notificação ao usuário |
| Session fixation prevention | ⚠️ Parcial | Parcial | Session::regenerate() chamado no login mas sem no privilege escalation |
| Audit logging | ✅ Implementado | Parcial | AuditLog model existe mas não loga todas as ações (só templates, login) |

**Gaps específicos:**
- Sem JWT — Meta-Framework requer access_token (15min) + refresh_token (7d)
- Sem headers de segurança (CSP, HSTS, X-Frame-Options, X-Content-Type-Options)
- Company::findByName() usa LIKE com concatenação direta: `"%{$name}%"` — potencial SQL injection se não sanitizado
- Rate limiting só por IP — sem rate limiting por usuário autenticado (ex: 10 buscas/hora/user)
- Sem CORS para API — quando API REST for implementada, vai precisar
- Audit log não cobre: criação/edição de leads, edição de companies, alterações de role
- Sem 2FA / MFA

**Prioridade:** P0 | **Effort:** L (48h)

---

#### BACK-11 — Testes
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Testes unitários | ❌ Ausente | — | Zero testes no código — diretório `tests/` não existe |
| Testes de integração | ❌ Ausente | — | Nenhum teste de controller/model |
| Testes E2E | ❌ Ausente | — | Nenhum teste end-to-end |
| Cobertura de testes | ❌ Ausente | — | 0% — Meta-Framework requer 70% |
| CI/CD pipeline | ❌ Ausente | — | Sem GitHub Actions, sem pipeline de testes |
| Test pyramid | ❌ Ausente | — | Sem estratégia de testes |

**Gaps específicos:**
- **Zero testes** — nenhum arquivo de teste existe no projeto
- Sem PHPUnit configurado
- Sem testes para: AuthController, LeadController, PipelineRules, PlanLimits, Validator, Model::create/update/delete
- Sem testes de segurança (CSRF bypass, SQL injection, XSS)
- Sem testes de integração com PostgreSQL e Redis
- Meta-Framework requer 70% cobertura, cyclomatic complexity ≤10, Security Grade A

**Prioridade:** P0 | **Effort:** XL (80h)

---

#### BACK-15 — OpenAPI / Swagger
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Especificação OpenAPI 3.1.0 | ❌ Ausente | — | Nenhum arquivo openapi.yaml/json |
| Swagger UI | ❌ Ausente | — | Sem documentação interativa |
| Contract-first | ❌ Ausente | — | Rotas definidas em routes.php, sem spec |
| SDK generation | ❌ Ausente | — | Sem SDK |

**Prioridade:** P1 | **Effort:** M (24h)

---

#### BACK-24 — API Versioning
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Versionamento na URL | ❌ Ausente | — | Sem `/v1/` nas rotas |
| Headers de deprecation | ❌ Ausente | — | Sem Sunset/Deprecation headers |
| Ciclo de vida de versão | ❌ Ausente | — | Sem política de versionamento |
| Migration guide | ❌ Ausente | — | Sem documentação de migração entre versões |

**Prioridade:** P1 | **Effort:** M (16h)

---

#### BACK-25 — Catálogo de Erros
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Códigos de erro padronizados | ❌ Ausente | — | Erros usam strings genéricas ("Erro ao criar estágio") sem código |
| Formato `{success, error{code, message, details}}` | ❌ Ausente | — | Controllers usam Flash::error() para HTML e json_encode ad hoc para AJAX |
| Error codes por domínio | ❌ Ausente | — | Sem AUTH_*, PERM_*, BIZ_*, etc. |
| Mapeamento HTTP status → error code | ❌ Ausente | — | Response::abort() usa status genéricos sem código |

**Gaps específicos:**
- Erros nos controllers são strings em português sem código (ex: "Nome do estágio é obrigatório" em vez de `VALIDATION_REQUIRED_field`)
- Respostas AJAX não seguem formato `{success: false, error: {code: "AUTH_INVALID_CREDENTIALS", message: "..."}}`
- Sem correlação entre erros do backend e mensagens do frontend
- AuditReport já aponta: Prospector API retorna 500 sem erro estruturado

**Prioridade:** P0 | **Effort:** M (24h)

---

#### BACK-37 — Quality Gates
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Cobertura de testes ≥ 70% | ❌ Ausente | — | 0% |
| Complexidade ciclomática ≤ 10 | ❌ Ausente | — | Sem análise — ProspecController provavelmente > 15 |
| Security Grade A | ❌ Ausente | — | Sem scan de segurança |
| P95 latency < 200ms | ❌ Ausente | — | Sem medição |
| Self-auditing | ❌ Ausente | — | Sem check automatizado |
| Self-documenting | ❌ Ausente | — | Sem doc automática |

**Prioridade:** P1 | **Effort:** L (40h)

---

#### BACK-48 — Ponte Legado
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Migração JSON → PostgreSQL | ⚠️ Parcial | — | PLAN.md descreve `scripts/migrate_json.php` mas não está implementado |
| Compatibilidade com Prospector | ⚠️ Parcial | — | ProspecService integra com APIs mas não migra dados do JSON antigo |

**Prioridade:** P1 | **Effort:** M (24h)

---

### 🏢 BUSINESS

#### BIZ-08 — Pagamentos
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Gateway de pagamento | ❌ Ausente | — | Sem integração com Mercado Pago ou Stripe |
| Assinaturas | ❌ Ausente | — | Sem tabela `subscriptions`, sem planos pagos |
| Webhooks de pagamento | ❌ Ausente | — | Sem endpoint para receber webhooks |
| PIX | ❌ Ausente | — | Sem geração de QR Code PIX |
| Idempotência | ❌ Ausente | — | Sem idempotency_key em transações |
| Tabela transactions | ❌ Ausente | — | Sem model/tabela para transações |
| Planos (Free/Pro/Enterprise) | ⚠️ Parcial | Não | PlanLimits existe mas sem persistência em plans/subscriptions |

**Gaps específicos:**
- Sem tabela `plans` no banco — PlanLimits tem limites hardcoded
- Sem tabela `subscriptions` — sem tracking de assinatura
- Sem tabela `transactions` — sem histórico de pagamentos
- Sem gateway de pagamento (Mercado Pago ou Stripe)
- Sem webhook endpoint para receber notificações de pagamento
- `plan_id` no User fillable mas sem FK para tabela plans

**Prioridade:** P0 | **Effort:** XL (80h)

---

#### BIZ-56 — Sales CRM
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Funil de vendas | ⚠️ Parcial | Parcial | Pipeline stages: Novo→Contatado→Respondendo→Reunião→Proposta→Fechado→Perdido — diferente do MQL→SQL do framework |
| Lead scoring | ⚠️ Parcial | Parcial | Score 0-100 em companies/leads mas cálculo é hardcoded, sem modelo configurável |
| CRM pipeline (Kanban) | ✅ Implementado | Sim | PipelineController com drag-and-drop via Alpine.js |
| Automation sequences | ❌ Ausente | — | Sem sequências automáticas de follow-up |
| Sales collateral (templates) | ✅ Implementado | Sim | TemplateController com variáveis e múltiplos canais |
| Lead→MQL→SQL→Oportunidade | ❌ Ausente | — | Sem qualificação formal MQL/SQL — apenas status active/won/lost |
| Conversão por etapa | ⚠️ Parcial | Parcial | ReportController mostra por stage mas sem taxa de conversão entre etapas |

**Gaps específicos:**
- Funil do CRM: Novo→Contatado→Respondendo→Reunião→Proposta→Fechado vs BIZ-56: Lead→MQL→SQL→Oportunidade→Cliente→Churn
- Sem conceito de MQL (Marketing Qualified Lead) — todos os leads são "active"
- Sem scoring model configurável — pesos são fixos no código
- Sem automação de follow-up — criar tarefa é manual
- Sem sequências de email automáticas (drip campaigns)
- Sem motivo de perda (lost reason) — leads marcados como "lost" sem categorização

**Prioridade:** P1 | **Effort:** L (40h)

---

#### BIZ-57 — Metrics & KPIs
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| KPIs no dashboard | ✅ Implementado | Parcial | TotalLeads, NewThisWeek, PipelineValue, ConversionRate, PendingTasks |
| Funil de conversão | ⚠️ Parcial | Parcial | ReportController mostra por stage mas sem taxa entre etapas |
| Ranking vendedores | ⚠️ Parcial | Parcial | Existe mas só para manager/admin |
| Métricas de produto | ❌ Ausente | — | Sem DAU, retenção, ativação |

**Prioridade:** P2 | **Effort:** M (16h)

---

#### BIZ-28 — Email Templates
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Templates de mensagem | ✅ Implementado | Sim | TemplateController com variáveis, múltiplos canais |
| Envio de email | ❌ Ausente | — | Sem EmailService/PHPMailer configurado |
| Drip campaigns | ❌ Ausente | — | Sem sequências automatizadas |

**Prioridade:** P1 | **Effort:** M (24h)

---

#### BIZ-16, BIZ-39, BIZ-43, BIZ-51, BIZ-52, BIZ-53, BIZ-54, BIZ-55 — Business Strategy
| Módulo | Status | Notas |
|--------|--------|-------|
| BIZ-16 Custo Real | ❌ Ausente | Sem tracking de custo por lead/prospecção |
| BIZ-39 Prod | ❌ Ausente | Sem product management |
| BIZ-43 Simulação | ❌ Ausente | Sem simulador financeiro |
| BIZ-51 Cost | ❌ Ausente | Sem cost management |
| BIZ-52 Brand | ❌ Ausente | N/A para CRM interno |
| BIZ-53 Growth Engine | ❌ Ausente | Sem growth loops |
| BIZ-54 Market Research | ⚠️ Parcial | Prospecção faz pesquisa de mercado via IA mas sem framework formal |
| BIZ-55 Pricing Psychology | ❌ Ausente | N/A para CRM interno |

**Prioridade:** P2 (maioria N/A) | **Effort:** S-M por módulo

---

### 🤖 AI

#### AI-09 — Gerenciamento de APIs (Provider Gateway)
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Gateway para APIs externas | ⚠️ Parcial | Não | ProspecService chama Serper/BrasilAPI/Ollama mas sem gateway formal |
| Health checking | ❌ Ausente | — | Sem health check dos providers (Serper up? Ollama up?) |
| Cost calculation | ❌ Ausente | — | Sem tracking de custo por chamada de API |
| Caching de respostas | ❌ Ausente | — | Sem cache Redis para respostas de APIs |
| Retry + circuit breaker | ❌ Ausente | — | Sem retry automático nem circuit breaker |
| Provider logs | ❌ Ausente | — | Sem log de chamadas/sucesso/falha por provider |
| Fallback automático | ❌ Ausente | — | Sem fallback entre providers |

**Gaps específicos:**
- ProspecService::search() chama Serper diretamente — sem retry se falhar
- ProspecService::analyzeWithAI() chama Ollama — sem fallback para modelo secundário
- Sem cache — mesma busca no mesmo nicho/cidade gera chamadas duplicadas
- Sem circuit breaker — se Serper cai, prospecção falha sem recuperação
- Sem rate limiting por provider (só rate limit geral por IP)

**Prioridade:** P0 | **Effort:** L (40h)

---

#### AI-10 — Segurança IA
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Prompt injection prevention | ❌ Ausente | — | Sem sanitização de input para IA |
| Output filtering | ❌ Ausente | — | Sem validação de resposta da IA |
| Content moderation | ❌ Ausente | — | Sem filter de conteúdo inadequado |

**Prioridade:** P1 | **Effort:** M (16h)

---

#### AI-12 — Streaming
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| SSE para respostas de IA | ❌ Ausente | — | Sem streaming — análise IA é síncrona |
| WebSocket | ❌ Ausente | — | Sem WebSocket |

**Prioridade:** P2 | **Effort:** M (24h)

---

#### AI-32 — RAG & Memory
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| RAG para contexto | ❌ Ausente | — | Sem retrieval-augmented generation |
| Memória de conversa | ❌ Ausente | — | Sem contexto persistente de interações com IA |

**Prioridade:** P2 | **Effort:** L (40h)

---

#### AI-38 — Orquestração de Agentes
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Orquestração multi-agente | ❌ Ausente | N/A | CRM não precisa de multi-agente agora |

**Prioridade:** N/A | **Effort:** N/A

---

#### AI-58/59 — Agent Capabilities / Execution Control
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Agent capabilities | ❌ Ausente | N/A | Sem sistema de agentes |
| Execution control | ❌ Ausente | N/A | Sem controle de execução de agentes |

**Prioridade:** N/A | **Effort:** N/A

---

### 🖥️ INFRA

#### INFRA-18 — Migrations Strategy
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Migrations SQL versionadas | ✅ Implementado | Parcial | 10 migrations SQL numeradas (001-010) mas sem migration runner |
| Migration runner | ❌ Ausente | — | Sem `migrate.php` — migrations aplicadas manualmente |
| Rollback | ❌ Ausente | — | Sem `down()` nas migrations — apenas CREATE, sem DROP |
| Seed data | ⚠️ Parcial | — | Pipeline stages tem seed na migration 003 mas sem admin user seed |
| CI/CD para migrations | ❌ Ausente | — | Sem pipeline |

**Gaps específicos:**
- Sem migration runner — migrations são aplicadas manualmente via `psql`
- Sem rollback — se migration falhar em produção, não há como reverter
- Sem migration de dados (backfill) — sem script para popular tenant_id ou outros campos
- Sem seed de admin user — primeiro admin deve ser criado manualmente
- Meta-Framework requer Prisma Migrate — em PHP, um runner simples é aceitável

**Prioridade:** P1 | **Effort:** M (16h)

---

#### INFRA-19 — Deploy & Infra
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Docker Compose | ✅ Implementado | Parcial | Tem docker-compose com PHP-FPM, Nginx, PostgreSQL, Redis |
| Cloudflare CDN | ❌ Ausente | — | Sem Cloudflare na frente |
| Estrutura VPS padronizada | ❌ Ausente | — | Sem documentação de deploy na VPS |
| Health check endpoint | ❌ Ausente | — | Sem `/health` |
| HTTPS | ⚠️ Parcial | — | Docker expõe porta 80; HTTPS depende do proxy/host |
| Container registry | ❌ Ausente | — | Sem push para registry |

**Gaps específicos:**
- Sem endpoint `/health` — INFRA-19 requer health check com verificação de DB e Redis
- Sem Cloudflare CDN na frente do VPS
- Docker Compose não tem limites de recursos (memory, CPU) nos containers
- Sem restart policy nos containers
- Sem rede isolada entre serviços
- Sem volumes nomeados para persistência de dados (apenas bind mounts implícitos)

**Prioridade:** P1 | **Effort:** M (24h)

---

#### INFRA-20 — SLO / SLA
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| SLIs definidos | ❌ Ausente | — | Sem indicadores de nível de serviço |
| SLOs definidos | ❌ Ausente | — | Sem objetivos de nível (99.9% uptime, etc.) |
| Error budget | ❌ Ausente | — | Sem orçamento de erro |
| SLA por plano | ❌ Ausente | — | Sem SLA definido |
| Dashboard de SLO | ❌ Ausente | — | Sem Prometheus/Grafana |

**Prioridade:** P2 | **Effort:** M (24h)

---

#### INFRA-21 — Disaster Recovery
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| RTO/RPO definidos | ❌ Ausente | — | Sem targets de Recovery Time/Point Objective |
| Backup PostgreSQL | ❌ Ausente | — | Sem pg_dump automatizado |
| WAL archiving | ❌ Ausente | — | Sem point-in-time recovery |
| Backup para S3 | ❌ Ausente | — | Sem upload de backup para storage externo |
| Redis backup | ❌ Ausente | — | Redis é cache, mas sem persistência AOF/RDB |
| Runbook de restore | ❌ Ausente | — | Sem documentação de procedimento de restore |
| Teste de restore | ❌ Ausente | — | Nunca testado |

**Gaps específicos:**
- **Zero backup** — se o banco cair, dados são perdidos
- Sem pg_dump agendado (cron)
- Sem backup em S3 ou storage externo
- Sem WAL archiving para point-in-time recovery
- Sem runbook documentado de como restaurar
- Redis sem persistência — se reiniciar, cache é perdido (aceitável para cache, mas rate limit counters são perdidos)

**Prioridade:** P0 | **Effort:** M (24h)

---

#### INFRA-36 — DevSecOps
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Security scanning | ❌ Ausente | — | Sem SAST/DAST |
| Dependency scanning | ❌ Ausente | — | Sem composer audit |
| Container scanning | ❌ Ausente | — | Sem scan de imagem Docker |
| Secret management | ❌ Ausente | — | Chaves de API em .env, sem vault |

**Prioridade:** P1 | **Effort:** M (24h)

---

#### INFRA-60 — Capacity Planning
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Métricas de capacidade | ❌ Ausente | — | Sem monitoramento de CPU, memória, disco |
| Alertas de capacidade | ❌ Ausente | — | Sem alertas de recurso baixo |

**Prioridade:** P2 | **Effort:** S (8h)

---

#### INFRA-61 — CI/CD Pipeline
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| CI pipeline | ❌ Ausente | — | Sem GitHub Actions ou similar |
| CD pipeline | ❌ Ausente | — | Deploy manual |
| Automated testing in CI | ❌ Ausente | — | Zero testes, zero CI |

**Prioridade:** P1 | **Effort:** M (24h)

---

### 📊 OPS

#### OPS-22 — Observabilidade
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Structured logging | ❌ Ausente | — | Apenas `error_log()` nativo do PHP — sem structured JSON logs |
| Log aggregation | ❌ Ausente | — | Sem Pino/Loki/ELK |
| Metrics | ❌ Ausente | — | Sem Prometheus metrics endpoint |
| Traces | ❌ Ausente | — | Sem OpenTelemetry/Jaeger |
| Dashboards | ❌ Ausente | — | Sem Grafana |
| Alertas | ❌ Ausente | — | Sem alertas baseados em métricas |
| Correlation IDs | ❌ Ausente | — | Sem request ID para rastrear requisições |

**Gaps específicos:**
- Todos os erros usam `error_log()` — logs vão para stderr do container, sem estrutura
- Sem nível de log (debug, info, warning, error)
- Sem contexto estruturado (user_id, request_id, timestamp ISO)
- Sem aggregation — impossível buscar logs de uma requisição específica
- Sem métricas de negócio (leads criados/min, buscas realizadas, etc.)
- `docker compose logs` é a única forma de ver logs

**Prioridade:** P0 | **Effort:** L (40h)

---

#### OPS-23 — Incident Response
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Severidades definidas | ❌ Ausente | — | Sem classificação SEV-1 a SEV-4 |
| Runbooks | ❌ Ausente | — | Sem procedimento documentado |
| Post-mortem template | ❌ Ausente | — | Sem template |
| Escalation | ❌ Ausente | — | Sem processo de escalação |
| PagerDuty/on-call | ❌ Ausente | — | Sem on-call |
| Status page | ❌ Ausente | — | Sem página de status |

**Prioridade:** P2 | **Effort:** M (16h)

---

#### OPS-29 — Performance Targets
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Targets por rota | ❌ Ausente | — | Sem metas de latência |
| Load testing | ❌ Ausente | — | Sem k6 ou similar |
| Connection pooling | ⚠️ Parcial | — | PDO com persistent connections mas sem PgBouncer |
| Gzip compression | ⚠️ Parcial | — | Depende do Nginx config, não confirmado |
| Database indexes | ✅ Implementado | Sim | Índices adequados nas migrations |

**Prioridade:** P2 | **Effort:** M (16h)

---

#### OPS-35 — FinOps
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Cost tracking | ❌ Ausente | — | Sem tracking de custo de APIs externas |
| Cost allocation | ❌ Ausente | — | Sem alocação por tenant/usuário |

**Prioridade:** P2 | **Effort:** S (8h)

---

#### OPS-42 — Auto-Cura
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Self-healing | ❌ Ausente | — | Sem circuit breaker, retry automático, fallback |

**Prioridade:** P2 | **Effort:** M (16h)

---

#### OPS-50 — Runbooks
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Runbooks documentados | ❌ Ausente | — | Sem documentação operacional |

**Prioridade:** P2 | **Effort:** S (8h)

---

### 🚀 ADVANCED

#### ADV-06 — Integrações
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Integrações com APIs externas | ⚠️ Parcial | Não | ProspecService chama APIs mas sem adapter pattern, sem health check |
| Webhook receiver | ❌ Ausente | — | Sem endpoint para receber webhooks (pagamentos, etc.) |
| Rate limiting por integração | ❌ Ausente | — | Sem rate limit específico por API externa |

**Prioridade:** P1 | **Effort:** M (24h)

---

#### ADV-13 — Feature Flags
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Tabela feature_flags | ❌ Ausente | — | Sem feature flags |
| FeatureFlagService | ❌ Ausente | — | Sem serviço de flags |
| Kill switch | ❌ Ausente | — | Sem maintenance mode via flag |
| Canary release | ❌ Ausente | — | Sem rollout gradual |

**Prioridade:** P1 | **Effort:** M (24h)

---

#### ADV-14 — Dead Letter Queue
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Tabela dead_letter_queue | ❌ Ausente | — | Sem DLQ |
| Retry com backoff | ❌ Ausente | — | Sem retry automático para falhas |
| Admin dashboard | ❌ Ausente | — | Sem interface de DLQ |

**Prioridade:** P1 | **Effort:** M (24h)

---

#### ADV-17 — Multi-Tenant
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Tabela tenants | ❌ Ausente | — | Sem multi-tenancy |
| tenant_id nas tabelas | ❌ Ausente | — | Sem isolamento |
| RLS (Row Level Security) | ❌ Ausente | — | Sem RLS no PostgreSQL |
| Middleware de tenant | ❌ Ausente | — | Sem extração de tenant do request |

**Prioridade:** P1 | **Effort:** XL (80h)

---

#### ADV-27 — Notificações Real-Time
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| WebSocket | ❌ Ausente | — | Sem WebSocket |
| Notificações push | ❌ Ausente | — | Sem sistema de notificação |
| Redis Pub/Sub | ❌ Ausente | — | Sem pub/sub para eventos |

**Prioridade:** P2 | **Effort:** L (40h)

---

### 🔗 SHARED

#### SHRD-56 — Threat Model
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| STRIDE analysis | ❌ Ausente | — | Sem threat model documentado |
| Data flow diagram | ❌ Ausente | — | Sem DFD |
| Attack surface analysis | ❌ Ausente | — | Sem análise de superfície de ataque |
| Mitigation register | ❌ Ausente | — | Sem registro de mitigações |

**Prioridade:** P1 | **Effort:** M (24h)

---

#### SHRD-57 — Data Flow
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Diagramas de fluxo de dados | ❌ Ausente | — | Sem DFD documentado |

**Prioridade:** P2 | **Effort:** S (8h)

---

#### SHRD-61 — LGPD Compliance
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Inventario de dados pessoais | ❌ Ausente | — | Sem inventário |
| Direito de acesso | ⚠️ Parcial | — | AccountController.export() existe mas sem formato JSON padronizado |
| Direito de apagamento | ⚠️ Parcial | Não | AccountController.delete() sem grace period de 30 dias |
| Portabilidade | ⚠️ Parcial | — | Exportação CSV mas sem formato JSON |
| Consentimento registrado | ❌ Ausente | — | Sem registro de consentimento |
| Tabela lgpd_requests | ❌ Ausente | — | Sem tracking de solicitações LGPD |
| DPO nomeado | ❌ Ausente | — | Sem DPO |
| Data breach plan | ❌ Ausente | — | Sem plano de incidente de dados |
| Cookies banner | ❌ Ausente | — | Sem banner de consentimento de cookies |
| Criptografia em repouso | ❌ Ausente | — | Sem criptografia de dados sensíveis no DB |

**Gaps específicos:**
- Termos de uso e política de privacidade existem (LegalController) mas sem registro de aceite
- Exportação de dados: sem endpoint GET `/users/me/data` com formato JSON padronizado
- Deleção: sem grace period de 30 dias — dados são removidos imediatamente
- Sem criptografia de dados sensíveis no banco (emails, telefones em texto plano)
- Sem banner de cookies
- Sem DPO definido

**Prioridade:** P0 | **Effort:** M (24h)

---

#### SHRD-40 — Anti-Patterns
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Anti-patterns documentados | ❌ Ausente | — | Sem documento de anti-patterns |
| Code review checklist | ❌ Ausente | — | Sem checklist de review |

**Prioridade:** P2 | **Effort:** S (8h)

---

#### SHRD-41 — Framework Evolutivo
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Versionamento do framework | ❌ Ausente | — | Sem versionamento |
| Changelog | ❌ Ausente | — | Sem changelog |

**Prioridade:** P2 | **Effort:** S (4h)

---

#### SHRD-44 — Guardião Ético
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Ethical guidelines | ❌ Ausente | — | Sem diretrizes éticas para IA |

**Prioridade:** P2 | **Effort:** S (4h)

---

#### SHRD-45 — Engenharia de Contexto
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Context engineering | ❌ Ausente | N/A | Mais relevante para sistemas de IA agents |

**Prioridade:** N/A | **Effort:** N/A

---

#### SHRD-49 — ADRs
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Architecture Decision Records | ❌ Ausente | — | Nenhum ADR documentado |

**Prioridade:** P2 | **Effort:** S (8h)

---

#### SHRD-58 — Onboarding
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Developer onboarding guide | ❌ Ausente | — | Sem guia de onboarding (além do PLAN.md) |

**Prioridade:** P2 | **Effort:** S (8h)

---

#### SHRD-59 — Glossário
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Glossário de termos | ❌ Ausente | — | Sem glossário |

**Prioridade:** P2 | **Effort:** S (4h)

---

#### SHRD-62 — Accessibility & Inclusion
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| WCAG compliance | ❌ Ausente | — | Sem testes de acessibilidade |
| ARIA labels | ❌ Ausente | — | Sem atributos ARIA nas views |

**Prioridade:** P2 | **Effort:** M (16h)

---

#### SHRD-63 — Compliance Global
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Compliance global | ❌ Ausente | — | Sem compliance multi-região |

**Prioridade:** N/A | **Effort:** N/A

---

#### SHRD-33 / SHRD-40 — Padrões Compartilhados
| Módulo | Status | Notas |
|--------|--------|-------|
| SHRD-33 | ❌ Ausente | Sem padrão compartilhado definido |

**Prioridade:** P2 | **Effort:** S (8h)

---

### 🎨 FRONTEND

#### FRONT-30 — Frontend Design
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Design system | ⚠️ Parcial | Não | Tailwind CSS via CDN mas sem design system formal |
| Component library | ⚠️ Parcial | — | Parciais em Views/partials mas sem documentação |
| Responsividade | ⚠️ Parcial | — | Tailwind é responsivo mas views não testadas em mobile |

**Prioridade:** P2 | **Effort:** M (16h)

---

#### FRONT-26 — Upload Pipeline
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Upload de arquivos | ❌ Ausente | — | Sem upload (importação CSV descrita no PLAN mas não implementada) |
| Validação de arquivo | ❌ Ausente | — | Sem validação |

**Prioridade:** P1 | **Effort:** S (8h)

---

#### FRONT-46 — Design Emocional
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Micro-interações | ❌ Ausente | — | Sem animações ou feedback visual refinado |
| Estados de empty state | ❌ Ausente | — | Sem tratamento visual de listas vazias |

**Prioridade:** P2 | **Effort:** S (8h)

---

#### FRONT-52 — UX Research
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| User research | ❌ Ausente | — | Sem pesquisa de UX |

**Prioridade:** P2 | **Effort:** N/A

---

#### FRONT-53 — Copywriting
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Copywriting UX | ⚠️ Parcial | — | Flash messages e labels em português mas sem guia de voz |

**Prioridade:** P2 | **Effort:** S (4h)

---

#### FRONT-54 — SEO & Content
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Meta tags | ❌ Ausente | — | Sem meta tags para SEO |
| Sitemap | ❌ Ausente | — | N/A para app autenticada |

**Prioridade:** N/A | **Effort:** N/A

---

#### FRONT-55 — Onboarding & Ativação
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Onboarding flow | ❌ Ausente | — | Sem tour ou onboarding para novos usuários |
| Tooltips | ❌ Ausente | — | Sem tooltips contextuais |

**Prioridade:** P2 | **Effort:** M (16h)

---

#### FRONT-56 — Referral & Gamification
| Aspecto | Status | Padrão? | Detalhes |
|---------|--------|---------|---------|
| Sistema de referral | ❌ Ausente | — | Sem programa de indicação |
| Gamificação | ❌ Ausente | — | Sem elementos de gamificação |

**Prioridade:** P2 | **Effort:** M (24h)

---

## 3. Top 10 Gaps Críticos

| # | Gap | Módulo | Prioridade | Effort | Impacto |
|---|-----|--------|-----------|--------|---------|
| 1 | **Zero testes** — Sem PHPUnit, sem CI, sem cobertura | BACK-11 | P0 | XL (80h) | Sem testes = qualquer mudança pode quebrar tudo. Sem CI = deploy manual e arriscado. |
| 2 | **Sem observabilidade** — Apenas error_log(), sem structured logging, métricas ou traces | OPS-22 | P0 | L (40h) | Impossível diagnosticar problemas em produção. Sem métricas = voando cego. |
| 3 | **Sem disaster recovery** — Zero backup, sem pg_dump, sem S3, sem runbook de restore | INFRA-21 | P0 | M (24h) | Um crash do PostgreSQL = perda total de dados. Sem possibilidade de recuperação. |
| 4 | **Sem API REST versionada** — Rotas são server-rendered, sem `/api/v1/`, sem JSON padronizado | BACK-04 | P0 | L (40h) | Sem API = impossível integrar com outros sistemas. Sem versionamento = breaking changes sem controle. |
| 5 | **Segurança insuficiente** — Sem JWT, sem security headers, sem CORS, rate limiting parcial, LGPD incompleto | BACK-05, SHRD-61 | P0 | L (48h) | Sessões PHP sem JWT limitam API. Sem headers = XSS/CSRF risks. Sem LGPD = multa até R$50M. |
| 6 | **Sem catálogo de erros** — Erros são strings em português sem código, sem formato padronizado | BACK-25 | P0 | M (24h) | Debugging impossível. Frontend não pode tratar erros programaticamente. APIs externas não entendem erros. |
| 7 | **Sem gateway de pagamento** — Sem Mercado Pago/Stripe, sem subscriptions, sem transações | BIZ-08 | P0 | XL (80h) | Sem monetização. PlanLimits existe mas não gera receita. Sem billing = sem negócio. |
| 8 | **Provider Gateway ausente** — Sem health check, retry, circuit breaker ou cache para APIs externas | AI-09 | P0 | L (40h) | Se Serper/Ollama caem, prospecção falha sem recuperação. Sem cache = custo duplicado. |
| 9 | **Sem service layer** — Lógica de negócio misturada nos Controllers, sem separação de responsabilidades | CORE-03 | P0 | XL (80h) | Controllers com 300+ linhas. Sem reuso. Sem testabilidade. Sem composição. |
| 10 | **LGPD incompleto** — Sem grace period na deleção, sem registro de consentimento, sem criptografia em repouso | SHRD-61 | P0 | M (24h) | Risco legal. Multa até 2% faturamento (máx R$50M). Sem compliance = risco existencial. |

---

## 4. Roadmap Sugerido

### Fase 1 — Sobrevivência (P0, 4 semanas, ~120h)

**Objetivo:** Garantir que o sistema não morra e possa ser diagnosticado.

| Semana | Tarefa | Módulo | Effort |
|--------|--------|--------|--------|
| 1 | Implementar backup PostgreSQL (pg_dump cron + S3) | INFRA-21 | 8h |
| 1 | Criar endpoint `/health` (DB + Redis check) | INFRA-19 | 4h |
| 1 | Adicionar security headers no Nginx | BACK-05 | 4h |
| 2 | Implementar structured logging (JSON logs com nível, contexto, request_id) | OPS-22 | 16h |
| 2 | Criar catálogo de erros padronizado (`AppError` com codes AUTH_*, PERM_*, BIZ_*) | BACK-25 | 12h |
| 3 | Configurar PHPUnit + escrever testes críticos (Auth, Lead CRUD, PipelineRules) | BACK-11 | 16h |
| 3 | Implementar circuit breaker + retry para ProspecService | AI-09 | 12h |
| 4 | Implementar LGPD grace period + consent tracking | SHRD-61 | 12h |
| 4 | Criar API REST v1 (leads, companies) com JSON padronizado | BACK-04 | 20h |

**Resultado esperado:** Score de 22% → ~38%

---

### Fase 2 — Fundação (P1, 6 semanas, ~160h)

**Objetivo:** Construir a fundação enterprise (API, segurança, infraestrutura).

| Semana | Tarefa | Módulo | Effort |
|--------|--------|--------|--------|
| 5-6 | Implementar JWT auth (access + refresh tokens) + middleware | BACK-05, CORE-07 | 24h |
| 5-6 | Criar Service Layer (ScoringService, EnrichmentService, DeduplicationService) | CORE-03 | 24h |
| 7 | Implementar gateway de pagamento (Mercado Pago PIX) + subscriptions | BIZ-08 | 32h |
| 7 | Criar migration runner + rollback | INFRA-18 | 8h |
| 8 | Feature flags (tabela + service + middleware) | ADV-13 | 16h |
| 8 | Threat model (STRIDE) + data flow diagram | SHRD-56 | 16h |
| 9 | OpenAPI spec + Swagger UI | BACK-15 | 16h |
| 9 | API versioning (/v1/, deprecation headers) | BACK-24 | 8h |
| 10 | Dead Letter Queue (tabela + service) | ADV-14 | 16h |

**Resultado esperado:** Score ~38% → ~55%

---

### Fase 3 — Maturidade (P2, 8 semanas, ~160h)

**Objetivo:** Alcançar conformidade enterprise e escalabilidade.

| Semana | Tarefa | Módulo | Effort |
|--------|--------|--------|--------|
| 11-12 | Multi-tenant (tenant_id, middleware, RLS) | ADV-17, CORE-31 | 40h |
| 12-13 | Testes: 70% coverage + E2E + CI/CD | BACK-11, BACK-37, INFRA-61 | 32h |
| 14 | Observabilidade completa (Prometheus metrics, Grafana dashboards) | OPS-22, INFRA-20 | 24h |
| 14 | SLO/SLA targets + error budget | INFRA-20 | 8h |
| 15 | Incident response (runbooks, post-mortem template, escalation) | OPS-23 | 16h |
| 15 | WebSocket para notificações real-time | ADV-27 | 24h |
| 16 | Performance targets + load testing (k6) | OPS-29 | 16h |

**Resultado esperado:** Score ~55% → ~72%

---

### Fase 4 — Excelência (contínuo, ~80h)

**Objetivo:** Alcançar 85%+ de conformidade.

| Tarefa | Módulo | Effort |
|--------|--------|--------|
| AI security (prompt injection, output filtering) | AI-10 | 16h |
| RAG / Memory para IA | AI-32 | 24h |
| SSE streaming para IA | AI-12 | 16h |
| DevSecOps (SAST, DAST, composer audit, container scanning) | INFRA-36 | 16h |
| Accessibility (WCAG, ARIA) | SHRD-62 | 8h |

**Resultado esperado:** Score ~72% → ~85%

---

## 5. Scoreboard

| Módulo | Score | ✅ | ⚠️ | ❌ | N/A | Prioridade | Effort Total |
|--------|-------|-----|-----|-----|-----|-----------|-------------|
| **CORE-01** Regras de Negócio | 25% | 1 | 2 | 2 | 0 | P0 | 40h |
| **CORE-02** Modelagem de Dados | 65% | 4 | 2 | 2 | 1 | P1 | 24h |
| **CORE-03** Arquitetura | 35% | 1 | 3 | 3 | 0 | P0 | 80h |
| **CORE-07** Fluxos de Usuário | 30% | 1 | 3 | 3 | 0 | P0 | 24h |
| **CORE-31** Workspaces & Teams | 0% | 0 | 0 | 5 | 0 | P1 | 80h |
| **CORE-34** Arquitetura Estratégica | 15% | 0 | 1 | 1 | 0 | P2 | 40h |
| **CORE-52** Analytics & Experiments | 0% | 0 | 0 | 3 | 0 | P2 | 24h |
| **BACK-04** API REST | 10% | 0 | 2 | 3 | 0 | P0 | 40h |
| **BACK-05** Segurança | 40% | 4 | 4 | 7 | 0 | P0 | 48h |
| **BACK-11** Testes | 0% | 0 | 0 | 6 | 0 | P0 | 80h |
| **BACK-15** OpenAPI | 0% | 0 | 0 | 4 | 0 | P1 | 24h |
| **BACK-24** API Versioning | 0% | 0 | 0 | 4 | 0 | P1 | 16h |
| **BACK-25** Catálogo de Erros | 0% | 0 | 0 | 4 | 0 | P0 | 24h |
| **BACK-37** Quality Gates | 0% | 0 | 0 | 6 | 0 | P1 | 40h |
| **BACK-48** Ponte Legado | 20% | 0 | 2 | 0 | 0 | P1 | 24h |
| **BIZ-08** Pagamentos | 5% | 0 | 1 | 6 | 0 | P0 | 80h |
| **BIZ-28** Email Templates | 33% | 1 | 0 | 2 | 0 | P1 | 24h |
| **BIZ-56** Sales CRM | 50% | 2 | 3 | 3 | 0 | P1 | 40h |
| **BIZ-57** Metrics & KPIs | 40% | 1 | 2 | 1 | 0 | P2 | 16h |
| **AI-09** Provider Gateway | 15% | 0 | 1 | 6 | 0 | P0 | 40h |
| **AI-10** Segurança IA | 0% | 0 | 0 | 3 | 0 | P1 | 16h |
| **AI-12** Streaming | 0% | 0 | 0 | 2 | 0 | P2 | 24h |
| **AI-32** RAG & Memory | 0% | 0 | 0 | 2 | 0 | P2 | 40h |
| **INFRA-18** Migrations | 30% | 1 | 1 | 4 | 0 | P1 | 16h |
| **INFRA-19** Deploy & Infra | 25% | 1 | 1 | 4 | 0 | P1 | 24h |
| **INFRA-20** SLO / SLA | 0% | 0 | 0 | 5 | 0 | P2 | 24h |
| **INFRA-21** Disaster Recovery | 0% | 0 | 0 | 7 | 0 | P0 | 24h |
| **INFRA-36** DevSecOps | 0% | 0 | 0 | 4 | 0 | P1 | 24h |
| **INFRA-61** CI/CD Pipeline | 0% | 0 | 0 | 3 | 0 | P1 | 24h |
| **OPS-22** Observabilidade | 0% | 0 | 0 | 7 | 0 | P0 | 40h |
| **OPS-23** Incident Response | 0% | 0 | 0 | 6 | 0 | P2 | 16h |
| **OPS-29** Performance | 25% | 1 | 1 | 4 | 0 | P2 | 16h |
| **ADV-06** Integrações | 15% | 0 | 1 | 3 | 0 | P1 | 24h |
| **ADV-13** Feature Flags | 0% | 0 | 0 | 4 | 0 | P1 | 24h |
| **ADV-14** Dead Letter Queue | 0% | 0 | 0 | 3 | 0 | P1 | 24h |
| **ADV-17** Multi-Tenant | 0% | 0 | 0 | 4 | 0 | P1 | 80h |
| **ADV-27** Notificações RT | 0% | 0 | 0 | 3 | 0 | P2 | 40h |
| **SHRD-56** Threat Model | 0% | 0 | 0 | 4 | 0 | P1 | 24h |
| **SHRD-61** LGPD | 15% | 0 | 3 | 7 | 0 | P0 | 24h |

**Legenda:** ✅ = Implementado | ⚠️ = Parcial | ❌ = Ausente | N/A = Não aplicável

---

## 6. Resumo de Esforço por Fase

| Fase | Foco | Duração | Effort | Score Target |
|------|------|---------|--------|-------------|
| **Fase 1** | Sobrevivência | 4 semanas | ~120h | 22% → 38% |
| **Fase 2** | Fundação | 6 semanas | ~160h | 38% → 55% |
| **Fase 3** | Maturidade | 8 semanas | ~160h | 55% → 72% |
| **Fase 4** | Excelência | Contínuo | ~80h | 72% → 85% |
| **Total** | — | ~18 semanas | ~520h | 85% |

---

## 7. Notas Metodológicas

- **Análise baseada em código real** — Todos os arquivos PHP, SQL e de configuração foram lidos e comparados com os respectivos módulos do Meta-Framework.
- **Stack PHP puro considerada** — Quando o Meta-Framework sugere Prisma, Fastify, BullMQ ou outras tecnologias Node.js/TypeScript, marquei como "N/A" ou adaptei para o equivalente em PHP (ex: PDO em vez de Prisma, PHPUnit em vez de Jest).
- **Módulos de IA avançada** (AI-38, AI-58, AI-59) e **Web3** (CORE-47) marcados como N/A — não são relevantes para o escopo atual do CRM.
- **Scores calculados** pela proporção de aspectos implementados vs. exigidos por cada módulo, com peso maior para aspectos críticos.
- **Effort estimado** com base na complexidade de implementação em PHP puro, considerando a base de código existente.

---

*Gerado pelo Agente Analista 📊 — ProspecCRM vs Meta-Framework Universal*