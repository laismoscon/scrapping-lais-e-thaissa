<?php

date_default_timezone_set('America/Sao_Paulo');

// ARQUIVO JSON
$json = __DIR__ . "/dados.json";

if (!file_exists($json)) {
    die("❌ O arquivo dados.json não foi encontrado. Faça upload na pasta htdocs.");
}

$data = json_decode(file_get_contents($json), true);

$datas = [];
$views = [];
$likes = [];
$comments = [];

$last = end($data);

$title   = $last["title"];
$channel = $last["channel"];

foreach ($data as $row) {
    if (!isset($row["data"])) continue;

    $datas[]    = $row["data"];
    $views[]    = intval($row["views"]);
    $likes[]    = intval($row["likes"]);
    $comments[] = intval($row["comments"]);
}

function safe_last($arr) {
    return count($arr) ? $arr[count($arr)-1] : null;
}

function series_delta($array) {
    $out = [];
    for ($i=1; $i<count($array); $i++) {
        $out[] = $array[$i] - $array[$i-1];
    }
    return $out;
}

// =========================
// INSIGHTS
// =========================

$insights = [
    "growth_total"    => 0,
    "growth_percent"  => 0,
    "engagement_now"  => 0,
    "engagement_avg"  => 0,
    "peak_views"      => "",
    "peak_comments"   => ""
];

if (count($views) >= 2) {

    $v_ini = $views[0];
    $v_fim = safe_last($views);
    $delta = $v_fim - $v_ini;
    $percent = ($v_ini > 0) ? ($delta / $v_ini) * 100 : 0;

    $insights["growth_total"]   = $delta;
    $insights["growth_percent"] = $percent;

    $eng_series = [];
    for ($i=0; $i<count($views); $i++) {
        $eng_series[] = ($views[$i] > 0)
            ? ($likes[$i] / $views[$i]) * 100
            : 0;
    }

    $insights["engagement_now"] = safe_last($eng_series);
    $insights["engagement_avg"] = array_sum($eng_series) / count($eng_series);

    $dv = series_delta($views);
    $dc = series_delta($comments);

    if (count($dv)) {
        $max_dv = max($dv);
        $idx = array_search($max_dv, $dv);
        $insights["peak_views"] =
            "{$datas[$idx]} → {$datas[$idx+1]} (+"
            . number_format($max_dv, 0, ',', '.') . " views)";
    }

    if (count($dc)) {
        $max_dc = max($dc);
        $idx2 = array_search($max_dc, $dc);
        $insights["peak_comments"] =
            "{$datas[$idx2]} → {$datas[$idx2+1]} (+"
            . number_format($max_dc, 0, ',', '.') . " comentários)";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard de Monitoramento</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
    <h1>📺 Monitoramento — Web Scraping YouTube</h1>
    
    <!-- TEXTO CURTO ABAIXO DO TÍTULO -->
    <p style="max-width: 800px; margin: 10px auto 20px; color:#cbd5e1; font-size:15px;">
        Este projeto, desenvolvido para a disciplina <strong>Processos Decisórios e Sistemas de Apoio à Decisão</strong> 
        do curso de Sistemas de Informação do UniBarretos, 
        utiliza a API de scraping <strong>ScraperAPI</strong> para monitorar a evolução de um vídeoclipe recém-lançado por uma
        cantora pop no YouTube. Dados reais de visualizações, likes e comentários foram coletados ao longo da semana 
        e organizados em um JSON, permitindo a análise temporal e a construção deste dashboard.
    </p>

</header>

<!-- ==================== -->
<!-- SEÇÃO DE INFORMAÇÕES -->
<!-- ==================== -->

<section class="insights">

    <div class="cards" style="margin-bottom: 25px;">
        <div class="card">
            <h3>Última coleta</h3>
            <p class="big"><?= $last["data"] ?></p>
            <p class="sub"><strong>👀 Views:</strong> <?= number_format($last["views"], 0, ',', '.') ?></p>
            <p class="sub"><strong>👍 Likes:</strong> <?= number_format($last["likes"], 0, ',', '.') ?></p>
            <p class="sub"><strong>💬 Comentários:</strong> <?= number_format($last["comments"], 0, ',', '.') ?></p>
        </div>

        <div class="card" style="grid-column: span 2;">
            <h3>Vídeo</h3>
            <iframe width="100%" height="280"
                    style="border-radius: 12px;"
                    src="https://www.youtube.com/embed/7b9TyVtVeJo">
            </iframe>
        </div>
    </div>

    <h2>🔍 Análises inteligentes</h2>

    <div class="cards">

        <div class="card">
            <h3>Crescimento total de visualizações</h3>
            <p class="big">+<?= number_format($insights["growth_total"], 0, ',', '.') ?></p>
            <p class="sub">Variação: <?= number_format($insights["growth_percent"], 2, ',', '.') ?>%</p>
        </div>

        <div class="card">
            <h3>Taxa de engajamento (likes/views)</h3>
            <p class="big"><?= number_format($insights["engagement_now"], 2, ',', '.') ?>%</p>
            <p class="sub">Média do período: <?= number_format($insights["engagement_avg"], 2, ',', '.') ?>%</p>
        </div>

        <div class="card">
            <h3>Maiores picos detectados</h3>
            <p class="sub">📈 Views: <?= $insights["peak_views"] ?: "Aguardando dados…" ?></p>
            <p class="sub">💬 Comentários: <?= $insights["peak_comments"] ?: "Aguardando dados…" ?></p>
        </div>

    </div>

</section>

<!-- ==================== -->
<!-- GRÁFICOS -->
<!-- ==================== -->

<section class="charts">
    <h2>📊 Gráficos</h2>

    <div class="grid">

        <div class="panel">
            <h3>👀 Visualizações</h3>
            <canvas id="cViews"></canvas>
        </div>

        <div class="panel">
            <h3>👍 Likes</h3>
            <canvas id="cLikes"></canvas>
        </div>

        <div class="panel">
            <h3>💬 Comentários</h3>
            <canvas id="cComments"></canvas>
        </div>

    </div>
</section>

<footer>
    <p>Prova Prática NB2 — Laís Moscon & Thaissa Erran</p>
</footer>

<script>
const labels   = <?= json_encode($datas) ?>;
const views    = <?= json_encode($views) ?>;
const likes    = <?= json_encode($likes) ?>;
const comments = <?= json_encode($comments) ?>;

function makeChart(id, label, data) {
    new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: label,
                data: data,
                borderWidth: 2,
                tension: 0.25,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });
}

makeChart("cViews", "Visualizações", views);
makeChart("cLikes", "Likes", likes);
makeChart("cComments", "Comentários", comments);

</script>

</body>
</html>
