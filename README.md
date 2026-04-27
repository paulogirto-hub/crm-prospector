# Prospec CRM

> Sistema de Prospecção Comercial B2B — gerencie leads, pipeline e prospecção inteligente com IA.

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=flat&logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?style=flat&logo=docker&logoColor=white)

---

## 📋 Descrição

O **Prospec CRM** é um sistema de gestão de prospecção comercial B2B desenvolvido em PHP 8.2+ com Laravel-like architecture. Ele permite que equipes comerciais gerenciem leads, visualizem pipelines em formato Kanban, automatizem tarefas e integrem inteligência artificial para enriquecimento e análise de dados de prospecção.

---

## 🧰 Stack Técnica

| Tecnologia   | Detalhes                          |
|--------------|-----------------------------------|
| **PHP**      | 8.2+ (FPM-Alpine)                 |
| **PostgreSQL** | 16 (banco de dados principal)    |
| **Redis**    | 7 (cache e sessões)               |
| **Nginx**    | 1.25 (reverse proxy / web server)|
| **Docker**   | Docker Compose para orquestração  |
| **IA**       | Integração Ollama (GLM-5.1)      |

### Estrutura de Diretórios

```
crm-prospector/
├── app/
│   ├── Controllers/    # Lógica de controllers
│   ├── Core/          # Router, functions, helpers
│   ├── Middleware/    # Auth, CSRF, Rate Limit, Admin
│   └── ...
├── config/            # Configurações da aplicação
├── database/          # Migrations
├── docker/            # Dockerfiles e configurações
├── docs/              # Documentação adicional
├── migrations/        # Schema do banco
├── public/            # Entry point
├── scripts/           # Scripts de seed e migração
├── storage/           # Logs, sessions, uploads
└── tests/             # Testes PHPUnit
```

---

## ✨ Features Principais

### 🔐 Autenticação & Segurança
- Login / Logout / Registro de usuários
- Middleware de autenticação, CSRF e rate limiting
- Recuperação de senha via token
- Proteção LGPD (exportação e exclusão de dados do usuário)

### 📊 Dashboard
- Visão geral da situação comercial
- Atividades recentes e métricas-chave

### 👥 Gestão de Leads
- CRUD completo de leads
- Histórico de atividades e estágios
- Sistema de pipeline Kanban
- Movimentação de leads entre estágios

### 🏢 Gestão de Empresas
- Cadastro e edição de empresas
- Vinculação com leads

### 📅 Agenda de Tarefas
- Criação de tarefas e lembretes
- Marcar tarefas como concluídas

### 🔍 Prospecção Inteligente (com IA)
- Busca inteligente via Prospector API
- Enriquecimento de dados com IA (Ollama)
- Scoragem e análise de leads
- Importação de leads enriquecidos para o CRM
- Diagnóstico de leads individuais
- Análise de mercado
- Histórico de sessões de prospecção
- Exportação de resultados

### 📄 Templates
- Gerenciamento de templates de comunicação
- CRUD completo de templates

### 📈 Relatórios
- Visão analítica do funil de vendas

### ⚙️ Configurações (Admin)
- Gerenciamento de etapas do pipeline (estágios)
- Gestão de equipe

### 📄 Páginas Legais
- Termos de Uso
- Política de Privacidade

---

## 🚀 Como Instalar

### Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) e Docker Compose
- Git

### 1. Clone o repositório

```bash
git clone https://github.com/paulogirto-hub/crm-prospector.git
cd crm-prospector
```

### 2. Configure as variáveis de ambiente

Copie o arquivo de exemplo ou defina as variáveis no `.env`:

```bash
# Configurações do banco
DB_NAME=prospec_crm
DB_USER=prospec
DB_PASS=prospec123

# Segurança
APP_SECRET=change_me_in_production
APP_ENV=production

# Opcional — IA
OLLAMA_KEY=
OLLAMA_BASE=https://ollama.com/v1
OLLAMA_MODEL=glm-5.1

# Opcional — Prospector API
SERPER_KEY=
```

### 3. Suba os containers

```bash
docker compose up -d
```

Isso irá iniciar:
- **php-fpm** na rede interna
- **nginx** nas portas 8080 (HTTP) e 8089 (HTTPS)
- **postgres** na porta 5433 local
- **redis** na porta 6380 local

### 4. Rode as migrations

```bash
docker compose exec php-fpm php database/migrate.php
```

### 5. (Opcional) Seed do banco

```bash
docker compose exec php-fpm php scripts/seed.php
```

### 6. Acesse a aplicação

- **HTTP:** http://localhost:8090
- **HTTPS:** https://localhost:8089

