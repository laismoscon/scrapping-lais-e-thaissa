<?php

date_default_timezone_set('America/Sao_Paulo');

// CONFIG
$API_KEY = "56d51dcab377a0365e0728c36c342e7f";
$VIDEO_ID = "7b9TyVtVeJo";
$JSON_FILE = "dados.json";

$target = "https://www.youtube.com/watch?v={$VIDEO_ID}";
$url = "https://api.scraperapi.com/?api_key={$API_KEY}&url=" . urlencode($target);

$html = @file_get_contents($url);

if (!$html) {
    die("❌ Erro ao acessar via ScraperAPI");
}

// =====================================================
// 1. EXTRAIR TODOS OS JSONS INTERNOS DO YOUTUBE
// =====================================================

// Pega o JSON mais importante: ytInitialData
preg_match('/ytInitialData"\]\s*=\s*(\{.*?\});/', $html, $init1);

// Pega o JSON do player: ytInitialPlayerResponse
preg_match('/ytInitialPlayerResponse"\]\s*=\s*(\{.*?\});/', $html, $init2);

// Fallback adicional
preg_match('/var ytInitialData = (\{.*?\});/', $html, $init3);

$json_raw = $init1[1] ?? $init2[1] ?? $init3[1] ?? null;
$yt = $json_raw ? json_decode($json_raw, true) : null;


// =====================================================
// 2. EXTRAIR DADOS DO ytInitialPlayerResponse SE EXISTIR
// =====================================================

preg_match('/ytInitialPlayerResponse"\]\s*=\s*(\{.*?\});/', $html, $playerMatch);
$playerJSON = isset($playerMatch[1]) ? json_decode($playerMatch[1], true) : null;


// =====================================================
// VIEWS
// =====================================================

$views = 0;

if ($playerJSON && isset($playerJSON["videoDetails"]["viewCount"])) {
    $views = intval($playerJSON["videoDetails"]["viewCount"]);
} else {
    // fallback HTML
    preg_match('/"viewCount":"(\d+)"/', $html, $m);
    if (isset($m[1])) $views = intval($m[1]);
}


// =====================================================
// LIKES
// =====================================================

$likes = 0;

if ($playerJSON && isset($playerJSON["videoDetails"]["likes"])) {
    $likes = intval($playerJSON["videoDetails"]["likes"]);
} else {
    // fallback
    preg_match('/"label":"([\d,.]+) Likes"/', $html, $ml);
    if (isset($ml[1])) $likes = intval(str_replace([",","."], "", $ml[1]));
}


// =====================================================
// COMENTÁRIOS (PARTE MAIS IMPORTANTE)
// =====================================================

$comments = 0;

// 1) Procurar na aba de comentários do ytInitialData
if ($yt) {

    // Caminho típico:
    // contents.twoColumnWatchNextResults.results
    $results = $yt["contents"]["twoColumnWatchNextResults"]["results"]["results"]["contents"] ?? [];

    foreach ($results as $block) {
        if (isset($block["itemSectionRenderer"]["targetId"]) &&
            $block["itemSectionRenderer"]["targetId"] === "comments-section") {

            // Tenta extrair
            $commentHeader = $block["itemSectionRenderer"]["contents"][0]["commentsEntryPointHeaderRenderer"] ?? null;

            if ($commentHeader && isset($commentHeader["commentCount"]["simpleText"])) {
                $found = $commentHeader["commentCount"]["simpleText"];
                $comments = intval(str_replace([",","."], "", $found));
            }
        }
    }
}


// 2) Fallback no playerJSON
if ($comments === 0 && $playerJSON) {
    $counts = @$playerJSON["engagementPanels"];
    if ($counts) {
        foreach ($counts as $block) {
            if (isset($block["engagementPanelSectionListRenderer"]["panelIdentifier"]) &&
                $block["engagementPanelSectionListRenderer"]["panelIdentifier"] === "comments-section") {

                $cText = $block["engagementPanelSectionListRenderer"]["header"]["engagementPanelTitleHeaderRenderer"]["commentsCount"]["simpleText"] ?? "";
                if ($cText) {
                    $comments = intval(str_replace([",","."], "", $cText));
                }
            }
        }
    }
}


// 3) Fallback HTML simples
if ($comments === 0) {
    preg_match('/"commentCount":"(\d+)"/', $html, $c1);
    if (isset($c1[1])) $comments = intval($c1[1]);
}


// =====================================================
// TÍTULO E CANAL
// =====================================================

$title = $playerJSON["videoDetails"]["title"]
      ?? ($yt["videoDetails"]["title"] ?? "Sem título");

$channel = $playerJSON["videoDetails"]["author"]
        ?? ($yt["videoDetails"]["author"] ?? "Desconhecido");


// =====================================================
// MONTAR REGISTRO
// =====================================================

$new = [
    "data"      => date("Y-m-d H:i:s"),
    "views"     => $views,
    "likes"     => $likes,
    "comments"  => $comments,
    "title"     => $title,
    "channel"   => $channel
];


// =====================================================
// GRAVAR NO JSON
// =====================================================

if (!file_exists($JSON_FILE)) {
    file_put_contents($JSON_FILE, "[]");
}

$old = json_decode(file_get_contents($JSON_FILE), true);
$old[] = $new;

file_put_contents($JSON_FILE, json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// =====================================================

echo "✅ Coleta registrada em {$new['data']}\n";
echo "👀 Views: {$new['views']} | 👍 Likes: {$new['likes']} | 💬 Comentários: {$new['comments']}\n";
