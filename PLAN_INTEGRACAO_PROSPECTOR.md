# Plano de Integração — Prospector + Prospec CRM

**Versão:** 1.0  
**Data:** 2026-04-27  
**Objetivo:** Colocar o Prospector online e integrá-lo corretamente ao Prospec CRM para que a prospecção de leads funcione.

---

## 1. Diagnóstico — Por Que a Prospecção Não Funciona

### Estado Atual

```
Prospector containers:  STOPPED (antes do debug)
Prospec CRM:           ONLINE  em 185.139.1.41:8089/8090
Rede Prospector:        isolada — prospector_net (rede separada)
Rede Prospec CRM:       prospec-crm_prospec-net (rede bridge)
```

### Problema Central

O Prospec CRM chama `http://185.139.1.41:8088/api`, mas:

1. **Rede isolada** — `prospector_net` é uma rede Docker bridge separada. O Prospec CRM (`prospec-crm_prospec-net`) **não consegue alcançar** `185.139.1.41:8088` porque estão em redes Docker diferentes. Só o host consegue reachar as portas do Prospector.
2. **Containers parados** — Antes do debug, `prospector-backend-1` e `prospector-frontend-1` estavam com status `Exited` (STOPPED).
3. **Validação:** durante o debug, os containers foram levantados e o backend respondeu com health check OK em `localhost:5000`, e o frontend em `localhost:8088/api/health` retornou `{"success":true}`.

### Conclusão

A prospecção não funciona porque **rede + containers parados**. O acesso via `185.139.1.41:8088` do container CRM é impossível sem integração de rede. Precisamos de uma das soluções da seção 3.

---

## 2. Arquitetura Atual vs. Arquitetura Proposta

### Arquitetura Atual (problema)

```
┌──────────────────────────────────────────────────┐
│  prospec-crm_prospec-net                          │
│  ┌─────────────┐                                  │
│  │  CRM PHP    │──calls──> http://185.x.x.x:8088   │
│  │  Laravel    │          (REDE NEGADA ✗)         │
│  └─────────────┘                                  │
└──────────────────────────────────────────────────┘
┌──────────────────────────────────────────────────┐
│  prospector_net (isolada)                         │
│  ┌────────────┐  ┌──────────────┐                │
│  │  Backend   │  │  Frontend    │  STOPPED ✗    │
│  │  :5000     │  │  :8088       │                │
│  └────────────┘  └──────────────┘                │
└──────────────────────────────────────────────────┘
```

### Arquitetura Proposta (Opção B — network join, recomendada)

```
┌─────────────────────────────────────────────────────────────────┐
│  prospec-crm_prospec-net                                          │
│  ┌─────────────┐   ┌─────────────┐  ┌─────────────────────────┐  │
│  │  CRM PHP    │   │  Postgres   │  │  prospector_net         │  │
│  │  Laravel   │   │  :5432      │  │  ┌────────────┐         │  │
│  │            │◄──│             │  │  │ Prospector │:5000    │  │
│  └─────────────┘   └─────────────┘  │  │ Backend   │         │  │
│                                     │  │ Prospector│:8088    │  │
│                                     │  │ Frontend  │         │  │
│                                     │  └────────────┘         │  │
│                                     └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

**Mudanças-chave:**
- Prospector ingressa na rede `prospec-crm_prospec-net`
- CRM acessa Prospector via **nome do container** (`http://prospector-backend:5000/api`) ou `http://prospector-frontend:80/api`
- Prospec CRM **não precisa mais expor porta 8088 no host** (pode remover)

---

## 3. Opções de Integração

| Opção | Como funciona | Vantagens | Desvantagens |
|---|---|---|---|
| **A. Network Join (recomendada)** | Prospector ingressa na rede `prospec-crm_prospec-net` | Simples, sem Traefik, comunicação interna Docker | Ambas stacks na mesma rede |
| **B. Traefik como gateway** | Traefik descobre Prospector via label e expõe rotas | Mais flexível, desacoplado | Mais complexo, mais um container |
| **C. Host mode** | Prospector usa `network_mode: host` | Sem bridge overhead | Não funciona em todos os ambientes, conflita portas |

**Recomendada: Opção A (Network Join)** — simplicidade x benefício.

### 3.1 Implementação — Opção A (Network Join)

**Arquivo:** `/root/.openclaw/workspace/main/prospector/docker-compose.yml`

```yaml
version: "3.8"

services:
  backend:
    build: ./backend
    environment:
      - SERPER_KEY=${SERPER_KEY}
      - OLLAMA_KEY=${OLLAMA_KEY}
      - OLLAMA_BASE=${OLLAMA_BASE:-https://ollama.com/v1}
      - OLLAMA_MODEL=${OLLAMA_MODEL:-glm-5.1}
      - DATA_DIR=/app/data
    volumes:
      - prospector_data:/app/data
    networks:
      - prospec-crm_prospec-net   # ← entra na rede do CRM
    restart: unless-stopped
    ports:
      - "5000:5000"               # mantém acesso host (dev/debug)
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.prospector-backend.rule=PathPrefix(`/api`)"
      - "traefik.http.services.prospector-backend.loadbalancer.server.port=5000"

  frontend:
    build:
      context: .
      dockerfile: frontend/Dockerfile
    ports:
      - "8088:80"                 # mantém acesso host (dev/debug)
    depends_on:
      - backend
    networks:
      - prospec-crm_prospec-net   # ← entra na rede do CRM
    restart: unless-stopped
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.prospector-frontend.rule=Host(`prospector.local`)"
      - "traefik.http.services.prospector-frontend.loadbalancer.server.port=80"

volumes:
  prospector_data:

networks:
  prospec-crm_prospec-net:
    external: true                # ← conecta na rede existente do CRM
```

