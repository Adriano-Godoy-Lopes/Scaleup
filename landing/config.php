<?php
// Configurações gerais da plataforma
const APP_NAME = 'ScaleUp LATAM & Europe';

// Banco de dados: 'mysql' (XAMPP / phpMyAdmin) ou 'sqlite' (arquivo local, sem instalar nada)
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');

// MySQL / MariaDB — padrão do XAMPP: usuário root, senha vazia
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'scaleup_landing');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// SQLite (usado apenas quando DB_DRIVER = 'sqlite')
const DB_FILE = __DIR__ . '/data/scaleup.sqlite';

// Senha do painel administrativo (altere antes de publicar ou defina ADMIN_PASSWORD no ambiente)
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'scaleup2026');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
