<?php
require_once __DIR__ . '/db.php';
if (!is_admin()) {
    header('Location: admin.php');
    exit;
}
$region = in_array($_GET['region'] ?? '', ['LATAM', 'EUROPE'], true) ? $_GET['region'] : null;
$st = db()->prepare('SELECT id, created_at, name, email, phone, company, role, country, region, language, interest, source, medium, campaign, stage, deal_value, message FROM leads' . ($region ? ' WHERE region = ?' : '') . ' ORDER BY id');
$st->execute($region ? [$region] : []);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="leads-' . ($region ? strtolower($region) . '-' : '') . date('Ymd') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
$first = true;
while ($row = $st->fetch()) {
    if ($first) { fputcsv($out, array_keys($row), ';'); $first = false; }
    fputcsv($out, $row, ';');
}
fclose($out);
