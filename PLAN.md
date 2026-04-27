# 🏗️ Plano Completo — CRM Prospec

> CRM de Prospecção Comercial em PHP puro (MVC), migrando do Prospector (Python/Flask + JSON) para PostgreSQL + Redis

---

## 1. Visão Geral

### 1.1 O que é
CRM de prospecção comercial B2B focado em nichos locais. Substitui o Prospector atual (Python/Flask + JSON files) por um sistema multi-usuário, persistente, com pipeline Kanban, agenda, templates e relatórios.

### 1.2 Stack Tecnológica
| Camada | Tecnologia | Versão |
|--------|-----------|--------|
| Backend | PHP puro (MVC manual, sem framework) | 8.2+ |
| Banco relacional | PostgreSQL | 16 |
| Cache / Rate Limit | Redis | 7 |
| Frontend CSS | Tailwind CSS via CDN | 3.4 |
| Frontend JS | Alpine.js via CDN | 3.x |
| Servidor HTTP | Nginx + PHP-FPM | 1.25 / 8.2 |
| Containerização | Docker Compose | v3.8 |

### 1.3 Por que PHP puro
- Sem dependência de framework (Laravel/Symfony são overkill para este porte)
- Controle total sobre performance e arquitetura
- Deploy simples: um container PHP-FPM + Nginx
- Facilidade de manutenção por qualquer dev PHP
- MVC manual é suficientemente estruturado para ~15 controllers

### 1.4 Migração do Prospector
O Prospector atual:
- Backend Python/Flask com JSON files como persistência
- Frontend SPA inline (HTML+CSS+JS em arquivo único)
- Pipeline 5 etapas: Discovery → Enrich → Score → Market Analysis → Lead Analysis
- APIs: Serper (Google Search + Places), BrasilAPI (CNPJ), Ollama Cloud (IA)
- Scoring: capital social + presença digital + tempo de mercado + avaliação Maps
- Enrichment: scraping de sites, extração de emails/telefones/redes sociais/CNPJ

Toda essa lógica será migrada para PHP, mantendo compatibilidade com os mesmos serviços externos.

---

## 2. Arquitetura MVC

### 2.1 Estrutura de Diretórios

```
prospec-crm/
├── docker-compose.yml
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       ├── Dockerfile
│       └── php.ini
├── public/
│   ├── index.php              # Entry point (front controller)
│   ├── assets/
│   │   ├── css/
│   │   │   └── app.css        # Tailwind overrides
│   │   ├── js/
│   │   │   ├── app.js         # Alpine.js global store
│   │   │   ├── components.js  # Reusable Alpine components
│   │   │   └── kanban.js      # Drag&drop Kanban
│   │   └── img/
│   │       └── logo.svg
│   └── .htaccess              # Fallback rewrite (Nginx handles this)
├── app/
│   ├── Core/
│   │   ├── Router.php         # Roteamento URL → Controller@method
│   │   ├── Request.php        # Wrapper de $_GET/$_POST/$_SERVER
│   │   ├── Response.php       # JSON responses, redirects
│   │   ├── Controller.php     # Base controller (abstract)
│   │   ├── Model.php          # Base model (PDO wrapper)
│   │   ├── View.php           # Template engine (PHP native)
│   │   ├── Auth.php           # Autenticação (session + bcrypt)
│   │   ├── Csrf.php           # Geração e validação CSRF token
│   │   ├── Validator.php      # Validação de dados
│   │   ├── Session.php        # Session handler
│   │   ├── Flash.php          # Flash messages
│   │   ├── Middleware.php     # Middleware pipeline
│   │   ├── RateLimiter.php    # Rate limiting via Redis
│   │   └── Helper.php         # Funções utilitárias (sanitize, slugify, etc.)
│   ├── Middleware/
│   │   ├── AuthMiddleware.php       # Verifica sessão autenticada
│   │   ├── CsrfMiddleware.php       # Valida CSRF em POST/PUT/DELETE
│   │   ├── AdminMiddleware.php      # Verifica role = admin
│   │   └── RateLimitMiddleware.php  # Rate limiting por IP/rota
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ProspecController.php
│   │   ├── LeadController.php
│   │   ├── PipelineController.php
│   │   ├── ContactController.php
│   │   ├── TemplateController.php
│   │   ├── AgendaController.php
│   │   ├── ReportController.php
│   │   ├── SettingsController.php
│   │   └── ApiController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Company.php
│   │   ├── Lead.php
│   │   ├── PipelineStage.php
│   │   ├── LeadActivity.php
│   │   ├── SearchSession.php
│   │   ├── SearchLead.php
│   │   ├── Template.php
│   │   ├── Task.php
│   │   ├── AuditLog.php
│   │   └── Setting.php
│   └── Services/
│       ├── SerperService.php        # Google Search + Places API
│       ├── BrasilApiService.php     # CNPJ lookup
│       ├── OllamaService.php        # IA (análise de mercado + leads)
│       ├── ScoringService.php       # Cálculo de scores
│       ├── EnrichmentService.php    # Scraping + dados de sites
│       ├── DeduplicationService.php # Deduplicação de leads
│       ├── QueryService.php         # Geração de query variations
│       ├── EmailService.php         # SMTP via PHPMailer
│       ├── RedisService.php         # Cache + rate limiting
│       └── MigrationService.php     # Importação JSON → PostgreSQL
├── config/
│   ├── app.php                 # Config geral (nome, URL, timezone)
│   ├── database.php            # PostgreSQL DSN, user, pass
│   ├── redis.php               # Redis host, port, db
│   ├── services.php            # Chaves API (Serper, Ollama, etc.)
│   └── mail.php                # SMTP config
├── database/
│   ├── migrations/
│   │   ├── 001_create_users.php
│   │   ├── 002_create_companies.php
│   │   ├── 003_create_pipeline_stages.php
│   │   ├── 004_create_leads.php
│   │   ├── 005_create_lead_activities.php
│   │   ├── 006_create_search_sessions.php
│   │   ├── 007_create_search_leads.php
│   │   ├── 008_create_templates.php
│   │   ├── 009_create_tasks.php
│   │   ├── 010_create_audit_log.php
│   │   ├── 011_create_settings.php
│   │   └── 012_seed_pipeline_stages.php
│   └── migrate.php             # Runner de migrations
├── views/
│   ├── layouts/
│   │   ├── app.php             # Layout principal (sidebar + content)
│   │   ├── auth.php            # Layout login/registro
│   │   └── blank.php           # Layout sem sidebar (erros, etc.)
│   ├── partials/
│   │   ├── sidebar.php
│   │   ├── header.php
│   │   ├── flash-messages.php
│   │   ├── pagination.php
│   │   ├── lead-card.php
│   │   ├── kanban-column.php
│   │   ├── activity-timeline.php
│   │   └── confirm-modal.php
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── forgot.php
│   │   └── reset.php
│   ├── dashboard/
│   │   └── index.php
│   ├── prospec/
│   │   ├── search.php          # Form + resultados + pipeline steps
│   │   └── history.php
│   ├── leads/
│   │   ├── index.php
│   │   ├── show.php
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── import.php
│   ├── pipeline/
│   │   └── index.php           # Kanban board
│   ├── contacts/
│   │   └── show.php            # Timeline de contato
│   ├── templates/
│   │   ├── index.php
│   │   └── form.php
│   ├── agenda/
│   │   ├── index.php
│   │   └── calendar.php
│   ├── reports/
│   │   ├── conversion.php
│   │   └── ranking.php
│   ├── settings/
│   │   ├── profile.php
│   │   ├── integrations.php
│   │   └── team.php
│   └── errors/
│       ├── 404.php
│       └── 500.php
├── storage/
│   ├── logs/                   # App logs
│   ├── cache/                   # File cache fallback
│   ├── sessions/                # PHP session files
│   └── uploads/                 # CSV imports, avatares
├── scripts/
│   ├── migrate_json.php         # Script de migração JSON → PostgreSQL
│   └── seed.php                 # Dados iniciais (admin user, stages)
└── tests/
    ├── Unit/
    │   ├── ScoringServiceTest.php
    │   └── DeduplicationServiceTest.php
    └── Feature/
        ├── AuthTest.php
        ├── LeadCrudTest.php
        └── PipelineTest.php
```

### 2.2 Fluxo de Requisição