> **Nota:** Antes de fazer `docker compose up`, a rede `prospec-crm_prospec-net` precisa existir. Verifique com:
> ```bash
> docker network ls | grep prospec-crm_prospec-net
> ```
> Se não existir, o Prospector não sobe ( `external: true` ). Nesse caso, usar a **Opção B** ou criar a rede primeiro.

### 3.2 Atualização do Prospec CRM

O CRM chama `http://185.139.1.41:8088/api`. Após a integração, atualizar para:

```php
// config/prospector.php (ou .env)
PROSPECTOR_BASE_URL=http://prospector-backend:5000
PROSPECTOR_API_KEY=${OLLAMA_KEY}
```

```php
// Exemplo de chamada no Laravel
$response = Http::get(env('PROSPECTOR_BASE_URL').'/api/health');
```

Ou manter o mesmo URL de externo via Traefik (Opção B).

---

## 4. Mudanças Detalhadas por Componente

### 4.1 Prospector (`/root/.openclaw/workspace/main/prospector/docker-compose.yml`)

| Campo | Antes | Depois |
|---|---|---|
| `networks` | `prospector_net` | `prospec-crm_prospec-net` (external) |
| `ports` | só mapeamento | mantém p/ debug local |
| `labels` | nenhum | labels Traefik (se usar) |

### 4.2 Prospec CRM — chamadas ao Prospector

| Antes | Depois |
|---|---|
| `http://185.139.1.41:8088/api` | `http://prospector-backend:5000/api` (interno) |
| Sem auth | adicionar `OLLAMA_KEY` se necessário |

### 4.3 Prospec CRM — .env

```bash
# Adicionar
PROSPECTOR_BASE_URL=http://prospector-backend:5000
```

### 4.4 Rede Docker

```bash
# Verificar rede existente
docker network ls | grep prospec-crm

# Se não existir, criar manualmente (raro — o CRM já cria)
docker network create prospec-crm_prospec-net
```

---

## 5. Ordem de Deploy

```
ANTES DE TUDO:
1. Verificar estado atual dos containers
   docker ps -a --filter "name=prospector"

2. Verificar redes Docker
   docker network ls | grep -E "prospec|prospector"

3. Testar连通性 do CRM ao Prospector (depois de integrar)
   docker exec prospec-crm-php curl -f http://prospector-backend:5000/api/health

PASSO A PASSO:

[1] — Backup do docker-compose atual
   cp /root/.openclaw/workspace/main/prospector/docker-compose.yml \
      /root/.openclaw/workspace/main/prospector/docker-compose.yml.bak

[2] — Atualizar docker-compose.yml do Prospector (seção 3.1)

[3] — Atualizar config do CRM (PROSPECTOR_BASE_URL)

[4] — Parar Prospector (se rodando)
   cd /root/.openclaw/workspace/main/prospector && docker compose down

[5] — Subir Prospector com nova rede
   cd /root/.openclaw/workspace/main/prospector && docker compose up -d

[6] — Verificar containers subiram
   docker ps --filter "name=prospector"

[7] — Testar health do backend
   curl http://localhost:5000/api/health

[8] — Testar连通性 do CRM
   docker exec prospec-crm-php curl -f http://prospector-backend:5000/api/health

[9] — Testar prospecção completa via CRM
   (_trigger uma prospecção manual e observar logs_)
   docker logs --tail=50 prospector-backend-1

[10] — Commit das mudanças
   git add docker-compose.yml
   git commit -m "feat: join prospec-crm_prospec-net for CRM integration"
```

---

## 6. Como Testar Que Funciona

### Teste 1 — Health check do backend
```bash
curl http://localhost:5000/api/health
# Esperado: {"success":true,"data":{...}}
```

### Teste 2 — Acessar frontend (para validar nginx)
```bash
curl http://localhost:8088/
# Esperado: HTML da página do Prospector
```

### Teste 3 — Conectividade CRM → Prospector
```bash
docker exec prospec-crm-php curl -f http://prospector-backend:5000/api/health
# Esperado: mesmo JSON do teste 1
```

### Teste 4 — Pipeline de prospecção
```bash
# Via API (se existir endpoint de trigger)
curl -X POST http://localhost:5000/api/prospect \
  -H "Content-Type: application/json" \
  -d '{"domain":" exemplo.com"}'

# Observar logs
docker logs --tail=100 prospector-backend-1
```