---

## ⚙️ Como Configurar

### Variáveis de Ambiente

| Variável        | Descrição                              | Padrão                  |
|-----------------|----------------------------------------|-------------------------|
| `DB_HOST`       | Host do banco PostgreSQL               | `postgres`              |
| `DB_PORT`       | Porta do PostgreSQL                    | `5432`                  |
| `DB_NAME`       | Nome do banco de dados                 | `prospec_crm`           |
| `DB_USER`       | Usuário do banco                       | `prospec`               |
| `DB_PASS`       | Senha do banco                         | `prospec123`            |
| `REDIS_HOST`    | Host do Redis                          | `redis`                 |
| `REDIS_PORT`    | Porta do Redis                         | `6379`                  |
| `APP_SECRET`    | Chave secreta da aplicação             | `change_me_in_production`|
| `APP_ENV`       | Ambiente (`production` / `development`)| `production`            |
| `OLLAMA_KEY`    | Chave da API Ollama                    | (vazio)                 |
| `OLLAMA_BASE`   | URL base da API Ollama                 | `https://ollama.com/v1` |
| `OLLAMA_MODEL`  | Modelo Ollama para IA                  | `glm-5.1`               |
| `SERPER_KEY`    | Chave da API Serper (Prospector)       | (vazio)                 |

### Configuração do Nginx

O arquivo de configuração do Nginx está em `docker/nginx/default.conf`. Ajustes de porta e SSL podem ser feitos ali.

---

## 🏃 Como Rodar

### Subir a aplicação

```bash
docker compose up -d
```

### Verificar status dos serviços

```bash
docker compose ps
```

### Ver logs

```bash
# Todos os serviços
docker compose logs -f

# Apenas PHP
docker compose logs -f php-fpm

# Apenas Nginx
docker compose logs -f nginx
```

### Rodar testes

```bash
docker compose exec php-fpm composer test
# ou
docker compose exec php-fpm ./vendor/bin/phpunit
```

### Parar a aplicação

```bash
docker compose down
```

---

## 🌐 Rotas da API (Web)

### Health

| Método | Rota         | Descrição              |
|--------|-------------|------------------------|
| GET    | `/health`   | Health check           |
| GET    | `/ready`    | Readiness check        |

### Autenticação

| Método | Rota               | Descrição                    |
|--------|-------------------|------------------------------|
| GET    | `/login`          | Formulário de login          |
| POST   | `/login`          | Efetuar login                |
| GET    | `/register`       | Formulário de registro       |
| POST   | `/register`       | Criar conta                  |
| GET    | `/signup`         | Self-registration (público)   |
| POST   | `/signup`         | Registrar-se                 |
| POST   | `/logout`         | Logout                       |
| GET    | `/profile`        | Perfil do usuário            |
| POST   | `/profile`        | Atualizar perfil             |
| GET    | `/forgot-password`| Form de recuperação de senha  |
| POST   | `/forgot-password`| Solicitar recuperação         |
| GET    | `/reset-password` | Form de reset de senha       |
| POST   | `/reset-password` | Resetar senha                |

### Dashboard

| Método | Rota           | Descrição              |
|--------|---------------|------------------------|
| GET    | `/`           | Dashboard principal    |
| GET    | `/dashboard`  | Dashboard principal    |

### Leads

| Método | Rota                        | Descrição                    |
|--------|----------------------------|------------------------------|
| GET    | `/leads`                   | Listar leads                 |
| GET    | `/leads/create`            | Form de criação              |
| POST   | `/leads`                   | Criar lead                   |
| GET    | `/leads/{id}`              | Detalhar lead                |
| GET    | `/leads/{id}/edit`         | Form de edição               |
| POST   | `/leads/{id}`              | Atualizar lead               |
| PUT    | `/leads/{id}`              | Atualizar lead (PUT)         |
| POST   | `/leads/{id}/stage`        | Mover lead de estágio        |
| POST   | `/leads/{id}/activity`     | Adicionar atividade           |
| POST   | `/leads/{id}/delete`       | Deletar lead                 |
| DELETE | `/leads/{id}`              | Deletar lead (DELETE)        |

### Pipeline

| Método | Rota                    | Descrição              |
|--------|------------------------|------------------------|
| GET    | `/pipeline`            | Visualizar Kanban      |
| POST   | `/pipeline/move/{id}`  | Mover lead no Kanban   |

### Prospecção (IA)

