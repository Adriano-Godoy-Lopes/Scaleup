<?php
require_once __DIR__.'/../config/database.php';
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rows(string $table, string $order='id'): array { return db()->query("SELECT * FROM `$table` ORDER BY $order")->fetchAll(); }
function one(string $table, int $id): ?array { $s=db()->prepare("SELECT * FROM `$table` WHERE id=?");$s->execute([$id]);return $s->fetch()?:null; }
function setting(string $section, string $title, string $fallback=''): string { $s=db()->prepare('SELECT conteudo FROM conteudos WHERE secao=? AND titulo=? LIMIT 1');$s->execute([$section,$title]);return $s->fetchColumn()?:$fallback; }
