<?php
require_once __DIR__ . '/db.php';

const STAGES = ['lead' => 'Lead', 'mql' => 'MQL', 'sql' => 'SQL', 'customer' => 'Cliente', 'lost' => 'Perdido'];
const REGIONS = ['LATAM' => 'LATAM', 'EUROPE' => 'Europa'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function redirect(?string $msg = null): never
{
    if ($msg) $_SESSION['flash'] = $msg;
    header('Location: admin.php' . (isset($_GET['region']) ? '?region=' . urlencode($_GET['region']) : ''));
    exit;
}

// Login / logout
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    header('Location: admin.php');
    exit;
}
if (!is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (hash_equals(ADMIN_PASSWORD, (string)$_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        redirect();
    }
    $flash = 'Senha incorreta.';
}

if (!is_admin()):
?>
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel – <?= e(APP_NAME) ?></title><link rel="stylesheet" href="assets/admin.css"></head>
<body class="login-page">
<form method="post" class="login-box">
    <h1>Scale<span>Up</span> · Painel</h1>
    <p>Acesso restrito à equipe de marketing e vendas.</p>
    <?php if ($flash): ?><div class="flash err"><?= e($flash) ?></div><?php endif; ?>
    <input type="password" name="password" placeholder="Senha" required autofocus>
    <button type="submit">Entrar</button>
    <a href="index.php">← Voltar ao site</a>
</form>
</body></html>
<?php
exit;
endif;

$pdo = db();

// Ações do painel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf()) redirect('Sessão expirada, tente novamente.');
    switch ($_POST['do'] ?? '') {
        case 'stage':
            $stage = array_key_exists($_POST['stage'] ?? '', STAGES) ? $_POST['stage'] : 'lead';
            $pdo->prepare("UPDATE leads SET stage = ?, deal_value = ?, updated_at = datetime('now') WHERE id = ?")
                ->execute([$stage, max(0, (float)($_POST['deal_value'] ?? 0)), (int)$_POST['id']]);
            redirect('Lead atualizado.');
        case 'delete':
            $pdo->prepare('DELETE FROM leads WHERE id = ?')->execute([(int)$_POST['id']]);
            redirect('Lead removido.');
        case 'campaign':
            $pdo->prepare('UPDATE campaigns SET budget = ?, spent = ? WHERE id = ?')
                ->execute([max(0, (float)$_POST['budget']), max(0, (float)$_POST['spent']), (int)$_POST['id']]);
            redirect('Campanha atualizada.');
        case 'add_campaign':
            $region = array_key_exists($_POST['region'] ?? '', REGIONS) ? $_POST['region'] : 'LATAM';
            $pdo->prepare('INSERT INTO campaigns (name, channel, source, region, budget, spent) VALUES (?,?,?,?,?,?)')
                ->execute([trim($_POST['name']) ?: 'Nova campanha', trim($_POST['channel']) ?: 'Outro',
                    strtolower(preg_replace('/[^\w\-]/', '', $_POST['source'] ?? '')) ?: 'direct', $region,
                    max(0, (float)$_POST['budget']), max(0, (float)$_POST['spent'])]);
            redirect('Campanha criada.');
        case 'seed':
            require __DIR__ . '/seed.php';
            seed_demo($pdo);
            redirect('Dados simulados gerados.');
        case 'reset':
            $pdo->exec('DELETE FROM leads; DELETE FROM events; UPDATE campaigns SET spent = 0;');
            redirect('Dados zerados.');
    }
    redirect();
}

// Filtro por região
$region = array_key_exists($_GET['region'] ?? '', REGIONS) ? $_GET['region'] : null;
$where = $region ? 'WHERE region = :r' : '';
$params = $region ? ['r' => $region] : [];
$q = function (string $sql) use ($pdo, $params) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st;
};

// Métricas do funil
$visits   = (int)$q("SELECT COUNT(*) FROM events $where" . ($where ? ' AND' : ' WHERE') . " type = 'page_view'")->fetchColumn();
$clicks   = (int)$q("SELECT COUNT(*) FROM events $where" . ($where ? ' AND' : ' WHERE') . " type = 'cta_click'")->fetchColumn();
$starts   = (int)$q("SELECT COUNT(*) FROM events $where" . ($where ? ' AND' : ' WHERE') . " type = 'form_start'")->fetchColumn();
$byStage  = $q("SELECT stage, COUNT(*) n, SUM(deal_value) v FROM leads $where GROUP BY stage")->fetchAll(PDO::FETCH_UNIQUE);
$leads    = array_sum(array_column($byStage, 'n'));
$mql      = ($byStage['mql']['n'] ?? 0) + ($byStage['sql']['n'] ?? 0) + ($byStage['customer']['n'] ?? 0);
$sqlN     = ($byStage['sql']['n'] ?? 0) + ($byStage['customer']['n'] ?? 0);
$customers = (int)($byStage['customer']['n'] ?? 0);
$revenue  = (float)($byStage['customer']['v'] ?? 0);
$spent    = (float)$q("SELECT COALESCE(SUM(spent),0) FROM campaigns $where")->fetchColumn();
$budget   = (float)$q("SELECT COALESCE(SUM(budget),0) FROM campaigns $where")->fetchColumn();