```
Requisição HTTP
    │
    ▼
public/index.php (Front Controller)
    │
    ▼
Router::dispatch($method, $uri)
    │  - Carrega rotas de config/routes.php
    │  - Match: method + URI pattern → Controller@method
    │  - Extrai parâmetros da URL
    │
    ▼
Middleware Pipeline
    │  - RateLimitMiddleware (Redis)
    │  - CsrfMiddleware (POST/PUT/DELETE)
    │  - AuthMiddleware (rotas protegidas)
    │  - AdminMiddleware (rotas admin)
    │
    ▼
Controller@method($request)
    │  - Recebe Request object
    │  - Valida dados (Validator)
    │  - Chama Model ou Service
    │  - Retorna Response
    │
    ├──→ Model (PDO → PostgreSQL)
    │       - CRUD genérico no Base Model
    │       - Queries específicas nos Models filhos
    │
    ├──→ Service (lógica de negócio)
    │       - SerperService, ScoringService, etc.
    │       - Chamadas HTTP (Guzzle/cURL)
    │       - Cache via Redis
    │
    └──→ Response
         ├── HTML: View::render('leads/index', $data)
         │       - Layout + view + partials
         │       - XSS: htmlspecialchars em todos os outputs
         │       - Alpine.js data no topo da view
         └── JSON: Response::json($data, $status)
                 - API endpoints
                 - AJAX requests
```

### 2.3 Core — Detalhes

#### Router.php
```php
// Registro de rotas
$router->get('/', 'DashboardController@index');
$router->get('/leads', 'LeadController@index');
$router->get('/leads/create', 'LeadController@create');
$router->post('/leads', 'LeadController@store');
$router->get('/leads/{id}', 'LeadController@show');
$router->get('/leads/{id}/edit', 'LeadController@edit');
$router->put('/leads/{id}', 'LeadController@update');
$router->delete('/leads/{id}', 'LeadController@destroy');
$router->post('/api/search', 'ApiController@search');
$router->post('/api/search/{id}/enrich', 'ApiController@enrich');
// ... etc

// Suporte a:
// - Route groups (prefix, middleware)
// - Named routes (para gerar URLs)
// - RESTful resource routing
```

#### Base Controller
```php
abstract class Controller {
    protected Request $request;
    
    protected function view(string $template, array $data = []): Response {
        return View::render($template, $data);
    }
    
    protected function json($data, int $status = 200): Response {
        return Response::json($data, $status);
    }
    
    protected function redirect(string $url): Response {
        return Response::redirect($url);
    }
    
    protected function authorize(string $role): void {
        if (!Auth::user()->hasRole($role)) {
            Response::abort(403, 'Acesso negado');
        }
    }
    
    protected function validate(array $data, array $rules): array {
        return Validator::make($data, $rules);
    }
}
```

#### Base Model (PDO)
```php
abstract class Model {
    protected static string $table;
    protected static PDO $pdo;
    
    public static function setPdo(PDO $pdo): void { static::$pdo = $pdo; }
    
    public static function find(int $id): ?array {
        $stmt = static::$pdo->prepare("SELECT * FROM " . static::$table . " WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public static function all(): array {
        $stmt = static::$pdo->query("SELECT * FROM " . static::$table . " ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function where(string $col, $val): array {
        $stmt = static::$pdo->prepare("SELECT * FROM " . static::$table . " WHERE {$col} = :val");
        $stmt->execute(['val' => $val]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function create(array $data): int {
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $stmt = static::$pdo->prepare("INSERT INTO " . static::$table . " ({$cols}) VALUES ({$placeholders})");
        $stmt->execute($data);
        return (int) static::$pdo->lastInsertId();
    }
    
    public static function update(int $id, array $data): bool {
        $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $data['id'] = $id;
        $stmt = static::$pdo->prepare("UPDATE " . static::$table . " SET {$sets} WHERE id = :id");
        return $stmt->execute($data);
    }
    
    public static function delete(int $id): bool {
        $stmt = static::$pdo->prepare("DELETE FROM " . static::$table . " WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    public static function paginate(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $total = static::$pdo->query("SELECT COUNT(*) FROM " . static::$table)->fetchColumn();
        $stmt = static::$pdo->prepare("SELECT * FROM " . static::$table . " ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->execute(['limit' => $perPage, 'offset' => $offset]);
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }
}
```

#### Auth.php
```php
class Auth {
    public static function attempt(string $email, string $password): bool {
        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (!$user['active']) return false;
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        Session::regenerate();
        return true;
    }
    
    public static function user(): ?array {
        $id = Session::get('user_id');
        return $id ? User::find($id) : null;
    }
    
    public static function logout(): void {
        Session::destroy();
    }
    
    public static function check(): bool {
        return Session::has('user_id');
    }
    
    public static function isAdmin(): bool {
        return Session::get('user_role') === 'admin';
    }
}
```

#### Csrf.php
```php
class Csrf {
    public static function generate(): string {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }
    
    public static function check(string $token): bool {
        $stored = Session::get('csrf_token');
        if (!$stored || !hash_equals($stored, $token)) {
            return false;
        }
        return true;
    }
    
    public static function field(): string {
        return '<input type="hidden" name="_csrf" value="' . self::generate() . '">';
    }
}
```

#### Validator.php
```php
class Validator {
    protected static array $errors = [];
    
    public static function make(array $data, array $rules): array {
        static::$errors = [];
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $fieldRules) as $rule) {
                // required|email|min:3|max:255|confirmed|unique:users,email
                match(true) {
                    $rule === 'required' && empty($value) => static::addError($field, 'Campo obrigatório'),
                    $rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL) => static::addError($field, 'Email inválido'),
                    str_starts_with($rule, 'min:') && strlen($value) < (int) substr($rule, 4) => static::addError($field, "Mínimo " . substr($rule, 4) . " caracteres"),
                    str_starts_with($rule, 'max:') && strlen($value) > (int) substr($rule, 4) => static::addError($field, "Máximo " . substr($rule, 4) . " caracteres"),
                    str_starts_with($rule, 'unique:') => static::checkUnique($field, $value, $rule),
                    default => null,
                };
            }
        }
        return static::$errors;
    }
    
    public static function fails(): bool { return !empty(static::$errors); }
    public static function errors(): array { return static::$errors; }
}
```

---

## 3. Banco de Dados — Schema Completo

### 3.1 users
```sql
CREATE TABLE users (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            VARCHAR(20) NOT NULL DEFAULT 'user',  -- admin | manager | user
    active          BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_active ON users(active);
```

### 3.2 companies
```sql
CREATE TABLE companies (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(500) NOT NULL,
    cnpj            VARCHAR(18),                             -- formatado: XX.XXX.XXX/XXXX-XX
    niche           VARCHAR(255),
    city            VARCHAR(255),
    state           VARCHAR(2),                              -- UF
    phone           VARCHAR(50),
    email           VARCHAR(255),
    site_url        VARCHAR(500),
    instagram       VARCHAR(500),
    facebook        VARCHAR(500),
    youtube         VARCHAR(500),
    tiktok          VARCHAR(500),
    maps_rating     DECIMAL(2,1),                            -- 0.0 a 5.0
    maps_reviews    INTEGER DEFAULT 0,
    maps_address    TEXT,
    maps_phone     VARCHAR(50),
    maps_category  VARCHAR(255),
    maps_lat       DECIMAL(10,7),
    maps_lng       DECIMAL(10,7),
    score          INTEGER DEFAULT 0,                        -- 0-100
    notes          TEXT,
    
    -- Dados BrasilAPI / Receita
    razao_social       VARCHAR(500),
    situacao           VARCHAR(100),
    capital_social     DECIMAL(15,2),
    data_inicio        DATE,
    opcao_pelo_mei     BOOLEAN DEFAULT false,
    opcao_pelo_simples BOOLEAN DEFAULT false,
    cnae_descricao     VARCHAR(500),
    natureza_juridica  VARCHAR(255),
    porte              VARCHAR(100),
    email_receita      VARCHAR(255),
    telefone_receita   VARCHAR(50),
    socios             JSONB,                                -- Array de nomes: ["João", "Maria"]
    
    -- Contatos extraídos do site
    site_emails        JSONB DEFAULT '[]',                   -- ["contato@empresa.com"]
    site_phones        JSONB DEFAULT '[]',                   -- ["44999999999"]
    site_instagram     VARCHAR(500),
    site_facebook      VARCHAR(500),
    site_youtube       VARCHAR(500),
    site_tiktok        VARCHAR(500),
    cnpj_source        VARCHAR(50),                          -- 'snippet' | 'site (site)' | 'maps_phone'
    enrichment_status  VARCHAR(20) DEFAULT 'pending',         -- pending | partial | done
    
    tem_site       BOOLEAN DEFAULT false,
    tem_instagram  BOOLEAN DEFAULT false,
    tem_facebook   BOOLEAN DEFAULT false,
    tem_maps       BOOLEAN DEFAULT false,
    tem_ads        BOOLEAN DEFAULT false,
    
    created_by     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at     TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_companies_cnpj ON companies(cnpj);
CREATE INDEX idx_companies_niche ON companies(niche);
CREATE INDEX idx_companies_city ON companies(city);
CREATE INDEX idx_companies_niche_city ON companies(niche, city);
CREATE INDEX idx_companies_score ON companies(score DESC);
CREATE INDEX idx_companies_name_trgm ON companies USING gin(name gin_trgm_ops);  -- busca fuzzy
CREATE INDEX idx_companies_created_by ON companies(created_by);
```

