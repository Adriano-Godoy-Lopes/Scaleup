<?php
// Configurações gerais da plataforma
const APP_NAME   = 'ScaleUp LATAM & Europe';
const DB_FILE    = __DIR__ . '/data/scaleup.sqlite';
// Senha do painel administrativo (altere antes de publicar ou defina ADMIN_PASSWORD no ambiente)
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'scaleup2026');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
