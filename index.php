<?php

date_default_timezone_set('America/Sao_Paulo');

// URL RAW do GitHub
$github_json_url = "https://raw.githubusercontent.com/laismoscon/scrapping-lais-e-thaissa/main/dados.json";

// Função para buscar JSON mesmo se fopen estiver bloqueado
function fetchJson($url) {
    $json = @file_get_contents($url);
    if ($json !== false) return $json;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $json = curl_exec($ch);
    curl_close($ch);

    return $json;
}

$jsonData = fetchJson($github_json_url);

if (!$jsonData) { die("❌ Erro ao carregar dados do GitHub."); }

$data = json_decode($jsonData, true);
if (!is_array($data)) { die("❌ JSON inválido."); }

// PREPARA ARRAYS
$datas = [];
$views = [];
$likes = [];
$comments = [];

foreach ($data as $row) {
    if (!isset($row["data"])) continue;

    $datas[]    = $row["data"];
    $views[]    = intval($row["views"]);
    $likes[]    = intval($row["likes"]);
    $comments[] = intval($row["comments"]);
}

$last = end($data);
$title = $last["title"];
$channel = $last["channel"];

// Funções auxiliares
function safe_last($a){ return count($a) ? $a[count($a)-1] : 0; }
function delta($a){ $d=[]; for($i=1;$i<count($a);$i++) $d[]=$a[$i]-$a[$i-1]; return $d; }

// ---------------------
// INSIGHTS BÁSICOS
// ---------------------

$insights = [];
$insights["growth_total"]   = safe_last($views) - $views[0];
$insights["growth_percent"] = ($views[0] > 0) ? ($insights["growth_total"] / $views[0] * 100) : 0;

$eng_series = [];
foreach ($views as $i=>$v){
    $eng_series[] = $v>0 ? ($likes[$i]/$v)*100 : 0;
}
$insights["engagement_now"] = safe_last($eng_series);
$insights["engagement_avg"] = array_sum($eng_series)/count($eng_series);

// Picos
$dv = delta($views);
$dc = delta($comments);

if (count($dv)>0){
    $idx = array_search(max($dv), $dv);
    $insights["peak_views"] = "{$datas[$idx]} → {$datas[$idx+1]} (+".number_format($dv[$idx],0,',','.')." views)";
}

if (count($dc)>0){
    $idx2 = array_search(max($dc), $dc);
    $insights["peak_comments"] = "{$datas[$idx2]} → {$datas[$idx2+1]} (+".number_format($dc[$idx2],0,',','.')." comentários)";
}

// ---------------------
// INSIGHTS AVANÇADOS
// ---------------------

// 1) Velocidade média de crescimento
$velocity_avg = count($dv)>0 ? array_sum($dv)/count($dv) : 0;
$velocity_now = safe_last($dv);

// 2) Tendência (acelerando/desacelerando)
if ($velocity_now > $velocity_avg){
    $trend = "Acelerando 📈";
} elseif ($velocity_now < $velocity_avg){
    $trend = "Desacelerando 📉";
} else {
    $trend = "Estável ➖";
}

// 3) Classificação do engajamento (viralidade)
$ratio = $insights["engagement_now"];
if ($ratio > 5) $viral = "Altíssimo (tendência forte de viralização) 🔥";
elseif ($ratio > 3) $viral = "Bom engajamento 👍";
elseif ($ratio > 1) $viral = "Médio 🟡";
else $viral = "Baixo engajamento ⚠️";

// 4) Detecção da fase da curva (ciclo de viralização)
$phase = "";
if ($velocity_now > ($velocity_avg * 1.5)){
    $phase = "Fase de Explosão 🚀 (crescimento muito acima da média)";
} elseif ($velocity_now > ($velocity_avg * 0.7)){
    $phase = "Fase de Estabilização 📊 (crescimento consistente)";
} else {
    $phase = "Fase de Saturação ⚠️ (queda natural do alcance)";
}

// 5) Regressão Linear simples (previsão)
function linear_regression($y){
    $n = count($y);
    $x = range(1,$n);
    $sumx = array_sum($x);
    $sumy = array_sum($y);
    $sumxy = 0; $sumxx = 0;
    for($i=0;$i<$n;$i++){
        $sumxy += $x[$i]*$y[$i];
        $sumxx += $x[$i]*$x[$i];
    }
    $m = ($n*$sumxy - $sumx*$sumy) / ($n*$sumxx - $sumx*$sumx);
    $b = ($sumy - $m*$sumx)/$n;
    return [$m,$b];
}

list($m,$b) = linear_regression($views);
$prediction_24h = $m*(count($views)+24) + $b;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard – Scraping YouTube</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
    <h1>📺 Monitoramento — Web Scraping YouTube</h1>

    <p style="max-width: 800px; margin: 10px auto 20px; color:#cbd5e1; font-size:15px;">
        Este projeto, desenvolvido para a disciplina <strong>Processos Decisórios e Sistemas de Apoio à Decisão</strong>,
        utilizou <strong>PHP</strong> para realizar <strong>Web Scraping</strong> do YouTube via <strong>ScraperAPI</strong>. 
        Os dados são coletados automaticamente através do GitHub Actions e exibidos neste dashboard hospedado no InfinityFree,
        demonstrando como métricas digitais podem apoiar decisões estratégicas ao analisar tendências, engajamento e 
        comportamento do público ao longo do tempo.
    </p>

    <p><strong><?= htmlspecialchars($title) ?></strong> | Canal: <?= htmlspecialchars($channel) ?></p>
</header>

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
            <iframe width="100%" height="280" style="border-radius: 12px;"
                    src="https://www.youtube.com/embed/7b9TyVtVeJo"></iframe>
        </div>
    </div>

    <h2>🔍 Análises Inteligentes</h2>

    <div class="cards">

        <div class="card">
            <h3>Potencial de Viralização</h3>
            <p class="big"><?= number_format($ratio,2,',','.') ?>%</p>
            <p class="sub"><?= $viral ?></p>
        </div>

        <div class="card">
            <h3>Velocidade de Crescimento</h3>
            <p class="big"><?= number_format($velocity_now,0,',','.') ?> views/h</p>
            <p class="sub">Média: <?= number_format($velocity_avg,0,',','.') ?> views/h</p>
            <p class="sub"><strong><?= $trend ?></strong></p>
        </div>

        <div class="card">
            <h3>Fase Atual do Ciclo Viral</h3>
            <p class="sub"><strong><?= $phase ?></strong></p>
        </div>

        <div class="card">
            <h3>Previsão para +24h</h3>
            <p class="big"><?= number_format($prediction_24h,0,',','.') ?> views</p>
        </div>

        <div class="card">
            <h3>Maior pico de Views</h3>
            <p class="sub"><?= $insights["peak_views"] ?></p>
        </div>

        <div class="card">
            <h3>Maior pico de Comentários</h3>
            <p class="sub"><?= $insights["peak_comments"] ?></p>
        </div>

    </div>

</section>

<section class="charts">
    <h2>📊 Gráficos</h2>

    <div class="grid">
        <div class="panel">
            <h3>👀 Views</h3>
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
    <p>Prova Prática — Laís Moscon & Thaissa Erran</p>
</footer>

<script>
const labels   = <?= json_encode($datas) ?>;
const views    = <?= json_encode($views) ?>;
const likes    = <?= json_encode($likes) ?>;
const comments = <?= json_encode($comments) ?>;

function makeChart(id, label, data){
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

makeChart("cViews","Visualizações",views);
makeChart("cLikes","Likes",likes);
makeChart("cComments","Comentários",comments);

</script>

</body>
</html>
