<?php

date_default_timezone_set('America/Sao_Paulo');

// CONFIG
$API_KEY = "56d51dcab377a0365e0728c36c342e7f";
$VIDEO_ID = "7b9TyVtVeJo";
$JSON_FILE = "dados.json";

// Monta URL ScraperAPI → YouTube
$url = "https://api.scraperapi.com/?api_key={$API_KEY}&url=" . urlencode("https://www.youtube.com/watch?v={$VIDEO_ID}");

// Baixa HTML via ScraperAPI
$html = @file_get_contents($url);

if (!$html) {
    die("❌ Erro ao acessar via ScraperAPI");
}

// ========================================================
// 1) EXTRAI VIEWS (funciona sempre)
// ========================================================
preg_match('/"viewCount":"(\d+)"/', $html, $mv);
$views = isset($mv[1]) ? intval($mv[1]) : 0;

// ========================================================
// 2) EXTRAIR LIKES – VÁRIOS FORMATO POSSÍVEIS
// ========================================================

$likes = null;

// Padrão comum
if (!$likes) {
    preg_match('/"label":"([\d,.]+) Likes"/', $html, $m1);
    if (isset($m1[1])) $likes = intval(str_replace([",","."], "", $m1[1]));
}

// Novo formato 2024+
if (!$likes) {
    preg_match('/"likeCount":"(\d+)"/', $html, $m2);
    if (isset($m2[1])) $likes = intval($m2[1]);
}

// Schema.org (JSON-LD)
if (!$likes) {
    if (preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $html, $jsonBlock)) {
        $json = json_decode($jsonBlock[1], true);
        if (isset($json["interactionStatistic"])) {
            foreach ($json["interactionStatistic"] as $stat) {
                if (($stat["interactionType"]["@type"] ?? "") === "LikeAction") {
                    $likes = intval($stat["userInteractionCount"]);
                }
            }
        }
    }
}

if (!$likes) $likes = 0;

// ========================================================
// 3) EXTRAIR COMENTÁRIOS – VÁRIOS FORMATO POSSÍVEIS
// ========================================================
$comments = null;

// Padrão antigo
if (!$comments) {
    preg_match('/"commentCount":"(\d+)"/', $html, $c1);
    if (isset($c1[1])) $comments = intval($c1[1]);
}

// Schema.org → CommentAction
if (!$comments) {
    if (preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $html, $jsonBlock)) {
        $json = json_decode($jsonBlock[1], true);
        if (isset($json["interactionStatistic"])) {
            foreach ($json["interactionStatistic"] as $stat) {
                if (($stat["interactionType"]["@type"] ?? "") === "CommentAction") {
                    $comments = intval($stat["userInteractionCount"]);
                }
            }
        }
    }
}

// Microdata
if (!$comments) {
    preg_match('/"commentCount":\{"simpleText":"([\d,.]+)"/', $html, $c2);
    if (isset($c2[1])) $comments = intval(str_replace([",","."], "", $c2[1]));
}

// fallback
if (!$comments) $comments = 0;

// ========================================================
// TÍTULO E CANAL
// ========================================================

preg_match('/"title":"(.*?)"/', $html, $mt);
$title = $mt[1] ?? "Sem título";

preg_match('/"ownerChannelName":"(.*?)"/', $html, $mc);
$channel = $mc[1] ?? "Desconhecido";

// ========================================================
// SALVAR NO JSON
// ========================================================

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

echo "=====================================\n";
echo "  Scraping via ScraperAPI (YouTube)  \n";
echo "=====================================\n";
echo "📅 Data: {$new['data']}\n";
echo "👀 Views: {$new['views']}\n";
echo "👍 Likes: {$new['likes']}\n";
echo "💬 Comentários: {$new['comments']}\n";
