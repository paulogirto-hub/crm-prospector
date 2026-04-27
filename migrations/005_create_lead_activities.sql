-- 005_create_lead_activities.sql
-- Timeline de atividades dos leads

CREATE TABLE lead_activities (
    id          SERIAL PRIMARY KEY,
    lead_id     INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    user_id     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    type        VARCHAR(50) NOT NULL,                       -- call | email | whatsapp | note | status_change | score_change | ia_analysis | enrichment | diagnosis
    description TEXT NOT NULL,
    metadata    JSONB DEFAULT '{}',
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_activities_lead ON lead_activities(lead_id);
CREATE INDEX idx_activities_user ON lead_activities(user_id);
CREATE INDEX idx_activities_type ON lead_activities(type);
CREATE INDEX idx_activities_created ON lead_activities(created_at DESC);