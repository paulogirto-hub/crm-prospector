-- 003_create_pipeline_stages.sql
-- Stages do pipeline (Kanban)

CREATE TABLE pipeline_stages (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    position    INTEGER NOT NULL DEFAULT 0,
    color       VARCHAR(7) NOT NULL DEFAULT '#6c5ce7',     -- hex color
    is_default  BOOLEAN NOT NULL DEFAULT false,
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX idx_pipeline_stages_position ON pipeline_stages(position);

-- Seed: stages padrão
INSERT INTO pipeline_stages (name, position, color, is_default) VALUES
    ('Novo',           1, '#6c5ce7', true),
    ('Contatado',      2, '#0984e3', false),
    ('Respondendo',    3, '#00cec9', false),
    ('Reunião',        4, '#fdcb6e', false),
    ('Proposta',       5, '#e17055', false),
    ('Fechado',        6, '#00b894', false),
    ('Perdido',        7, '#d63031', false);