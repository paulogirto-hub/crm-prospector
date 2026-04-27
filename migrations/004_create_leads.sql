-- 004_create_leads.sql
-- Tabela de leads com FK para companies, pipeline_stages e users

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