<?php
/** Gera dados simulados (visitas, leads, vendas e gastos) para demonstrar os KPIs. */
function seed_demo(PDO $pdo): void
{
    $sources = [
        // source => [peso de tráfego, taxa de conversão visita→lead, gasto por visita]
        'google'   => [30, 0.06, 4.5],
        'meta'     => [25, 0.04, 2.8],
        'linkedin' => [12, 0.08, 9.0],
        'organic'  => [22, 0.05, 0],
        'email'    => [6, 0.12, 0],
        'direct'   => [5, 0.03, 0],
    ];
    $markets = [
        'br' => ['LATAM', 'pt-BR', ['Brasil']],
        'es' => ['LATAM', 'es', ['México', 'Argentina', 'Chile', 'Colombia', 'Perú']],
        'eu' => ['EUROPE', 'en', ['Portugal', 'Spain', 'France', 'Germany', 'Italy', 'Netherlands']],
    ];
    $first = ['Ana', 'Bruno', 'Carla', 'Diego', 'Elena', 'Felipe', 'Giulia', 'Hugo', 'Inês', 'João', 'Lucía', 'Marco', 'Nina', 'Pablo', 'Sofia', 'Thomas', 'Valentina', 'Lukas', 'Camille', 'Mateo'];
    $last  = ['Silva', 'García', 'Rossi', 'Müller', 'Dubois', 'Santos', 'López', 'Costa', 'Martín', 'Schmidt', 'Ferreira', 'Moreau'];
    $companies = ['Nexa', 'Lumio', 'Orbita', 'Vértice', 'Kairo', 'Brava', 'Nordia', 'Solara', 'Fluxo', 'Altis'];
    $roles = ['CEO', 'CMO', 'Head de Growth', 'Gerente Comercial', 'Founder', 'Marketing Manager'];
    $msgs = ['Queremos entrar no México', 'Baixa conversão de leads', 'Expansão para Portugal', 'Medir ROI das campanhas', ''];

    $ev = $pdo->prepare("INSERT INTO events (type, region, language, source, label, session_id, created_at) VALUES (?,?,?,?,?,?,?)");
    $ld = $pdo->prepare("INSERT INTO leads (name, email, phone, company, role, country, region, language, interest, message, source, campaign, stage, deal_value, consent, created_at, updated_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?)");
    $spend = [];

    $pdo->beginTransaction();
    for ($day = 29; $day >= 0; $day--) {
        $date = date('Y-m-d', strtotime("-$day days"));
        foreach ($markets as $mk => [$region, $lang, $countries]) {
            foreach ($sources as $src => [$weight, $rate, $cpv]) {
                $w = $weight * ($region === 'EUROPE' && $src === 'linkedin' ? 1.8 : 1) * ($region === 'LATAM' && $src === 'meta' ? 1.5 : 1);
                $visits = random_int((int)($w * 0.5), (int)($w * 1.2));
                for ($i = 0; $i < $visits; $i++) {
                    $ts = $date . sprintf(' %02d:%02d:00', random_int(7, 22), random_int(0, 59));
                    $sid = bin2hex(random_bytes(6));
                    $ev->execute(['page_view', $region, $lang, $src, $mk, $sid, $ts]);
                    if (mt_rand() / mt_getrandmax() < 0.25) $ev->execute(['cta_click', $region, $lang, $src, 'hero_primary', $sid, $ts]);
                    if (mt_rand() / mt_getrandmax() < $rate * 1.8) $ev->execute(['form_start', $region, $lang, $src, 'lead_form', $sid, $ts]);
                    if (mt_rand() / mt_getrandmax() < $rate) {
                        $fn = $first[array_rand($first)]; $lnm = $last[array_rand($last)]; $co = $companies[array_rand($companies)];
                        $r = mt_rand() / mt_getrandmax();
                        $stage = $r < 0.45 ? 'lead' : ($r < 0.70 ? 'mql' : ($r < 0.84 ? 'sql' : ($r < 0.91 ? 'customer' : 'lost')));
                        $value = $stage === 'customer' ? random_int($region === 'EUROPE' ? 1800 : 900, $region === 'EUROPE' ? 5000 : 3000) : 0;
                        $ld->execute(["$fn $lnm", strtolower(strtr("$fn.$lnm@$co", ['ã' => 'a', 'ê' => 'e', 'í' => 'i', 'ü' => 'u', 'ú' => 'u', 'é' => 'e', 'á' => 'a'])) . '.example', null, $co, $roles[array_rand($roles)],
                            $countries[array_rand($countries)], $region, $lang, $region === 'EUROPE' ? 'Europe' : 'LATAM',
                            $msgs[array_rand($msgs)], $src, "{$src}_{$mk}", $stage, $value, $ts, $ts]);
                        $ev->execute(['lead', $region, $lang, $src, $mk, $sid, $ts]);
                    }
                    $spend[$region][$src] = ($spend[$region][$src] ?? 0) + $cpv;
                }
            }
        }
    }
    $up = $pdo->prepare('UPDATE campaigns SET spent = spent + ? WHERE id = (SELECT id FROM campaigns WHERE region = ? AND source = ? ORDER BY id LIMIT 1)');
    $add = $pdo->prepare('INSERT INTO campaigns (name, channel, source, region, budget, spent) VALUES (?,?,?,?,?,?)');
    $find = $pdo->prepare('SELECT COUNT(*) FROM campaigns WHERE region = ? AND source = ?');
    foreach ($spend as $region => $bySrc) {
        foreach ($bySrc as $src => $v) {
            if ($v <= 0) continue;
            $find->execute([$region, $src]);
            if ($find->fetchColumn()) $up->execute([round($v, 2), $region, $src]);
            else $add->execute(["Simulação $src $region", ucfirst($src), $src, $region, round($v * 1.2, 2), round($v, 2)]);
        }
    }
    $pdo->exec('UPDATE campaigns SET budget = MAX(budget, ROUND(spent * 1.15, 2))');
    $pdo->commit();
}
