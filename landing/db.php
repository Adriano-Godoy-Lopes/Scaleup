<?php
require_once __DIR__ . '/config.php';

/**
 * Conexão com o banco (MySQL do XAMPP ou SQLite, conforme DB_DRIVER em config.php).
 * No MySQL, o banco e as tabelas são criados automaticamente no primeiro acesso
 * (ou importe database/scaleup_landing.sql pelo phpMyAdmin).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (is_mysql()) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, $options);
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('Não foi possível conectar ao MySQL (' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '). '
                . 'Verifique se o MySQL está iniciado no painel do XAMPP e os dados em config.php.');
        }
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');
        $pdo->exec(file_get_contents(__DIR__ . '/database/tables_mysql.sql'));
    } else {
        if (!is_dir(dirname(DB_FILE))) {
            mkdir(dirname(DB_FILE), 0775, true);
        }
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        migrate_sqlite($pdo);
    }
    seed_campaigns($pdo);
    return $pdo;
}

function is_mysql(): bool
{
    return DB_DRIVER === 'mysql';
}

/** Expressão SQL de "agora", compatível com o banco em uso. */
function sql_now(): string
{
    return is_mysql() ? 'NOW()' : "datetime('now')";
}

function migrate_sqlite(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS leads (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT NOT NULL,
            email       TEXT NOT NULL,
            phone       TEXT,
            company     TEXT,
            role        TEXT,
            country     TEXT,
            region      TEXT NOT NULL CHECK (region IN ('LATAM','EUROPE')),
            language    TEXT,
            interest    TEXT,
            message     TEXT,
            source      TEXT DEFAULT 'direct',
            medium      TEXT,
            campaign    TEXT,
            stage       TEXT NOT NULL DEFAULT 'lead' CHECK (stage IN ('lead','mql','sql','customer','lost')),
            deal_value  REAL NOT NULL DEFAULT 0,
            consent     INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS events (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            type        TEXT NOT NULL,
            region      TEXT,
            language    TEXT,
            source      TEXT DEFAULT 'direct',
            label       TEXT,
            session_id  TEXT,
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE TABLE IF NOT EXISTS campaigns (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT NOT NULL,
            channel     TEXT NOT NULL,
            source      TEXT NOT NULL,
            region      TEXT NOT NULL,
            budget      REAL NOT NULL DEFAULT 0,
            spent       REAL NOT NULL DEFAULT 0,
            created_at  TEXT NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_leads_region ON leads(region);
        CREATE INDEX IF NOT EXISTS idx_leads_source ON leads(source);
        CREATE INDEX IF NOT EXISTS idx_events_type ON events(type);
    ");
}

function seed_campaigns(PDO $pdo): void
{
    if ((int)$pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn() === 0) {
        $seed = $pdo->prepare('INSERT INTO campaigns (name, channel, source, region, budget, spent) VALUES (?,?,?,?,?,?)');
        foreach ([
            ['Busca – Expansão Brasil/México', 'Google Ads', 'google', 'LATAM', 1500, 0],
            ['Instagram/Facebook – Awareness LATAM', 'Meta Ads', 'meta', 'LATAM', 1000, 0],
            ['Busca – Portugal/Espanha/França', 'Google Ads', 'google', 'EUROPE', 2000, 0],
            ['LinkedIn B2B – Decisores Europa', 'LinkedIn Ads', 'linkedin', 'EUROPE', 1500, 0],
            ['Conteúdo orgânico + SEO', 'Orgânico', 'organic', 'LATAM', 0, 0],
            ['Newsletter / E-mail nutrição', 'E-mail', 'email', 'EUROPE', 0, 0],
        ] as $row) {
            $seed->execute($row);
        }
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function track(string $type, array $data = []): void
{
    $stmt = db()->prepare('INSERT INTO events (type, region, language, source, label, session_id) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $type,
        $data['region'] ?? null,
        $data['language'] ?? null,
        $data['source'] ?? 'direct',
        $data['label'] ?? null,
        session_id(),
    ]);
}

function is_admin(): bool
{
    return !empty($_SESSION['is_admin']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): bool
{
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
}

/** Corta texto com segurança para UTF-8 (funciona mesmo sem a extensão mbstring). */
function cut(string $text, int $max, string $suffix = ''): string
{
    if (preg_match_all('/./us', $text, $m) <= $max) {
        return $text;
    }
    return implode('', array_slice($m[0], 0, $max)) . $suffix;
}