| Método | Rota                                     | Descrição                    |
|--------|------------------------------------------|------------------------------|
| GET    | `/prospec`                               | Painel de prospecção         |
| POST   | `/prospec/search`                        | Buscar prospects             |
| GET    | `/prospec/session/{id}`                  | Detalhe da sessão            |
| GET    | `/prospec/session/{id}/status`           | Status da sessão             |
| POST   | `/prospec/enrich/{id}`                   | Enriquecer com IA            |
| POST   | `/prospec/score/{id}`                    | Scorar lead com IA           |
| POST   | `/prospec/analyze/{id}`                  | Analisar                     |
| POST   | `/prospec/session/{id}/analyze-lead`     | Analisar lead específico     |
| POST   | `/prospec/import/{id}`                   | Importar resultados          |
| POST   | `/prospec/import-lead/{sid}/{lid}`       | Importar lead específico     |
| POST   | `/prospec/session/{id}/diagnose/{lid}`   | Diagnosticar lead            |
| POST   | `/prospec/session/{id}/analyze-market`   | Analisar mercado             |
| GET    | `/prospec/session/{id}/lead/{lid}`       | Detalhe do lead na sessão    |
| GET    | `/prospec/export/{id}`                   | Exportar sessão              |
| GET    | `/prospec/history`                       | Histórico de sessões         |

### Templates

| Método | Rota                         | Descrição              |
|--------|------------------------------|------------------------|
| GET    | `/templates`                 | Listar templates       |
| GET    | `/templates/create`         | Form de criação        |
| POST   | `/templates`                | Criar template         |
| GET    | `/templates/{id}/edit`      | Form de edição         |
| POST   | `/templates/{id}`           | Atualizar template     |
| PUT    | `/templates/{id}`           | Atualizar (PUT)        |
| POST   | `/templates/{id}/delete`    | Deletar template       |
| DELETE | `/templates/{id}`            | Deletar (DELETE)       |

### Agenda

| Método | Rota                        | Descrição              |
|--------|-----------------------------|------------------------|
| GET    | `/agenda`                   | Listar tarefas         |
| GET    | `/agenda/create`           | Form de criação        |
| POST   | `/agenda`                   | Criar tarefa           |
| POST   | `/agenda/{id}/complete`    | Marcar como concluída  |
| POST   | `/agenda/{id}/delete`      | Deletar tarefa        |

### Empresas

| Método | Rota                      | Descrição              |
|--------|---------------------------|------------------------|
| GET    | `/companies`             | Listar empresas        |
| GET    | `/companies/create`      | Form de criação        |
| POST   | `/companies`             | Criar empresa         |
| GET    | `/companies/{id}`        | Detalhar empresa       |
| GET    | `/companies/{id}/edit`   | Form de edição         |
| POST   | `/companies/{id}`       | Atualizar empresa      |
| PUT    | `/companies/{id}`       | Atualizar (PUT)        |

### Relatórios

| Método | Rota        | Descrição              |
|--------|------------|------------------------|
| GET    | `/reports` | Painel de relatórios   |

### Configurações (Admin)

| Método | Rota                                  | Descrição                |
|--------|---------------------------------------|--------------------------|
| GET    | `/settings`                          | Painel de configurações  |
| GET    | `/settings/team`                     | Gestão de equipe         |
| POST   | `/settings/stage/create`             | Criar estágio            |
| POST   | `/settings/stage/{id}`               | Editar estágio           |
| PUT    | `/settings/stage/{id}`               | Editar (PUT)             |
| POST   | `/settings/stage/{id}/delete`        | Deletar estágio          |
| DELETE | `/settings/stage/{id}`               | Deletar (DELETE)         |

### Legal (Públicas)

| Método | Rota        | Descrição              |
|--------|------------|------------------------|
| GET    | `/terms`   | Termos de Uso          |
| GET    | `/privacy` | Política de Privacidade|

### Conta (LGPD)

| Método | Rota                   | Descrição                      |
|--------|-----------------------|--------------------------------|
| GET    | `/account/delete`    | Form de exclusão de conta      |
| POST   | `/account/delete`    | Solicitar exclusão             |
| GET    | `/account/export`    | Form de exportação de dados     |
| POST   | `/account/export`    | Baixar exportação dos dados    |

---

## 👥 Contribuidores

- **Paulo Girto** — [@paulogirto-hub](https://github.com/paulogirto-hub)

---

## 📄 Licença

Este projeto está sob a licença **MIT**. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

## 📚 Documentação Adicional

- `PLAN.md` — Plano de implementação detalhado
- `IMPLEMENTATION_PLAN.md` — Plano de execução
- `META-FRAMEWORK-GAP-ANALYSIS.md` — Análise de gaps
- `AUDIT_REPORT.md` — Relatório de auditoria
- `docs/` — Documentação complementar
