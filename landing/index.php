<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content.php';

$m = current_market();
$t = MARKETS[$m];

// Guarda a origem do tráfego (UTM) para atribuir leads a canais/campanhas
foreach (['utm_source' => 'source', 'utm_medium' => 'medium', 'utm_campaign' => 'campaign'] as $q => $k) {
    if (!empty($_GET[$q])) {
        $_SESSION[$k] = substr(preg_replace('/[^\w\-\.]/', '', $_GET[$q]), 0, 60);
    }
}
$source = $_SESSION['source'] ?? 'direct';

if (empty($_SESSION['viewed'][$m])) {
    track('page_view', ['region' => $t['region'], 'language' => $t['lang'], 'source' => $source, 'label' => $m]);
    $_SESSION['viewed'][$m] = true;
}

$whatsapp = 'https://wa.me/5511915095885';
$email    = 'mailto:contact@eyegis-eyewear.com';
$secondaryHref = $t['region'] === 'EUROPE' ? $email : $whatsapp;
$ids = ['problemas', 'metodo', 'funil', 'mercados', 'kpis', 'contato'];
?>
<!DOCTYPE html>
<html lang="<?= e($t['lang']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> – <?= e($t['hero_tag']) ?></title>
    <meta name="description" content="<?= e($t['hero_text']) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body data-market="<?= e($m) ?>" data-region="<?= e($t['region']) ?>" data-lang="<?= e($t['lang']) ?>" data-source="<?= e($source) ?>">

<header class="topbar">
    <div class="container topbar-inner">
        <a href="?m=<?= e($m) ?>" class="logo">Scale<span>Up</span> <small>LATAM &amp; Europe</small></a>
        <nav class="menu" id="menu">
            <?php foreach ($t['nav'] as $i => $label): ?>
                <a href="#<?= $ids[$i] ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="markets" aria-label="Mercado / idioma">
            <?php foreach (MARKETS as $key => $mk): ?>
                <a href="?m=<?= $key ?>" class="<?= $key === $m ? 'active' : '' ?>" title="<?= e($mk['label']) ?>"><span class="full"><?= e($mk['label']) ?></span><span class="short"><?= strtoupper($key) ?></span></a>
            <?php endforeach; ?>
        </div>
        <button class="burger" aria-label="Menu" onclick="document.getElementById('menu').classList.toggle('open')">☰</button>
    </div>
</header>

<section class="hero region-<?= strtolower($t['region']) ?>">
    <div class="container hero-grid">
        <div>
            <span class="tag"><?= e($t['hero_tag']) ?></span>
            <h1><?= e($t['hero_title']) ?></h1>
            <p class="lead"><?= e($t['hero_text']) ?></p>
            <div class="cta-row">
                <a href="#contato" class="btn btn-primary" data-cta="hero_primary"><?= e($t['cta_primary']) ?></a>
                <a href="<?= e($secondaryHref) ?>" target="_blank" rel="noopener" class="btn btn-ghost" data-cta="hero_secondary"><?= e($t['cta_secondary']) ?></a>
            </div>
            <ul class="trust">
                <?php foreach ($t['trust'] as $item): ?><li>✓ <?= e($item) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <div class="hero-card">
            <div class="map">
                <div class="pin pin-latam"><b>LATAM</b><span>Brasil · México · Argentina · Chile · Colômbia</span></div>
                <div class="pin pin-eu"><b>EUROPE</b><span>Portugal · España · France · Deutschland · Italia</span></div>
                <svg viewBox="0 0 300 200" class="route" aria-hidden="true"><path d="M70 150 C 120 40, 200 40, 240 70" /></svg>
            </div>
            <div class="mini-funnel">
                <?php foreach ($t['funnel'] as $i => $f): ?>
                    <div style="--w:<?= 100 - $i * 15 ?>%"><?= e($f[2]) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section id="problemas" class="section">
    <div class="container">
        <h2><?= e($t['problems_title']) ?></h2>
        <div class="grid grid-5">
            <?php foreach ($t['problems'] as [$icon, $title, $text]): ?>
                <article class="card"><div class="icon"><?= $icon ?></div><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="metodo" class="section alt">
    <div class="container">
        <h2><?= e($t['method_title']) ?></h2>
        <div class="grid grid-4">
            <?php foreach ($t['method'] as [$n, $title, $text]): ?>
                <article class="step"><span><?= $n ?></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="funil" class="section">
    <div class="container">
        <h2><?= e($t['funnel_title']) ?></h2>
        <div class="funnel">
            <?php foreach ($t['funnel'] as $i => [$stage, $desc, $metric]): ?>
                <div class="funnel-row" style="--w:<?= 100 - $i * 12 ?>%">
                    <strong><?= e($stage) ?></strong><span><?= e($desc) ?></span><em><?= e($metric) ?></em>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="mercados" class="section alt">
    <div class="container">
        <h2><?= e($t['markets_title']) ?></h2>
        <div class="grid grid-2">
            <article class="market latam <?= $t['region'] === 'LATAM' ? 'highlight' : '' ?>">
                <h3>🌎 <?= e($t['latam'][0]) ?></h3><p><?= e($t['latam'][1]) ?></p>
                <div class="chips"><span>WhatsApp</span><span>Instagram</span><span>Meta Ads</span><span>Google Ads</span><span>SEO</span></div>
            </article>
            <article class="market europe <?= $t['region'] === 'EUROPE' ? 'highlight' : '' ?>">
                <h3>🌍 <?= e($t['europe'][0]) ?></h3><p><?= e($t['europe'][1]) ?></p>
                <div class="chips"><span>LinkedIn</span><span>E-mail</span><span>Google Ads</span><span>SEO</span><span>GDPR</span></div>
            </article>
        </div>
    </div>
