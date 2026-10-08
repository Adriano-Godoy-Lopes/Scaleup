<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content.php';

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'] ?? '';

function respond(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$market = $_POST['market'] ?? ($_SESSION['market'] ?? 'br');
$t = MARKETS[$market] ?? MARKETS['br'];
$source = $_SESSION['source'] ?? 'direct';

switch ($action) {
    case 'event':
        $type = in_array($_POST['type'] ?? '', ['cta_click', 'form_start'], true) ? $_POST['type'] : null;
        if (!$type) {
            respond(['ok' => false], 422);
        }
        track($type, ['region' => $t['region'], 'language' => $t['lang'], 'source' => $source, 'label' => substr((string)($_POST['label'] ?? ''), 0, 40)]);
        respond(['ok' => true]);

    case 'lead':
        if (!empty($_POST['website'])) {
            respond(['ok' => true]); // honeypot anti-spam
        }
        $f = fn(string $k, int $max = 160) => trim(cut((string)($_POST[$k] ?? ''), $max));
        $data = [
            'name' => $f('name', 120), 'email' => $f('email'), 'phone' => $f('phone', 40),
            'company' => $f('company', 120), 'role' => $f('role', 80), 'country' => $f('country', 60),
            'interest' => $f('interest', 40), 'message' => $f('message', 1000),
        ];
        $errors = [];
        if ($data['name'] === '') $errors[] = 'name';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
        if ($data['company'] === '') $errors[] = 'company';
        if ($data['country'] === '') $errors[] = 'country';
        if (empty($_POST['consent'])) $errors[] = 'consent';
        if ($errors) {
            respond(['ok' => false, 'errors' => $errors], 422);
        }

        $stmt = db()->prepare('INSERT INTO leads (name, email, phone, company, role, country, region, language, interest, message, source, medium, campaign, consent)
                               VALUES (:name, :email, :phone, :company, :role, :country, :region, :language, :interest, :message, :source, :medium, :campaign, 1)');
        $stmt->execute($data + [
            'region' => $t['region'], 'language' => $t['lang'], 'source' => $source,
            'medium' => $_SESSION['medium'] ?? null, 'campaign' => $_SESSION['campaign'] ?? null,
        ]);
        track('lead', ['region' => $t['region'], 'language' => $t['lang'], 'source' => $source, 'label' => $market]);
        respond(['ok' => true, 'id' => (int)db()->lastInsertId()]);

    default:
        respond(['ok' => false, 'error' => 'unknown_action'], 404);
}
