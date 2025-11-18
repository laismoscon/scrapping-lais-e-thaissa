<?php

date_default_timezone_set('America/Sao_Paulo');

// CONFIG
$API_KEY = "56d51dcab377a0365e0728c36c342e7f";
$VIDEO_ID = "7b9TyVtVeJo";
$JSON_FILE = "dados.json";

// URL com RENDER, DESKTOP e PAÍS definido
$target = "https://www.youtube.com/watch?v={$VIDEO_ID}";
$url = "https://api.scraperapi.com/?" . http_build_query([
    'api_key'     => $API_KEY,
    'url'         => $target,
    'render'      => 'true',
    'country'     => 'us',
    'device_type' => 'desktop'
]);

$html = @file_get_contents($url);

if (!$html) {
    die("❌ Erro ao acessar via ScraperAPI no modo renderizado.");
}

// ==========================================================
// 1) EXTRAIR JSON INTERNO ytInitialData e ytInitialPlayerResponse
// ==========================================================

preg_match('/ytInitialData"\] = (\{.*?\});/s', $html, $js1);
preg_match('/ytInitialPlayerResponse"\] = (\{.*?\});/s', $html, $js2);

$ytInitialData  = isset($js1[1]) ? json_decode($js1[1], true) : null;
$ytPlayer       = isset($js2[1]) ? json_decode($js2[1], true) : null;

// ==========================================================
// 2) EXTRAIR VIEWS
// ==========================================================

$views = 0;

if ($ytPlayer && isset($ytPlayer["videoDetails"]["viewCount"])) {
    $views = intval($ytPlayer["videoDetails"]["viewCount"]);
} else {
    preg_match('/"viewCount":"(\d+)"/', $html, $mviews);
    if (isset($mviews[1])) $views = intval($mviews[1]);
}

// ==========================================================
// 3) EXTRAIR LIKES (render mode = sempre aparece!)
// ==========================================================

$likes = 0;

// modo moderno: engagementPanels
if ($ytPlayer) {
    if (isset($ytPlayer["videoDetails"]["likes"])) {
        $likes = intval($ytPlayer["videoDetails"]["likes"]);
    }
}

// fallback via HTML
if ($likes == 0) {
    preg_match('/"label":"([\d,.]+) Likes"/', $html, $mlike);
    if (isset($mlike[1])) {
        $likes = intval(str_replace([",","."], "", $mlike[1]));
    }
}

// ==========================================================
// 4) EXTRAIR COMENTÁRIOS (funciona no render=true)
// ==========================================================

$comments = 0;

// caminho mais comum no render=true
if ($ytInitialData) {
    $contents = $ytInitialData["contents"]["twoColumnWatchNextResults"]["results"]["results"]["contents"] ?? [];

    foreach ($contents as $block) {
        if (isset($block["itemSectionRenderer"]["targetId"]) &&
            $block["itemSectionRenderer"]["targetId"] === "comments-section") {

            // este campo sempre aparece no modo render=true
            $count = $block["itemSectionRenderer"]["header"]["commentsEntryPointHeaderRenderer"]["commentCount"]["simpleText"]
                     ?? null;

            if ($count) {
                $comments = intval(str_replace([",","."], "", $count));
            }
        }
    }
}

// fallback simples
if ($comments == 0) {
    preg_match('/"commentCount":"(\d+)"/', $html, $mcom);
    if (isset($mcom[1])) $comments = intval($mcom[1]);
}

// ==========================================================
// 5) TÍTULO E CANAL
// ==========================================================

$title   = $ytPlayer["videoDetails"]["title"] ?? "Sem título";
$channel = $ytPlayer["videoDetails"]["author"] ?? "Desconhecido";

// ==========================================================
// 6) SALVAR
// ==========================================================

$new = [
    "data"      => date("Y-m-d H:i:s"),
    "views"     => $views,
    "likes"     => $likes,
    "comments"  => $comments,
    "title"     => $title,
    "channel"   => $channel
];

if (!file_exists($JSON_FILE)) {
    file_put_contents($JSON_FILE, "[]");
}

$old = json_decode(file_get_contents($JSON_FILE), true);
$old[] = $new;

file_put_contents($JSON_FILE, json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "==========================================\n";
echo " ScraperAPI (Render Mode) - Coleta Completa\n";
echo "==========================================\n";
echo "📅 Data: {$new['data']}\n";
echo "👀 Views: {$new['views']}\n";
echo "👍 Likes: {$new['likes']}\n";
echo "💬 Comentários: {$new['comments']}\n";