### 3.3 pipeline_stages
```sql
CREATE TABLE pipeline_stages (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    position    INTEGER NOT NULL DEFAULT 0,
    color       VARCHAR(7) NOT NULL DEFAULT '#6c5ce7',     -- hex color
    is_default  BOOLEAN NOT NULL DEFAULT false,
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX idx_pipeline_stages_position ON pipeline_stages(position);

-- Seed data
INSERT INTO pipeline_stages (name, position, color, is_default) VALUES
    ('Novo',           1, '#6c5ce7', true),
    ('Contatado',      2, '#0984e3', false),
    ('Qualificado',    3, '#00cec9', false),
    ('Proposta',       4, '#fdcb6e', false),
    ('Fechado',        5, '#00b894', false),
    ('Perdido',        6, '#e17055', false);
```

### 3.4 leads
```sql
CREATE TABLE leads (
    id                SERIAL PRIMARY KEY,
    company_id        INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    pipeline_stage_id INTEGER NOT NULL REFERENCES pipeline_stages(id) ON DELETE RESTRICT,
    assigned_to       INTEGER REFERENCES users(id) ON DELETE SET NULL,
    score             INTEGER DEFAULT 0,
    source            VARCHAR(50) DEFAULT 'prospecção',     -- prospecção | importação | manual | referral
    status            VARCHAR(30) DEFAULT 'active',         -- active | won | lost | archived
    estimated_value   DECIMAL(12,2) DEFAULT 0,
    last_contact_at   TIMESTAMP WITH TIME ZONE,
    
    -- IA analysis (migrado do Prospector)
    ia_analise        TEXT,
    ia_market_analysis TEXT,
    
    created_at        TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_leads_company ON leads(company_id);
CREATE INDEX idx_leads_pipeline ON leads(pipeline_stage_id);
CREATE INDEX idx_leads_assigned ON leads(assigned_to);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_score ON leads(score DESC);
CREATE INDEX idx_leads_source ON leads(source);
CREATE INDEX idx_leads_last_contact ON leads(last_contact_at);
CREATE INDEX idx_leads_created ON leads(created_at DESC);
```

### 3.5 lead_activities
```sql
CREATE TABLE lead_activities (
    id          SERIAL PRIMARY KEY,
    lead_id     INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    user_id     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    type        VARCHAR(50) NOT NULL,                       -- call | email | whatsapp | note | status_change | score_change | ia_analysis | enrichment
    description TEXT NOT NULL,
    metadata    JSONB DEFAULT '{}',                         -- dados extras (ex: {"from_stage": "Novo", "to_stage": "Contatado"})
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_activities_lead ON lead_activities(lead_id);
CREATE INDEX idx_activities_user ON lead_activities(user_id);
CREATE INDEX idx_activities_type ON lead_activities(type);
CREATE INDEX idx_activities_created ON lead_activities(created_at DESC);
```

### 3.6 search_sessions
```sql
CREATE TABLE search_sessions (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    niche           VARCHAR(255) NOT NULL,
    city            VARCHAR(255) NOT NULL,
    state           VARCHAR(2) NOT NULL,
    query           VARCHAR(500),
    query_variations JSONB DEFAULT '[]',                     -- Array de strings
    raw_results_count INTEGER DEFAULT 0,
    total_results   INTEGER DEFAULT 0,
    com_site        INTEGER DEFAULT 0,
    com_instagram   INTEGER DEFAULT 0,
    com_maps        INTEGER DEFAULT 0,
    com_ads         INTEGER DEFAULT 0,
    com_cnpj        INTEGER DEFAULT 0,
    com_site_email  INTEGER DEFAULT 0,
    com_site_phone  INTEGER DEFAULT 0,
    com_youtube     INTEGER DEFAULT 0,
    com_tiktok      INTEGER DEFAULT 0,
    ia_market_analysis TEXT,
    status          VARCHAR(30) DEFAULT 'discovery',         -- discovery | enriching | enriched | scored | market_analyzed | analyzing_leads | analyzed
    analyzed_count  INTEGER DEFAULT 0,
    total_to_analyze INTEGER DEFAULT 0,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_search_user ON search_sessions(user_id);
CREATE INDEX idx_search_niche_city ON search_sessions(niche, city);
CREATE INDEX idx_search_status ON search_sessions(status);
CREATE INDEX idx_search_created ON search_sessions(created_at DESC);
```

### 3.7 search_leads
```sql
CREATE TABLE search_leads (
    id              SERIAL PRIMARY KEY,
    search_id       INTEGER NOT NULL REFERENCES search_sessions(id) ON DELETE CASCADE,
    company_id      INTEGER REFERENCES companies(id) ON DELETE SET NULL,
    position        INTEGER,
    is_place        BOOLEAN DEFAULT false,
    created_at      TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_search_leads_session ON search_leads(search_id);
CREATE INDEX idx_search_leads_company ON search_leads(company_id);
```

### 3.8 templates
```sql
CREATE TABLE templates (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name        VARCHAR(255) NOT NULL,
    niche       VARCHAR(255),
    channel     VARCHAR(30) NOT NULL,                       -- whatsapp | email | instagram
    subject     VARCHAR(500),                               -- Para email
    body        TEXT NOT NULL,
    variables   JSONB DEFAULT '[]',                          -- ["nome", "empresa", "cidade"]
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_templates_user ON templates(user_id);
CREATE INDEX idx_templates_niche ON templates(niche);
CREATE INDEX idx_templates_channel ON templates(channel);
```

### 3.9 tasks
```sql
CREATE TABLE tasks (
    id            SERIAL PRIMARY KEY,
    lead_id       INTEGER REFERENCES leads(id) ON DELETE CASCADE,
    user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title         VARCHAR(255) NOT NULL,
    description   TEXT,
    due_date      DATE,
    completed_at  TIMESTAMP WITH TIME ZONE,
    created_at    TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_tasks_lead ON tasks(lead_id);
CREATE INDEX idx_tasks_user ON tasks(user_id);
CREATE INDEX idx_tasks_due ON tasks(due_date);
CREATE INDEX idx_tasks_completed ON tasks(completed_at) WHERE completed_at IS NOT NULL;  -- partial index
CREATE INDEX idx_tasks_pending ON tasks(due_date) WHERE completed_at IS NULL;             -- pending tasks
```

### 3.10 audit_log
```sql
CREATE TABLE audit_log (
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action       VARCHAR(100) NOT NULL,                      -- login | create | update | delete | export | search
    entity_type  VARCHAR(50),                                 -- lead | company | template | user
    entity_id    INTEGER,
    details      JSONB DEFAULT '{}',
    ip           VARCHAR(45),                                -- IPv4 ou IPv6
    created_at   TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_audit_action ON audit_log(action);
CREATE INDEX idx_audit_entity ON audit_log(entity_type, entity_id);
CREATE INDEX idx_audit_created ON audit_log(created_at DESC);
```

### 3.11 settings
```sql
CREATE TABLE settings (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    key         VARCHAR(100) NOT NULL,
    value       TEXT,
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    
    UNIQUE(user_id, key)
);

CREATE INDEX idx_settings_user_key ON settings(user_id, key);
```

### 3.12 Extensões PostgreSQL necessárias
```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;      -- Busca fuzzy (similaridade entre nomes)
CREATE EXTENSION IF NOT EXISTS unaccent;     -- Busca sem acento
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";  -- UUID generation (se necessário)
```

---

## 4. Módulos Detalhados

