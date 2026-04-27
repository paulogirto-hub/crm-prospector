-- 008_create_tasks.sql
-- Tarefas e follow-ups

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
CREATE INDEX idx_tasks_pending ON tasks(due_date) WHERE completed_at IS NULL;