### Teste 5 — Through CRM
1. Abrir CRM em `185.139.1.41:8089`
2. Acessar módulo de prospecção
3. Inserir um domínio de teste
4. Verificar se o lead aparece enriquecido após processamento

---

## 7. Funcionalidades que Vão Melhorar

### 7.1 Busca e Descoberta
- Busca de empresas via **SerpAPI** (`SERPER_KEY`)
- Descoberta automática de cargos, e-mails, telefones
-查到Competidores, tecnologias, dados públicos

### 7.2 Enriquecimento de Dados
- Preenchimento automático de campos do lead (empresa, cargo, seniority)
- Scoring de e-mail (valididade)
- Dados de mercado (tamanho, segmento, receita estimada)

### 7.3 Scoring e Priorização
- Score de propensão à compra baseado em sinais digitais
- Qualificação automática do lead (Hot/Warm/Cold)
- Rankeamento por ICP (Ideal Customer Profile)

### 7.4 Análise com IA
- Geração de insights via **OLLAMA** (`glm-5.1`)
- Resumo automático de empresas
- Recomendação de próxima ação (next best action)
- Análise de sentiment de dados públicos

### 7.5 Análise de Mercado
- Análise setorial
- Mapeamento de dores comunes por segmento
- Identificação de tendências

---

## 8. Cron Jobs e Automações Sugeridas

```bash
# ================================================
# CRON JOBS — Prospector Automation
# ================================================

# 1. Prospecção automática a cada 6 horas
0 */6 * * * cd /root/.openclaw/workspace/main/prospector && \
  docker compose exec -T backend python -m prospector run --batch 50 >> /var/log/prospector-batch.log 2>&1

# 2. Enriquecimento de leads pendentes a cada 15 minutos
*/15 * * * * cd /root/.openclaw/workspace/main/prospector && \
  docker compose exec -T backend python -m prospector enrich --pending >> /var/log/prospector-enrich.log 2>&1

# 3. Limpeza de dados velhos (60 dias) semanalmente
0 3 * * 0 cd /root/.openclaw/workspace/main/prospector && \
  docker compose exec -T backend python -m prospector cleanup --older-than 60 >> /var/log/prospector-cleanup.log 2>&1

# 4. Health check do Prospector a cada 5 minutos (alerta se cair)
*/5 * * * * curl -f http://localhost:5000/api/health > /dev/null 2>&1 || \
  (cd /root/.openclaw/workspace/main/prospector && docker compose restart backend)

# 5. Backup do volume de dados do Prospector diariamente às 2h
0 2 * * * docker run --rm \
  -v prospector_prospector_data:/source \
  -v /root/backups/prospector:/dest \
  alpine tar czf /dest/prospector-data-$(date +\%Y\%m\%d).tar.gz -C /source . 2>&1
```

---

## 9. Limitações e Riscos

| Risco | Severidade | Mitigação |
|---|---|---|
| **Prospector cai** e CRM falha em silêncio | Alta | Monitorar com health check (item 8.4) |
| ** SERPER_KEY** inválida/expirada | Alta | Verificar quota em `serper.dev`, renovar se preciso |
| **OLLAMA_KEY** expirar (API key rate limits) | Alta | Monitorar logs, ter fallback graceful |
| **Rede não existe** na hora do deploy | Média | Verificar `docker network ls` antes; criar se preciso |
| **Conflito de portas** (8088/5000 já em uso) | Média | `netstat -tlnp | grep -E '8088|5000'` antes de subir |
| **Dados sensíveis** no volume Docker | Média | Criptografar volumes ou usar secrets Docker |
| **Performance degrada** com muitos leads | Média | Batch size menor, filas assíncronas |
| **Quebrar CRM** ao modificar docker-compose | Baixa | Backup antes + rollback rápido |

### Limitações Conhecidas
- **Sem fila persistente:** se o container reiniciar durante processamento, jobs podem ser perdidos (considerar Redis queue)
- **Rate limits:** SerpAPI e OLLAMA têm limites; cron jobs podem ser bloqueados temporariamente
- **Sem SSO:** autenticação do Prospector é básica; sem LDAP/SSO integrado ao CRM

---

## 10. Próximos Passos

```
[ ] 1. Backup do docker-compose.yml atual
[ ] 2. Aplicar mudanças no docker-compose.yml do Prospector
[ ] 3. Atualizar .env do CRM com PROSPECTOR_BASE_URL
[ ] 4. Verificar se rede prospec-crm_prospec-net existe
[ ] 5. Subir Prospector (docker compose up -d)
[ ] 6. Validar连通ividade CRM → Prospector
[ ] 7. Rodar teste de prospecção completa
[ ] 8. Configurar cron jobs de automação
[ ] 9. Configurar monitoring/alertas
[ ] 10. Documentarandoff handover para equipe
```

---

## Referências

- Prospector: `/root/.openclaw/workspace/main/prospector/`
- Prospec CRM: `/root/.openclaw/workspace/technical/crm-prospector/`
- Health check: `curl http://localhost:5000/api/health`
- Redes: `docker network ls | grep -E "prospec|prospector"`