### 4a. Auth

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /login | login form | guest |
| POST | /login | autenticar | guest, csrf |
| GET | /register | registro form | guest |
| POST | /register | criar conta | guest, csrf |
| POST | /logout | encerrar sessão | auth |
| GET | /profile | editar perfil | auth |
| PUT | /profile | atualizar perfil | auth, csrf |
| GET | /forgot | esqueci senha | guest |
| POST | /forgot | enviar reset link | guest, csrf |
| GET | /reset/{token} | form reset senha | guest |
| POST | /reset/{token} | salvar nova senha | guest, csrf |

**Controller Methods:**
- `login()` — exibe form, `authenticate()` — verifica credenciais, cria sessão, loga audit
- `register()` — exibe form, `store()` — valida (nome, email único, senha min 8), hash bcrypt, cria user, loga audit
- `logout()` — destroy session, redirect
- `profile()` — dados do user logado, `updateProfile()` — atualiza nome/email/senha
- `forgot()` — gera token, salva em settings, envia email, `reset()` — valida token, atualiza senha

**Views:** `auth/login.php`, `auth/register.php`, `auth/forgot.php`, `auth/reset.php`

**Regras de Negócio:**
- Senha: bcrypt (cost 12), mínimo 8 caracteres
- Sessão: PHP native sessions, timeout 2h (configurável)
- Rate limiting: 5 tentativas de login por IP por 15 min (Redis)
- Após 5 falhas, bloqueia IP por 30 min
- Primeiro user criado = admin
- Registro pode ser desabilitado nas settings (padrão: habilitado)

---

### 4b. Dashboard

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | / | dashboard principal | auth |

**Controller Methods:**
- `index()` — agrega dados de múltiplos models para montar dashboard

**KPIs exibidos:**
- Total de leads ativos / por estágio do pipeline
- Leads novos esta semana / este mês
- Taxa de conversão (leads → clientes) por período
- Valor estimado total no pipeline
- Tarefas pendentes (vencendo hoje + vencidas)
- Últimas prospecções realizadas
- Top 5 empresas por score

**Views:** `dashboard/index.php`

**Queries:**
```sql
-- Leads por estágio
SELECT ps.name, ps.color, COUNT(l.id) as count
FROM pipeline_stages ps
LEFT JOIN leads l ON l.pipeline_stage_id = ps.id AND l.status = 'active'
GROUP BY ps.id ORDER BY ps.position;

-- Taxa de conversão
SELECT 
    COUNT(CASE WHEN status = 'won' THEN 1 END) as won,
    COUNT(CASE WHEN status = 'lost' THEN 1 END) as lost,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(*) as total
FROM leads;

-- Tarefas pendentes
SELECT * FROM tasks 
WHERE user_id = :uid AND completed_at IS NULL 
ORDER BY due_date ASC NULLS LAST 
LIMIT 10;
```

---

### 4c. Prospecção

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /prospec | form busca + histórico | auth |
| POST | /api/prospec/search | iniciar busca | auth, csrf, rate:10/min |
| POST | /api/prospec/{id}/enrich | enriquecer | auth, csrf |
| POST | /api/prospec/{id}/score | calcular scores | auth, csrf |
| POST | /api/prospec/{id}/market | análise mercado IA | auth, csrf |
| POST | /api/prospec/{id}/leads | análise leads IA | auth, csrf |
| POST | /api/prospec/{id}/run-all | pipeline completo | auth, csrf |
| GET | /api/prospec/{id} | status/dados busca | auth |
| GET | /api/propec/history | histórico buscas | auth |
| DELETE | /api/propec/{id} | excluir busca | auth, csrf, admin |

**Controller Methods (ApiController):**
- `search()` — recebe nicho/cidade/estado, gera search_id, dispara Discovery em background (exec_async ou queue)
- `enrich()` — executa enriquecimento CNPJ + site scraping
- `score()` — calcula scores de todos os leads da busca
- `marketAnalysis()` — chama Ollama Cloud para análise de mercado
- `leadAnalysis()` — chama Ollama Cloud para análise individual de leads
- `runAll()` — executa pipeline completo (enrich → score → market → leads)
- `status()` — retorna dados da busca (para polling do frontend)

**Serviços migrados do Prospector Python:**

1. **SerperService** — tradução direta de `serper_search()`:
   - `search(string $query): array` — Google Search organic
   - `places(string $query): array` — Google Places
   - Throttle: 2s entre chamadas

2. **BrasilApiService** — tradução de `check_cnpj()`:
   - `lookupCnpj(string $cnpj): ?array` — Retorna dados da Receita
   - Throttle: 0.3s a cada 5 calls

3. **OllamaService** — tradução de `ai_analyze()`:
   - `chat(string $prompt, string $model = null): string`
   - Fallback models: glm-5.1 → minimax-m2.7 → deepseek-v3.2
   - Timeout: 90s

4. **EnrichmentService** — tradução de `run_enrich()` + `fetch_site_data()`:
   - `enrichCompany(Company $company): void` — scraping + BrasilAPI
   - `scrapeSite(string $url): array` — extrai emails, telefones, redes, CNPJ
   - `findCnpjByPhone(string $phone): ?string` — busca reversa

5. **ScoringService** — tradução de `calculate_score()`:
   - `calculate(array $companyData): int` — 0-100
   - Pesos idênticos ao Prospector:
     - Capital social (0-20)
     - Presença digital (0-40: site 10, inst 10, maps 10, ads 10)
     - Enrichment bônus (email +5, tel +3, youtube +3, tiktok +2)
     - Tempo de mercado (0-15)
     - Avaliação Maps (0-15)
     - MEI penalty (-5)

6. **QueryService** — tradução de `generate_query_variations()`:
   - `generate(string $niche, string $city, string $state): array` — Até 8 variações
   - Mantém todas as variações específicas por nicho (estética, odonto, etc.)

7. **DeduplicationService** — tradução de `_deduplicate_leads()`:
   - `deduplicate(array $leads): array` — Remove duplicatas por domain, CNPJ, fuzzy name
   - Fuzzy: similaridade >= 0.7 (usando similar_text do PHP)

**Views:** `prospec/search.php`, `prospec/history.php`

**Regras de Negócio:**
- Cada busca gera uma `search_session` + N `search_leads`
- Empresas descobertas são salvas em `companies` (se já existir por CNPJ/domain, merge dados)
- Leads são criados em `leads` com estágio "Novo"
- Pipeline é executado passo a passo (frontend controla via AJAX)
- Rate limiting: 10 buscas/hora/user (Redis)
- Background: buscas longas rodam via `exec()` ou fastcgi_finish_request()

---

### 4d. Leads

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /leads | listagem (filtros + paginação) | auth |
| GET | /leads/create | form criação manual | auth |
| POST | /leads | salvar | auth, csrf |
| GET | /leads/{id} | detalhe + timeline | auth |
| GET | /leads/{id}/edit | form edição | auth |
| PUT | /leads/{id} | atualizar | auth, csrf |
| DELETE | /leads/{id} | excluir | auth, csrf |
| POST | /leads/{id}/enrich | enriquecer dados | auth, csrf |
| POST | /leads/{id}/diagnostic | diagnóstico IA | auth, csrf |
| GET | /leads/export | exportar CSV | auth |
| POST | /leads/import | importar CSV | auth, csrf |

**Controller Methods:**
- `index()` — lista leads com filtros (pipeline_stage, assigned_to, source, status, score range, search)
- `create()` / `store()` — criação manual de lead
- `show()` — detalhe com timeline de atividades, tarefas, IA
- `edit()` / `update()` — edição
- `destroy()` — soft: status = 'archived'
- `enrich()` — enriquece empresa associada (BrasilAPI + scraping)
- `diagnostic()` — gera análise IA para este lead específico
- `export()` — gera CSV com todos os leads filtrados
- `import()` — importa CSV, mapeia colunas, cria companies + leads

**Views:** `leads/index.php`, `leads/create.php`, `leads/show.php`, `leads/edit.php`, `leads/import.php`

**Regras de Negociação:**
- Lead sempre associado a uma Company (cria se não existir)
- Score é recalculado quando dados da empresa mudam
- Import CSV: colunas obrigatórias (name), opcionais (cnpj, phone, email, city, niche)
- Export CSV: todas as colunas de leads + companies
- Permissão: user vê só seus leads, manager/admin vê todos

---

### 4e. Pipeline

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /pipeline | Kanban board | auth |
| PUT | /api/pipeline/move | mover lead de estágio | auth, csrf |
| POST | /api/pipeline/automove | auto-mover baseado em regras | auth, csrf |
| GET | /pipeline/stages | gerenciar estágios | auth, admin |
| POST | /pipeline/stages | criar estágio | auth, csrf, admin |
| PUT | /pipeline/stages/{id} | editar estágio | auth, csrf, admin |
| DELETE | /pipeline/stages/{id} | excluir estágio | auth, csrf, admin |

