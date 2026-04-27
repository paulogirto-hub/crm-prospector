-- 002_create_companies.sql
-- Tabela de empresas (enriquecidas via Serper, BrasilAPI, scraping)

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
    maps_phone      VARCHAR(50),
    maps_category   VARCHAR(255),
    maps_lat        DECIMAL(10,7),
    maps_lng        DECIMAL(10,7),
    score           INTEGER DEFAULT 0,                        -- 0-100
    notes           TEXT,
    
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
    socios             JSONB,                                -- Array de nomes
    
    -- Contatos extraídos do site
    site_emails        JSONB DEFAULT '[]',
    site_phones        JSONB DEFAULT '[]',
    site_instagram     VARCHAR(500),
    site_facebook      VARCHAR(500),
    site_youtube       VARCHAR(500),
    site_tiktok        VARCHAR(500),
    cnpj_source        VARCHAR(50),                          -- 'snippet' | 'site' | 'maps_phone'
    enrichment_status  VARCHAR(20) DEFAULT 'pending',         -- pending | partial | done
    
    tem_site          BOOLEAN DEFAULT false,
    tem_instagram     BOOLEAN DEFAULT false,
    tem_facebook      BOOLEAN DEFAULT false,
    tem_maps          BOOLEAN DEFAULT false,
    tem_ads           BOOLEAN DEFAULT false,
    
    created_by        INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at        TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_companies_cnpj ON companies(cnpj);
CREATE INDEX idx_companies_niche ON companies(niche);
CREATE INDEX idx_companies_city ON companies(city);
CREATE INDEX idx_companies_niche_city ON companies(niche, city);
CREATE INDEX idx_companies_score ON companies(score DESC);
CREATE INDEX idx_companies_created_by ON companies(created_by);