$pct = fn($a, $b) => $b > 0 ? round($a / $b * 100, 1) : 0;
$money = fn($v) => '$ ' . number_format($v, 2, ',', '.');
$conv = $pct($leads, $visits);
$cpl  = $leads ? $spent / $leads : 0;
$cac  = $customers ? $spent / $customers : 0;
$roi  = $spent > 0 ? ($revenue - $spent) / $spent * 100 : 0;
$ticket = $customers ? $revenue / $customers : 0;

// Desempenho por canal (origem UTM) com ROI
$channels = $pdo->prepare("
    SELECT s.source,
           (SELECT COUNT(*) FROM events e WHERE e.type='page_view' AND e.source = s.source " . ($region ? 'AND e.region = :r' : '') . ") visits,
           (SELECT COUNT(*) FROM leads l WHERE l.source = s.source " . ($region ? 'AND l.region = :r' : '') . ") leads,
           (SELECT COUNT(*) FROM leads l WHERE l.source = s.source AND l.stage='customer' " . ($region ? 'AND l.region = :r' : '') . ") customers,
           (SELECT COALESCE(SUM(deal_value),0) FROM leads l WHERE l.source = s.source AND l.stage='customer' " . ($region ? 'AND l.region = :r' : '') . ") revenue,
           (SELECT COALESCE(SUM(spent),0) FROM campaigns c WHERE c.source = s.source " . ($region ? 'AND c.region = :r' : '') . ") spent
    FROM (SELECT source FROM leads UNION SELECT source FROM events WHERE type='page_view' UNION SELECT source FROM campaigns) s
    ORDER BY leads DESC");
$channels->execute($params);
$channels = $channels->fetchAll();

$regionStats = $pdo->query("SELECT region, COUNT(*) leads, SUM(stage='customer') customers, COALESCE(SUM(CASE WHEN stage='customer' THEN deal_value END),0) revenue FROM leads GROUP BY region")->fetchAll(PDO::FETCH_UNIQUE);
$countries = $q("SELECT country, COUNT(*) n FROM leads $where GROUP BY country ORDER BY n DESC LIMIT 8")->fetchAll();
$daily = $q("SELECT date(created_at) d, COUNT(*) n FROM leads $where GROUP BY d ORDER BY d DESC LIMIT 30")->fetchAll();
$daily = array_reverse($daily);
$leadList = $q("SELECT * FROM leads $where ORDER BY id DESC LIMIT 200")->fetchAll();
$campaigns = $q("SELECT * FROM campaigns $where ORDER BY region, id")->fetchAll();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel de KPIs – <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/admin.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<header class="bar">
    <strong>Scale<span>Up</span> · Painel de KPIs</strong>
    <nav class="filters">
        <a href="admin.php" class="<?= !$region ? 'active' : '' ?>">Todas</a>
        <?php foreach (REGIONS as $k => $v): ?><a href="?region=<?= $k ?>" class="<?= $region === $k ? 'active' : '' ?>"><?= $v ?></a><?php endforeach; ?>
    </nav>
    <div class="bar-actions">
        <a href="export.php<?= $region ? '?region=' . $region : '' ?>">⬇ CSV</a>
        <a href="index.php" target="_blank">Ver site</a>
        <a href="?logout=1">Sair</a>
    </div>
</header>

<main class="wrap">
    <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

    <section class="cards">
        <div class="kpi"><small>Visitantes</small><b><?= $visits ?></b><em><?= $clicks ?> cliques em CTA</em></div>
        <div class="kpi"><small>Leads</small><b><?= $leads ?></b><em>Conversão <?= $conv ?>%</em></div>
        <div class="kpi"><small>Clientes</small><b><?= $customers ?></b><em>Lead → cliente <?= $pct($customers, $leads) ?>%</em></div>
        <div class="kpi"><small>Receita</small><b><?= $money($revenue) ?></b><em>Ticket médio <?= $money($ticket) ?></em></div>
        <div class="kpi"><small>Investimento</small><b><?= $money($spent) ?></b><em>de <?= $money($budget) ?> orçado</em></div>
        <div class="kpi"><small>CPL</small><b><?= $money($cpl) ?></b><em>Custo por lead</em></div>
        <div class="kpi"><small>CAC</small><b><?= $money($cac) ?></b><em>Custo por cliente</em></div>
        <div class="kpi <?= $roi >= 0 ? 'good' : 'bad' ?>"><small>ROI</small><b><?= round($roi, 1) ?>%</b><em>(receita − custo) / custo</em></div>
    </section>

    <section class="grid2">
        <div class="panel">
            <h2>Funil de marketing e vendas</h2>
            <?php $funnel = ['Visitantes' => $visits, 'Iniciaram formulário' => $starts, 'Leads' => $leads, 'MQL' => $mql, 'SQL' => $sqlN, 'Clientes' => $customers];
            $max = max(1, max($funnel)); $prev = null;
            foreach ($funnel as $label => $n): ?>
                <div class="frow">
                    <span><?= $label ?></span>
                    <div class="fbar"><i style="width:<?= max(2, $n / $max * 100) ?>%"></i></div>
                    <b><?= $n ?></b>
                    <small><?= $prev !== null ? $pct($n, $prev) . '%' : '' ?></small>
                </div>
            <?php $prev = $n; endforeach; ?>
        </div>
        <div class="panel">
            <h2>LATAM x Europa</h2>
            <canvas id="regionChart" height="200"></canvas>
            <table class="mini">
                <tr><th>Região</th><th>Leads</th><th>Clientes</th><th>Receita</th></tr>
                <?php foreach (REGIONS as $k => $v): $r = $regionStats[$k] ?? ['leads' => 0, 'customers' => 0, 'revenue' => 0]; ?>
                    <tr><td><?= $v ?></td><td><?= (int)$r['leads'] ?></td><td><?= (int)$r['customers'] ?></td><td><?= $money((float)$r['revenue']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </section>

    <section class="grid2">
        <div class="panel"><h2>Leads por dia</h2><canvas id="dailyChart" height="200"></canvas></div>
        <div class="panel"><h2>Leads por país</h2><canvas id="countryChart" height="200"></canvas></div>
    </section>

    <section class="panel">
        <h2>Desempenho por canal (ROI)</h2>
        <div class="table-wrap"><table>
            <tr><th>Canal / origem</th><th>Visitas</th><th>Leads</th><th>Conv.</th><th>Clientes</th><th>Receita</th><th>Investido</th><th>CPL</th><th>CAC</th><th>ROI</th></tr>
            <?php foreach ($channels as $c): $croi = $c['spent'] > 0 ? ($c['revenue'] - $c['spent']) / $c['spent'] * 100 : null; ?>
                <tr>
                    <td><b><?= e($c['source']) ?></b></td><td><?= $c['visits'] ?></td><td><?= $c['leads'] ?></td><td><?= $pct($c['leads'], $c['visits']) ?>%</td>
                    <td><?= $c['customers'] ?></td><td><?= $money($c['revenue']) ?></td><td><?= $money($c['spent']) ?></td>
                    <td><?= $c['leads'] ? $money($c['spent'] / $c['leads']) : '–' ?></td>
                    <td><?= $c['customers'] ? $money($c['spent'] / $c['customers']) : '–' ?></td>
                    <td class="<?= $croi === null ? '' : ($croi >= 0 ? 'pos' : 'neg') ?>"><?= $croi === null ? 'orgânico' : round($croi, 1) . '%' ?></td>
                </tr>
            <?php endforeach; ?>
        </table></div>
        <p class="hint">Use links com UTM para atribuir tráfego: <code>index.php?m=br&amp;utm_source=google&amp;utm_campaign=busca_brasil</code></p>
    </section>

    <section class="panel">
        <h2>Campanhas (orçamento simulado)</h2>
        <div class="table-wrap"><table>
            <tr><th>Campanha</th><th>Canal</th><th>Origem (utm_source)</th><th>Região</th><th>Orçamento</th><th>Gasto</th><th></th></tr>
            <?php foreach ($campaigns as $c): $fid = 'camp' . $c['id']; ?>
                <tr>
                    <td><?= e($c['name']) ?></td><td><?= e($c['channel']) ?></td><td><code><?= e($c['source']) ?></code></td><td><?= REGIONS[$c['region']] ?? e($c['region']) ?></td>
                    <td><input form="<?= $fid ?>" type="number" step="0.01" min="0" name="budget" value="<?= $c['budget'] ?>"></td>
                    <td><input form="<?= $fid ?>" type="number" step="0.01" min="0" name="spent" value="<?= $c['spent'] ?>"></td>
                    <td><form id="<?= $fid ?>" method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="do" value="campaign"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button>Salvar</button></form></td>
                </tr>
            <?php endforeach; ?>
            <tr class="new">
                <td><input form="newcamp" name="name" placeholder="Nova campanha" required></td>
                <td><input form="newcamp" name="channel" placeholder="Canal"></td>
                <td><input form="newcamp" name="source" placeholder="utm_source"></td>
                <td><select form="newcamp" name="region"><?php foreach (REGIONS as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></td>
                <td><input form="newcamp" type="number" step="0.01" min="0" name="budget" value="0"></td>
                <td><input form="newcamp" type="number" step="0.01" min="0" name="spent" value="0"></td>
                <td><form id="newcamp" method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="do" value="add_campaign"><button>Adicionar</button></form></td>
            </tr>
        </table></div>
    </section>

    <section class="panel">
        <h2>Leads (<?= count($leadList) ?>)</h2>
        <div class="table-wrap"><table>
            <tr><th>#</th><th>Data</th><th>Nome / e-mail</th><th>Empresa</th><th>País</th><th>Região</th><th>Origem</th><th>Interesse</th><th>Etapa / valor</th><th></th></tr>
            <?php foreach ($leadList as $l): ?>
                <tr>
                    <td><?= $l['id'] ?></td>
                    <td><?= e(substr($l['created_at'], 0, 16)) ?></td>
                    <td><b><?= e($l['name']) ?></b><br><small><?= e($l['email']) ?><?= $l['phone'] ? ' · ' . e($l['phone']) : '' ?></small><?php if ($l['message']): ?><br><small class="msg" title="<?= e($l['message']) ?>">💬 <?= e(cut($l['message'], 60, '…')) ?></small><?php endif; ?></td>
                    <td><?= e($l['company']) ?><br><small><?= e($l['role']) ?></small></td>
                    <td><?= e($l['country']) ?></td>
                    <td><span class="pill <?= strtolower($l['region']) ?>"><?= REGIONS[$l['region']] ?></span></td>
                    <td><?= e($l['source']) ?><?= $l['campaign'] ? '<br><small>' . e($l['campaign']) . '</small>' : '' ?></td>
                    <td><?= e($l['interest']) ?></td>
                    <td>
                        <form method="post" class="inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="do" value="stage"><input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <select name="stage" class="stage-<?= $l['stage'] ?>"><?php foreach (STAGES as $k => $v): ?><option value="<?= $k ?>" <?= $l['stage'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
                            <input type="number" name="deal_value" step="0.01" min="0" value="<?= $l['deal_value'] ?>" title="Valor do negócio">
                            <button>OK</button>
                        </form>
                    </td>
                    <td><form method="post" onsubmit="return confirm('Remover este lead?')"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $l['id'] ?>"><button class="danger" title="Remover">✕</button></form></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$leadList): ?><tr><td colspan="10" class="empty">Nenhum lead ainda. Envie o formulário do site ou gere dados simulados abaixo.</td></tr><?php endif; ?>
        </table></div>
    </section>

    <section class="panel tools">
        <h2>Simulação</h2>
        <p>O desafio trabalha com hipóteses e dados simulados. Gere um conjunto de visitas, leads, vendas e gastos de campanha para apresentar os KPIs.</p>
        <form method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="do" value="seed"><button>Gerar dados simulados</button></form>
        <form method="post" onsubmit="return confirm('Apagar todos os leads e eventos?')"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="do" value="reset"><button class="danger">Zerar dados</button></form>
    </section>
</main>

<script>
const COLORS = { latam: '#10b981', eu: '#3b82f6', primary: '#ff6b35' };
new Chart(document.getElementById('regionChart'), {
    type: 'bar',
    data: { labels: ['Leads', 'Clientes'], datasets: [
        { label: 'LATAM', backgroundColor: COLORS.latam, data: [<?= (int)($regionStats['LATAM']['leads'] ?? 0) ?>, <?= (int)($regionStats['LATAM']['customers'] ?? 0) ?>] },
        { label: 'Europa', backgroundColor: COLORS.eu, data: [<?= (int)($regionStats['EUROPE']['leads'] ?? 0) ?>, <?= (int)($regionStats['EUROPE']['customers'] ?? 0) ?>] },
    ] },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: { labels: <?= json_encode(array_column($daily, 'd')) ?>, datasets: [{ label: 'Leads', data: <?= json_encode(array_map('intval', array_column($daily, 'n'))) ?>, borderColor: COLORS.primary, backgroundColor: 'rgba(255,107,53,.15)', fill: true, tension: .3 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
new Chart(document.getElementById('countryChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_column($countries, 'country'), JSON_UNESCAPED_UNICODE) ?>, datasets: [{ data: <?= json_encode(array_map('intval', array_column($countries, 'n'))) ?>, backgroundColor: ['#ff6b35','#10b981','#3b82f6','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#64748b'] }] },
    options: { responsive: true, plugins: { legend: { position: 'right' } } }
});
</script>
</body>
</html>