**Controller Methods:**
- `index()` — busca leads agrupados por estágio
- `move()` — move lead entre estágios, registra atividade
- `automove()` — regras automáticas (ex: após contato, mover para "Contatado")

**Views:** `pipeline/index.php`

**Kanban (Alpine.js + Drag & Drop):**
```html
<div x-data="kanban()" class="flex gap-4 overflow-x-auto">
    <template x-for="stage in stages" :key="stage.id">
        <div class="kanban-column min-w-[280px] flex-shrink-0"
             x-on:dragover.prevent
             x-on:drop="moveLead(stage.id, $event)">
            <div class="font-bold mb-3" :style="'color:' + stage.color"
                 x-text="stage.name + ' (' + stage.leads.length + ')'"></div>
            <template x-for="lead in stage.leads" :key="lead.id">
                <div class="kanban-card bg-gray-800 rounded-lg p-3 mb-2 cursor-grab"
                     draggable="true"
                     x-on:dragstart="dragLead = lead">
                    <!-- lead card content -->
                </div>
            </template>
        </div>
    </template>
</div>
```

**Regras de Auto-Move:**
- Lead com `last_contact_at` preenchido e estágio "Novo" → "Contatado"
- Lead com proposta enviada (atividade tipo "email" com subject contendo "proposta") → "Proposta"
- Lead sem contato há 30+ dias → notificação (não move automático)

---

### 4f. Contatos

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /leads/{id}/contact | timeline de contato | auth |
| POST | /leads/{id}/activity | registrar atividade | auth, csrf |
| POST | /leads/{id}/whatsapp | gerar link WhatsApp | auth |
| POST | /leads/{id}/email | enviar email | auth, csrf |
| POST | /leads/{id}/note | adicionar nota | auth, csrf |

**Controller Methods:**
- `timeline()` — lista lead_activities ordenados por data
- `addActivity()` — cria atividade (call, email, whatsapp, note)
- `whatsapp()` — gera link `https://wa.me/55XXXXXXXX?text=MENSAGEM` (abre em nova aba)
- `sendEmail()` — envia email via SMTP (PHPMailer)
- `addNote()` — cria atividade tipo "note"

**Views:** Embutido em `leads/show.php` (aba "Contatos")

**Regras de Negócio:**
- Cada atividade atualiza `last_contact_at` do lead
- WhatsApp: apenas gera o link (não envia diretamente)
- Email: usa template se selecionado, substitui variáveis
- Notas: texto livre, visível na timeline

---

### 4g. Templates

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /templates | listar | auth |
| GET | /templates/create | form criar | auth |
| POST | /templates | salvar | auth, csrf |
| GET | /templates/{id}/edit | form editar | auth |
| PUT | /templates/{id} | atualizar | auth, csrf |
| DELETE | /templates/{id} | excluir | auth, csrf |

**Controller Methods:**
- CRUD padrão
- `preview()` — mostra template com variáveis preenchidas (sample data)

**Views:** `templates/index.php`, `templates/form.php`

**Variáveis disponíveis:**
- `{{nome}}` — nome da empresa
- `{{contato}}` — nome do contato (se disponível)
- `{{cidade}}` — cidade
- `{{nicho}}` — nicho
- `{{score}}` — score da empresa
- `{{gap_principal}}` — principal gap identificado pela IA

**Regras:**
- Templates são por usuário (cada um vê os seus)
- Admin pode marcar templates como "globais" (visíveis para todos)
- Canal: whatsapp (máx 200 chars), email (sem limite), instagram (máx 500 chars)

---

### 4h. Agenda

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /agenda | lista tarefas | auth |
| GET | /agenda/calendar | calendário visual | auth |
| POST | /tasks | criar tarefa | auth, csrf |
| PUT | /tasks/{id} | atualizar | auth, csrf |
| PUT | /tasks/{id}/complete | marcar como feita | auth, csrf |
| DELETE | /tasks/{id} | excluir | auth, csrf |

**Controller Methods:**
- `index()` — lista tarefas do user (pendentes primeiro, por due_date)
- `calendar()` — tarefas agrupadas por data
- `store()` — cria tarefa (pode associar a lead)
- `complete()` — marca `completed_at = now()`
- `followUp()` — cria tarefa de follow-up automaticamente após contato

**Views:** `agenda/index.php`, `agenda/calendar.php`

**Regras de Negócio:**
- Tarefas sem lead são pessoais
- Tarefas com lead são visíveis na timeline do lead
- Follow-up automático: ao registrar contato, sugere follow-up em 3 dias
- Notificação: tarefas vencendo hoje destacadas em vermelho
- Calendário: visual mensal com tarefas por dia

---

### 4i. Relatórios

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /reports | menu relatórios | auth |
| GET | /reports/conversion | funil de conversão | auth |
| GET | /reports/ranking | ranking por score | auth |
| GET | /reports/roi | ROI por período | auth |
| GET | /reports/team | performance equipe | auth, manager |
| GET | /api/reports/data | dados JSON (AJAX) | auth |

**Relatórios:**

1. **Funil de Conversão**
   - Leads por estágio: Novo → Contatado → Qualificado → Proposta → Fechado
   - Taxa de conversão entre etapas
   - Período filtrável (7d, 30d, 90d, all)
   - Valor estimado por etapa

2. **Ranking por Score**
   - Top 20 empresas com maior score
   - Distribuição de scores (histograma)
   - Comparativo: com site vs sem site, com Instagram vs sem

3. **ROI**
   - Leads convertidos / total de prospecções
   - Valor estimado fechado vs investido (tempo)
   - Custo por lead (baseado em horas × valor/hora)

4. **Performance Equipe** (manager/admin)
   - Leads por membro
   - Atividades por tipo (calls, emails, etc.)
   - Taxa de resposta por membro

**Views:** `reports/conversion.php`, `reports/ranking.php`

---

### 4j. Configurações

**Rotas:**
| Método | URI | Ação | Middleware |
|--------|-----|------|-----------|
| GET | /settings | painel config | auth |
| GET | /settings/profile | editar perfil | auth |
| PUT | /settings/profile | salvar perfil | auth, csrf |
| GET | /settings/integrations | chaves API | auth, admin |
| PUT | /settings/integrations | salvar chaves | auth, csrf, admin |
| GET | /settings/team | gerenciar equipe | auth, admin |
| POST | /settings/team | criar user | auth, csrf, admin |
| PUT | /settings/team/{id} | editar user | auth, csrf, admin |
| DELETE | /settings/team/{id} | desativar user | auth, csrf, admin |

**Views:** `settings/profile.php`, `settings/integrations.php`, `settings/team.php`

**Configurações armazenadas:**
- API keys: SERPER_KEY, OLLAMA_KEY, OLLAMA_BASE, OLLAMA_MODEL
- SMTP: host, port, user, pass, from_name, from_email
- App: timezone, registro_ativado, sessao_timeout
- Rate limits: buscas/hora, enrich/min, ia/min

---

### 4k. API

**Rotas (prefixo /api/v1):**
| Método | URI | Ação | Auth |
|--------|-----|------|------|
| GET | /api/v1/leads | listar leads | token |
| GET | /api/v1/leads/{id} | detalhe lead | token |
| POST | /api/v1/leads | criar lead | token |
| PUT | /api/v1/leads/{id} | atualizar | token |
| DELETE | /api/v1/leads/{id} | excluir | token |
| GET | /api/v1/companies | listar empresas | token |
| POST | /api/v1/search | busca Serper | token |
| GET | /api/v1/pipeline | estágios + leads | token |
| GET | /api/v1/reports/summary | KPIs gerais | token |

**Autenticação API:**
- Token no header: `Authorization: Bearer {token}`
- Tokens gerados na tabela `settings` (key = 'api_token')
- Rate limiting: 100 requests/min por token

**Response format:**
```json
{
    "data": [...],
    "meta": {
        "total": 150,
        "page": 1,
        "per_page": 20,
        "last_page": 8
    }
}
```

---

## 5. Migração de Dados

### 5.1 Fonte
O Prospector atual salva cada busca como um JSON file em `/app/data/{search_id}.json`. O script de migração deve ler todos os JSONs e importar para PostgreSQL.

### 5.2 Script: `scripts/migrate_json.php`

