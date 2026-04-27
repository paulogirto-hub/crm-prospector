-- 009_create_audit_log.sql
-- Log de ações do sistema

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