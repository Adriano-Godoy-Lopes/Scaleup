<?php
// Roteador para o servidor embutido do PHP (php -S): bloqueia acesso direto ao banco
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/data') || preg_match('/\.(sqlite|db)$/', $path) || in_array(basename($path), ['config.php', 'db.php', 'content.php', 'router.php'], true)) {
    http_response_code(403);
    exit('Forbidden');
}
return false;