```php
<?php
/**
 * Migração: Prospector JSON → CRM PostgreSQL
 * 
 * Uso: php scripts/migrate_json.php /caminho/para/data/
 * 
 * Lê todos os .json do diretório, cria companies, leads, search_sessions, search_leads
 */

$dataDir = $argv[1] ?? '/app/data';
$files = glob($dataDir . '/*.json');

echo "Encontrados " . count($files) . " arquivos JSON\n";

foreach ($files as $file) {
    $json = json_decode(file_get_contents($file), true);
    if (!$json) continue;
    
    $summary = $json['summary'] ?? [];
    $leads = $json['leads'] ?? [];
    $searchId = basename($file, '.json');
    
    echo "Processando busca {$searchId}: {$summary['niche']} em {$summary['city']}\n";
    
    // 1. Criar search_session
    $sessionId = SearchSession::create([
        'user_id'         => 1, // admin (ou configurável)
        'niche'           => $summary['niche'],
        'city'            => $summary['city'],
        'state'           => $summary['state'],
        'query'           => $summary['query'],
        'query_variations' => json_encode($summary['query_variations'] ?? []),
        'raw_results_count' => $summary['raw_results'] ?? 0,
        'total_results'   => $summary['total_results'] ?? 0,
        'com_site'        => $summary['com_site'] ?? 0,
        'com_instagram'   => $summary['com_instagram'] ?? 0,
        'com_maps'        => $summary['com_maps'] ?? 0,
        'com_ads'         => $summary['com_ads'] ?? 0,
        'com_cnpj'        => $summary['com_cnpj'] ?? 0,
        'com_site_email'  => $summary['com_site_email'] ?? 0,
        'com_site_phone'  => $summary['com_site_phone'] ?? 0,
        'com_youtube'     => $summary['com_youtube'] ?? 0,
        'com_tiktok'      => $summary['com_tiktok'] ?? 0,
        'ia_market_analysis' => $summary['ia_market_analysis'] ?? null,
        'status'          => $json['status'] ?? 'discovery',
        'analyzed_count'  => $summary['analyzed_count'] ?? 0,
        'total_to_analyze' => $summary['total_to_analyze'] ?? 0,
        'created_at'      => $summary['timestamp'] ?? date('c'),
    ]);
    
    // 2. Processar cada lead → company + lead + search_lead
    foreach ($leads as $idx => $leadData) {
        // 2a. Criar/atualizar Company (dedup por CNPJ ou nome)
        $companyId = null;
        
        if (!empty($leadData['cnpj'])) {
            $existing = Company::where('cnpj', $leadData['cnpj']);
            if ($existing) {
                $companyId = $existing['id'];
                // Merge: preencher campos vazios
                Company::mergeFromProspector($existing['id'], $leadData);
            }
        }
        
        if (!$companyId && !empty($leadData['title'])) {
            $existing = Company::findByNameFuzzy($leadData['title']);
            if ($existing) {
                $companyId = $existing['id'];
                Company::mergeFromProspector($existing['id'], $leadData);
            }
        }
        
        if (!$companyId) {
            $companyId = Company::createFromProspector($leadData);
        }
        
        // 2b. Criar Lead (se não existe para esta company nesta sessão)
        $leadId = Lead::create([
            'company_id'        => $companyId,
            'pipeline_stage_id' => 1, // "Novo"
            'assigned_to'       => 1,
            'score'             => $leadData['score'] ?? 0,
            'source'            => 'prospecção',
            'status'            => 'active',
            'ia_analise'        => $leadData['ia_analise'] ?? null,
        ]);
        
        // 2c. Criar search_lead
        SearchLead::create([
            'search_id'  => $sessionId,
            'company_id' => $companyId,
            'position'   => $leadData['position'] ?? $idx,
            'is_place'   => $leadData['is_place'] ?? false,
        ]);
        
        // 2d. Criar atividade se tem análise IA
        if (!empty($leadData['ia_analise'])) {
            LeadActivity::create([
                'lead_id'     => $leadId,
                'user_id'     => 1,
                'type'        => 'ia_analysis',
                'description' => 'Análise IA importada do Prospector',
                'metadata'    => json_encode(['ia_analise' => $leadData['ia_analise']]),
            ]);
        }
    }
    
    echo "  → {$sessionId}: " . count($leads) . " leads importados\n";
}

echo "\nMigração concluída!\n";
```

### 5.3 Mapeamento de Campos (JSON → PostgreSQL)

| JSON (Prospector) | Tabela | Campo |
|---|---|---|
| `title` | companies | name |
| `link` | companies | site_url (se não social) |
| `snippet` | — | descartado (dados enriquecidos vão para campos específicos) |
| `cnpj` | companies | cnpj |
| `razao_social` | companies | razao_social |
| `situacao` | companies | situacao |
| `capital_social` | companies | capital_social |
| `data_inicio` | companies | data_inicio |
| `opcao_pelo_mei` | companies | opcao_pelo_mei |
| `cnae_descricao` | companies | cnae_descricao |
| `porte` | companies | porte |
| `email_receita` | companies | email_receita |
| `telefone_receita` | companies | telefone_receita |
| `socios` | companies | socios (JSONB) |
| `tem_site` | companies | tem_site |
| `tem_instagram` | companies | tem_instagram |
| `tem_maps` | companies | tem_maps |
| `tem_ads` | companies | tem_ads |
| `maps_rating` | companies | maps_rating |
| `maps_reviews` | companies | maps_reviews |
| `maps_address` | companies | maps_address |
| `maps_phone` | companies | maps_phone |
| `site_url` | companies | site_url |
| `instagram_url` | companies | instagram |
| `facebook_url` | companies | facebook |
| `site_emails` | companies | site_emails (JSONB) |
| `site_phones` | companies | site_phones (JSONB) |
| `site_instagram` | companies | site_instagram |
| `site_facebook` | companies | site_facebook |
| `site_youtube` | companies | site_youtube |
| `site_tiktok` | companies | site_tiktok |
| `score` | leads | score |
| `ia_analise` | leads | ia_analise |
| `niche` (do summary) | companies | niche |
| `city` (do summary) | companies | city |
| `state` (do summary) | companies | state |
| `query_variations` | search_sessions | query_variations (JSONB) |
| `ia_market_analysis` | search_sessions | ia_market_analysis |

### 5.4 Checklist de Migração
- [ ] Ler todos os JSON files do Prospector
- [ ] Para cada JSON: criar search_session
- [ ] Para cada lead: criar/atualizar company (dedup)
- [ ] Para cada lead: criar lead + search_lead
- [ ] Preservar ia_analise e ia_market_analysis
- [ ] Preservar scores calculados
- [ ] Preservar timestamps (usar `timestamp` do summary)
- [ ] Log de erros/avisos
- [ ] Validação pós-migração: contar registros, comparar totais

---

## 6. Segurança

### 6.1 Autenticação
- Senhas: `password_hash()` com `PASSWORD_BCRYPT` (cost 12)
- Sessão: PHP native, `session.cookie_httponly = 1`, `session.cookie_samesite = Strict`
- Session ID: regenerado após login
- Timeout: 2h de inatividade (configurável)
- API: Bearer token no header, 64 chars random

### 6.2 CSRF
- Token por sessão, validado em POST/PUT/DELETE
- Campo hidden: `<input type="hidden" name="_csrf" value="{{csrf}}">`
- AJAX: token no header `X-CSRF-Token`

### 6.3 SQL Injection
- 100% PDO prepared statements (nenhuma query com concatenação)
- Base Model não aceita strings cruas em where

### 6.4 XSS
- Output: `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` em todas as views
- Helper `e()` como alias: `<?= e($company['name']) ?>`
- JSON responses: `json_encode()` (já escapa)
- CSP header: `Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net`

### 6.5 Rate Limiting (Redis)
```php
class RateLimiter {
    public static function check(string $key, int $maxAttempts, int $decaySeconds): bool {
        $redis = RedisService::getConnection();
        $current = (int) $redis->incr($key);
        if ($current === 1) {
            $redis->expire($key, $decaySeconds);
        }
        return $current <= $maxAttempts;
    }
}

// Uso:
// Login: 5 tentativas / 15 min por IP
if (!RateLimiter::check("login:{$_SERVER['REMOTE_ADDR']}", 5, 900)) {
    Response::abort(429, 'Muitas tentativas. Tente novamente em 15 minutos.');
}

// Search: 10 buscas / hora por user
if (!RateLimiter::check("search:{$userId}", 10, 3600)) {
    Response::abort(429, 'Limite de buscas atingido. Tente em 1 hora.');
}
```

