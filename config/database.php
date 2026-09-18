<?php
// Ajuste estas credenciais conforme o MySQL local.
const DB_HOST = 'localhost'; const DB_NAME = 'scaleup_latam_europe'; const DB_USER = 'root'; const DB_PASS = '';
function db(): PDO { static $pdo; if (!$pdo) { $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); } return $pdo; }
