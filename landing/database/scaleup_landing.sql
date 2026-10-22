-- ScaleUp LATAM & Europe — banco MySQL para importar no phpMyAdmin (XAMPP)
-- (Opcional: o site também cria o banco e as tabelas sozinho no primeiro acesso.)

CREATE DATABASE IF NOT EXISTS scaleup_landing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scaleup_landing;

CREATE TABLE IF NOT EXISTS leads (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    phone       VARCHAR(40) NULL,
    company     VARCHAR(160) NULL,
    role        VARCHAR(120) NULL,
    country     VARCHAR(80) NULL,
    region      ENUM('LATAM','EUROPE') NOT NULL,
    language    VARCHAR(10) NULL,
    interest    VARCHAR(60) NULL,
    message     TEXT NULL,
    source      VARCHAR(40) DEFAULT 'direct',
    medium      VARCHAR(60) NULL,
    campaign    VARCHAR(120) NULL,
    stage       ENUM('lead','mql','sql','customer','lost') NOT NULL DEFAULT 'lead',
    deal_value  DECIMAL(12,2) NOT NULL DEFAULT 0,
    consent     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_leads_region (region),
    INDEX idx_leads_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type        VARCHAR(40) NOT NULL,
    region      VARCHAR(10) NULL,
    language    VARCHAR(10) NULL,
    source      VARCHAR(40) DEFAULT 'direct',
    label       VARCHAR(120) NULL,
    session_id  VARCHAR(128) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campaigns (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(160) NOT NULL,
    channel     VARCHAR(60) NOT NULL,
    source      VARCHAR(40) NOT NULL,
    region      VARCHAR(10) NOT NULL,
    budget      DECIMAL(12,2) NOT NULL DEFAULT 0,
    spent       DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO campaigns (name, channel, source, region, budget, spent) VALUES
('Busca – Expansão Brasil/México', 'Google Ads', 'google', 'LATAM', 1500, 0),
('Instagram/Facebook – Awareness LATAM', 'Meta Ads', 'meta', 'LATAM', 1000, 0),
('Busca – Portugal/Espanha/França', 'Google Ads', 'google', 'EUROPE', 2000, 0),
('LinkedIn B2B – Decisores Europa', 'LinkedIn Ads', 'linkedin', 'EUROPE', 1500, 0),
('Conteúdo orgânico + SEO', 'Orgânico', 'organic', 'LATAM', 0, 0),
('Newsletter / E-mail nutrição', 'E-mail', 'email', 'EUROPE', 0, 0);