</section>

<section id="kpis" class="section">
    <div class="container">
        <h2><?= e($t['kpi_title']) ?></h2>
        <div class="kpi-list">
            <?php foreach ($t['kpis'] as $k): ?><div><?= e($k) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<section id="contato" class="section form-section">
    <div class="container form-grid">
        <div>
            <h2><?= e($t['form_title']) ?></h2>
            <p class="lead"><?= e($t['form_text']) ?></p>
            <div class="faq">
                <h3><?= e($t['faq_title']) ?></h3>
                <?php foreach ($t['faq'] as [$q, $a]): ?>
                    <details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details>
                <?php endforeach; ?>
            </div>
        </div>
        <form id="lead-form" class="lead-form" method="post" action="api.php?action=lead" novalidate>
            <input type="hidden" name="market" value="<?= e($m) ?>">
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label><?= e($t['f_name']) ?> *<input name="name" required maxlength="120" autocomplete="name"></label>
            <label><?= e($t['f_email']) ?> *<input name="email" type="email" required maxlength="160" autocomplete="email"></label>
            <div class="two">
                <label><?= e($t['f_phone']) ?><input name="phone" type="tel" maxlength="40" autocomplete="tel"></label>
                <label><?= e($t['f_company']) ?> *<input name="company" required maxlength="120" autocomplete="organization"></label>
            </div>
            <div class="two">
                <label><?= e($t['f_role']) ?><input name="role" maxlength="80"></label>
                <label><?= e($t['f_country']) ?> *
                    <select name="country" required>
                        <?php foreach ($t['countries'] as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label><?= e($t['f_interest']) ?>
                <select name="interest">
                    <?php foreach ($t['interests'] as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><?= e($t['f_message']) ?><textarea name="message" rows="3" maxlength="1000"></textarea></label>
            <label class="check"><input type="checkbox" name="consent" value="1" required> <?= e($t['f_consent']) ?></label>
            <button class="btn btn-primary full" type="submit" data-cta="form_submit"><?= e($t['f_submit']) ?></button>
            <p class="form-msg" id="form-msg" data-ok="<?= e($t['f_success']) ?>" data-err="<?= e($t['f_error']) ?>" role="status"></p>
        </form>
    </div>
</section>

<footer class="footer">
    <div class="container footer-inner">
        <div><strong><?= e(APP_NAME) ?></strong><br><small>Eyegis · Pierre Yves Claudon · <?= e($t['footer']) ?></small></div>
        <div class="footer-links">
            <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
            <a href="<?= e($email) ?>">E-mail</a>
            <a href="admin.php"><?= e($t['admin_link']) ?></a>
        </div>
    </div>
</footer>

<a href="<?= e($secondaryHref) ?>" class="float-cta" target="_blank" rel="noopener" data-cta="floating" aria-label="<?= e($t['cta_secondary']) ?>"><?= $t['region'] === 'EUROPE' ? '✉' : '💬' ?></a>

<script src="assets/app.js"></script>
</body>
</html>