### 6.6 Audit Log
Todas as ações sensíveis são logadas:
- Login (sucesso/falha)
- CRUD de leads, companies, templates
- Exportação de dados
- Alteração de configurações
- Acesso a API

```php
AuditLog::create([
    'user_id'     => Auth::user()['id'],
    'action'      => 'login',
    'entity_type' => 'user',
    'entity_id'   => Auth::user()['id'],
    'details'     => json_encode(['method' => 'password']),
    'ip'          => $_SERVER['REMOTE_ADDR'],
]);
```

### 6.7 HTTPS
- Docker: Nginx com certbot (Let's Encrypt) em produção
- Redirecionamento HTTP → HTTPS
- HSTS header: `Strict-Transport-Security: max-age=31536000; includeSubDomains`

### 6.8 Outros
- Upload: apenas CSV, máx 5MB, validação MIME type
- Directory traversal: todas as views/templates usam paths fixos
- Error handling: em produção, erros 500 não expõem stack trace
- Headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`

---

## 7. Cronograma

### Fase 0: Core + Auth + DB + Docker (1 semana)
**Objetivo:** Sistema rodando com autenticação e banco

- [ ] Docker Compose (Nginx + PHP-FPM + PostgreSQL + Redis)
- [ ] Nginx config (rewrite rules, HTTPS)
- [ ] PHP config (opcache, upload limits)
- [ ] Core MVC: Router, Controller, Model, View, Request, Response
- [ ] Core: Session, Auth, Csrf, Validator, Flash, Helper
- [ ] Middleware: Auth, Csrf, RateLimit
- [ ] Database: migrations runner, todas as 12 migrations
- [ ] Seed: admin user, pipeline stages
- [ ] Auth: login, logout, registro, perfil
- [ ] Layout: sidebar, header, flash messages
- [ ] Testes: login, CRUD básico, CSRF

**Entregável:** Sistema rodando em `docker-compose up`, login funcionando, migrations aplicadas.

---

### Fase 1: Prospecção + Leads + Migração (1.5 semana)
**Objetivo:** Pipeline de prospecção funcional + dados migrados

- [ ] Services: SerperService, BrasilApiService, OllamaService
- [ ] Services: EnrichmentService (scraping), ScoringService, QueryService, DeduplicationService
- [ ] ApiController: search, enrich, score, market, leads, runAll
- [ ] RedisService: cache + rate limiting
- [ ] View: prospec/search.php (form + resultados + pipeline steps)
- [ ] View: prospec/history.php
- [ ] LeadController: CRUD completo (index, create, show, edit, destroy)
- [ ] Lead: import CSV, export CSV
- [ ] Lead: enrich, diagnostic (IA)
- [ ] Script: migrate_json.php
- [ ] Testes: busca, enrichment, scoring, migração

**Entregável:** Prospecção end-to-end, leads CRUD, dados do Prospector importados.

---

### Fase 2: Pipeline + Contatos (1 semana)
**Objetivo:** Kanban + timeline de contatos

- [ ] PipelineController: index, move, automove
- [ ] View: pipeline/index.php (Kanban drag&drop com Alpine.js)
- [ ] Kanban JS: dragstart, dragover, drop, atualização via AJAX
- [ ] Pipeline: gerenciamento de estágios (admin)
- [ ] ContactController: timeline, addActivity, whatsapp, email, note
- [ ] LeadActivity model + timeline
- [ ] EmailService (PHPMailer + SMTP)
- [ ] WhatsApp: gerar link
- [ ] View: leads/show.php (aba contatos/timeline)
- [ ] Testes: mover lead, registrar atividade, enviar email

**Entregável:** Pipeline Kanban funcional, timeline de contatos.

---

### Fase 3: Templates + Agenda (0.5 semana)
**Objetivo:** Templates de mensagem + agenda

- [ ] TemplateController: CRUD completo
- [ ] View: templates/index.php, templates/form.php
- [ ] Sistema de variáveis: parser `{{variavel}}`
- [ ] Preview de template
- [ ] AgendaController: index, calendar, store, complete
- [ ] View: agenda/index.php, agenda/calendar.php
- [ ] Follow-up automático (3 dias após contato)
- [ ] Notificação: tarefas vencendo
- [ ] Testes: criar template, substituir variáveis, criar tarefa

**Entregável:** Templates por nicho, agenda com follow-up.

---

### Fase 4: Dashboard + Relatórios (0.5 semana)
**Objetivo:** Dashboard com KPIs + relatórios

- [ ] DashboardController: index
- [ ] View: dashboard/index.php
- [ ] KPIs: leads por estágio, taxa conversão, valor pipeline, tarefas pendentes
- [ ] Gráficos: Chart.js (funil, pizza, barras)
- [ ] ReportController: conversion, ranking, roi, team
- [ ] View: reports/conversion.php, reports/ranking.php
- [ ] Filtros por período (7d, 30d, 90d, all)
- [ ] Export: relatórios em PDF (opcional, wkhtmltopdf)
- [ ] Testes: cálculos de conversão, ranking

**Entregável:** Dashboard informativo, relatórios de conversão e ranking.

---

### Fase 5: API + Config (0.5 semana)
**Objetivo:** API REST + configurações finais

- [ ] ApiController: endpoints REST (leads, companies, search, pipeline, reports)
- [ ] Autenticação via Bearer token
- [ ] Settings: profile, integrations, team management
- [ ] View: settings/profile.php, settings/integrations.php, settings/team.php
- [ ] Admin: criar/editar/desativar users
- [ ] API keys: CRUD de chaves (Serper, Ollama, SMTP)
- [ ] Documentação API: Swagger/OpenAPI (básico)
- [ ] Testes: API endpoints, auth token, settings

**Entregável:** API funcional, painel de configurações completo.

---

**Total estimado: 4 semanas**

| Fase | Duração | Semana |
|------|---------|--------|
| 0 | 1 semana | 1 |
| 1 | 1.5 semana | 1-2 |
| 2 | 1 semana | 3 |
| 3 | 0.5 semana | 3-4 |
| 4 | 0.5 semana | 4 |
| 5 | 0.5 semana | 4 |

---

## 8. Frontend

### 8.1 Layout Principal

```
┌──────────────────────────────────────────────────────┐
│  🔍 ProspecCRM          [User] [🔔] [⚙️]             │ ← Header
├──────────┬───────────────────────────────────────────┤
│          │                                           │
│  📊 Dash │   Content Area                            │
│  🔍 Prosp│                                           │
│  👥 Leads│   (views renderizadas aqui)               │
│  📋 Pipe │                                           │
│  ✉️ Tmpl │                                           │
│  📅 Agen │                                           │
│  📈 Rel  │                                           │
│  ⚙️ Conf │                                           │
│          │                                           │
│          │                                           │
│  ──────  │                                           │
│  🚪 Sair │                                           │
│          │                                           │
└──────────┴───────────────────────────────────────────┘
```

### 8.2 Dark Theme (Tailwind)

```html
<!-- Layout base -->
<body class="bg-gray-950 text-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 border-r border-gray-800 min-h-screen p-4 flex flex-col">
            <div class="mb-8">
                <h1 class="text-xl font-bold bg-gradient-to-r from-purple-400 to-cyan-400 bg-clip-text text-transparent">
                    🔍 ProspecCRM
                </h1>
            </div>
            <nav class="flex-1 space-y-1">
                <a href="/" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 transition">
                    📊 Dashboard
                </a>
                <!-- ... mais itens ... -->
            </nav>
            <div class="border-t border-gray-800 pt-4">
                <a href="/logout" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-800 text-gray-400">
                    🚪 Sair
                </a>
            </div>
        </aside>
        
        <!-- Main content -->
        <main class="flex-1 p-6">
            <!-- Flash messages -->
            <!-- Page content -->
        </main>
    </div>
</body>
```

### 8.3 Componentes

**Card:**
```html
<div class="bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-purple-500/50 transition">
    <h3 class="text-lg font-semibold mb-2"><?= e($company['name']) ?></h3>
    <p class="text-gray-400 text-sm"><?= e($company['niche']) ?> · <?= e($company['city']) ?></p>
</div>
```

**Tabela:**
```html
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-900/50 text-gray-400">
            <tr>
                <th class="px-4 py-3 text-left">Nome</th>
                <th class="px-4 py-3 text-left">Score</th>
                <th class="px-4 py-3 text-left">Estágio</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            <!-- rows -->
        </tbody>
    </table>
</div>
```

**Kanban:**
- Alpine.js `x-data` para estado
- HTML5 Drag & Drop API
- Cards arrastáveis entre colunas
- Atualização via `fetch()` PUT

**Forms:**
```html
<form method="POST" action="/leads" class="space-y-4">
    <?= Csrf::field() ?>
    <div>
        <label class="block text-sm font-medium text-gray-400 mb-1">Nome da Empresa</label>
        <input type="text" name="name" required
               class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 
                      focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
    </div>
    <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2.5 rounded-lg font-semibold transition">
        Salvar
    </button>
</form>
```

**Modal (Alpine.js):**
```html
<div x-data="{ show: false }">
    <button @click="show = true" class="text-purple-400 hover:text-purple-300">Abrir</button>
    <div x-show="show" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div @click.outside="show = false" class="bg-gray-900 border border-gray-700 rounded-xl p-6 max-w-lg w-full">
            <!-- modal content -->
        </div>
    </div>
</div>
```

### 8.4 Mobile-First
- Sidebar: colapsa em hamburger menu abaixo de 768px
- Tabelas: scroll horizontal em mobile
- Kanban: scroll horizontal em mobile (colunas empilhadas verticalmente em portrait)
- Forms: full-width em mobile
- Cards: full-width, stack vertical

### 8.5 Alpine.js Global Store
```javascript
// public/assets/js/app.js
document.addEventListener('alpine:init', () => {
    Alpine.store('app', {
        user: null,
        loading: false,
        
        async init() {
            // Fetch user data
        },
        
        async api(method, url, data = {}) {
            this.loading = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': token,
                    },
                    body: method !== 'GET' ? JSON.stringify(data) : undefined,
                });
                return await res.json();
            } finally {
                this.loading = false;
            }
        },
        
        flash(type, message) {
            // Toast notification
        },
    });
});
```

---

## 9. Integrações

### 9.1 Serper API (Google Search + Places)
```php
class SerperService {
    private string $apiKey;
    private string $baseUrl = 'https://google.serper.dev';
    
