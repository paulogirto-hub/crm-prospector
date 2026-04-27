-- 007_create_templates.sql
-- Templates de mensagem (WhatsApp, Email, Instagram)

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