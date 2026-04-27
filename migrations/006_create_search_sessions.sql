-- 006_create_search_sessions.sql
-- Prospecções realizadas (migração do JSON do Prospector)

CREATE TABLE search_sessions (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    niche           VARCHAR(255) NOT NULL,
    city            VARCHAR(255) NOT NULL,
    state           VARCHAR(2) NOT NULL,
    query           VARCHAR(500),
    query_variations JSONB DEFAULT '[]',
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

-- Tabela auxiliar: search_leads (relação N:N entre search_sessions e companies)
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