    public function search(string $query): array {
        return $this->call('/search', ['q' => $query, 'gl' => 'br', 'hl' => 'pt-br']);
    }
    
    public function places(string $query): array {
        return $this->call('/places', ['q' => $query, 'gl' => 'br', 'hl' => 'pt-br']);
    }
    
    private function call(string $endpoint, array $payload): array {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new RuntimeException("Serper API error: HTTP {$httpCode}");
        }
        
        return json_decode($response, true) ?: [];
    }
}
```

### 9.2 BrasilAPI (CNPJ)
```php
class BrasilApiService {
    private string $baseUrl = 'https://brasilapi.com.br/api/cnpj/v1';
    
    public function lookupCnpj(string $cnpj): ?array {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $ch = curl_init("{$this->baseUrl}/{$cnpj}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $code === 200 ? json_decode($response, true) : null;
    }
}
```

### 9.3 Ollama Cloud (IA)
```php
class OllamaService {
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private array $fallbacks;
    
    public function chat(string $prompt, string $systemPrompt = null, int $timeout = 90): string {
        $models = array_unique(array_merge([$this->model], $this->fallbacks));
        
        foreach ($models as $model) {
            try {
                $payload = [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt ?? 'Você é um consultor de negócios especialista em prospecção B2B. Responda sempre em português brasileiro.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                ];
                
                $ch = curl_init("{$this->baseUrl}/chat/completions");
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $this->apiKey,
                        'Content-Type: application/json',
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                ]);
                $response = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($code === 200) {
                    $data = json_decode($response, true);
                    $content = $data['choices'][0]['message']['content'] ?? '';
                    if (strlen($content) > 20) {
                        return $content;
                    }
                }
            } catch (Exception $e) {
                error_log("Ollama {$model} error: " . $e->getMessage());
            }
        }
        
        return 'IA temporariamente indisponível. Dados cadastrais e score foram calculados normalmente.';
    }
}
```

### 9.4 WhatsApp
- Não envia mensagens diretamente
- Gera link de redirecionamento: `https://wa.me/55{DDD}{numero}?text={mensagem_urlencoded}`
- Se telefone tem 9 dígitos (celular), usa `55{DDD}9{numero}`
- Templates do tipo "whatsapp" limitados a 200 chars

### 9.5 SMTP (Email)
```php
class EmailService {
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $fromName;
    private string $fromEmail;
    
    public function send(string $to, string $subject, string $body, ?string $html = null): bool {
        // Usar PHPMailer ou Symfony Mailer (via composer)
        // Fallback: mail() nativo
        $headers = [
            'From' => "{$this->fromName} <{$this->fromEmail}>",
            'Reply-To' => $this->fromEmail,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
        ];
        
        return mail($to, $subject, $html ?? nl2br(e($body)), implode("\r\n", array_map(
            fn($k, $v) => "$k: $v", array_keys($headers), array_values($headers)
        )));
    }
}
```

### 9.6 Redis
```php
class RedisService {
    private static ?Redis $instance = null;
    
    public static function getConnection(): Redis {
        if (!self::$instance) {
            self::$instance = new Redis();
            self::$instance->connect(
                config('redis.host', '127.0.0.1'),
                config('redis.port', 6379)
            );
            self::$instance->select(config('redis.db', 0));
        }
        return self::$instance;
    }
    
    // Cache helpers
    public static function remember(string $key, int $ttl, callable $callback) {
        $redis = self::getConnection();
        if ($cached = $redis->get($key)) {
            return json_decode($cached, true);
        }
        $value = $callback();
        $redis->setex($key, $ttl, json_encode($value));
        return $value;
    }
    
    // Rate limiting
    public static function throttle(string $key, int $maxAttempts, int $decaySeconds): bool {
        $redis = self::getConnection();
        $current = $redis->incr($key);
        if ($current === 1) {
            $redis->expire($key, $decaySeconds);
        }
        return $current <= $maxAttempts;
    }
}
```

### 9.7 Configuração das Chaves (docker-compose.yml)
```yaml
services:
  app:
    build: .
    environment:
      - DB_HOST=postgres
      - DB_NAME=prospec_crm
      - DB_USER=prospec
      - DB_PASS=${DB_PASS:-prospec123}
      - REDIS_HOST=redis
      - SERPER_KEY=${SERPER_KEY}
      - OLLAMA_KEY=${OLLAMA_KEY}
      - OLLAMA_BASE=https://ollama.com/v1
      - OLLAMA_MODEL=glm-5.1
      - SMTP_HOST=${SMTP_HOST}
      - SMTP_PORT=${SMTP_PORT:-587}
      - SMTP_USER=${SMTP_USER}
      - SMTP_PASS=${SMTP_PASS}
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:16
    environment:
      - POSTGRES_DB=prospec_crm
      - POSTGRES_USER=prospec
      - POSTGRES_PASSWORD=prospec123
    volumes:
      - pgdata:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  nginx:
    image: nginx:1.25-alpine
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

volumes:
  pgdata:
```

---

## 10. Considerações Finais

### 10.1 Performance
- Opcache habilitado no PHP-FPM
- Redis cache para queries frequentes (dashboard KPIs, pipeline counts)
- Database indexes cobrem todas as queries comuns
- Nginx gzipping ativo
- CDN (jsdelivr) para Tailwind + Alpine.js

### 10.2 Escalabilidade
- Arquitetura stateless (sessão em Redis, não em files)
- PostgreSQL suporta milhões de registros
- Redis para rate limiting distribuído
- Horizontal scaling: múltiplos PHP-FPM containers atrás de Nginx

### 10.3 Monitoramento
- PHP error_log → `storage/logs/`
- Nginx access/error logs
- PostgreSQL slow queries (log_min_duration_statement = 500)
- Redis: monitor via `redis-cli info`
- Docker: healthchecks nos containers

### 10.4 Backup
- PostgreSQL: `pg_dump` diário (cron no container)
- Redis: RDB snapshot a cada 15 min
- Uploads: volume Docker com backup em S3 (opcional)

### 10.5 Deploy
```bash
# Desenvolvimento
docker-compose up -d

# Produção
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Migração
docker-compose exec app php database/migrate.php
docker-compose exec app php scripts/migrate_json.php /path/to/data/

# Seed
docker-compose exec app php scripts/seed.php
```

---

*Documento gerado em 2026-04-21 — Versão 1.0*
*Este plano é o guia de implementação do CRM Prospec. Cada seção deve ser seguida na ordem das fases.*