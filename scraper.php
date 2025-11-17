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

// ----------------------------------------------
// FUNÇÃO: tenta vários padrões até encontrar
// ----------------------------------------------

function extract_match($html, $patterns) {
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            return $m[1];
        }
    }
    return null;
}

// ----------------------------------------------
// VIEWS (funciona bem sempre)
// ----------------------------------------------
$views = extract_match($html, [
    '/"viewCount":"(\d+)"/',
    '/"shortViewCount":"(\d+)"/',
    '/"viewCount":{"simpleText":"([\d,.]+)"/'
]);

$views = $views ? intval(str_replace([",","."], "", $views)) : 0;

// ----------------------------------------------
// LIKES (vários padrões)
// ----------------------------------------------
$likes = extract_match($html, [
    '/"label":"([\d,.]+) Likes"/',
    '/"likeCount":"(\d+)"/',
    '/"defaultText":"([\d,.]+)"/',
    '/"toggleButtonRenderer":{.*?"accessibilityData":{.*?"label":"([\d,.]+) likes"/s'
]);

$likes = $likes ? intval(str_replace([",","."], "", $likes)) : 0;

// ----------------------------------------------
// COMENTÁRIOS — AQUI ESTÁ A MÁGICA
// ----------------------------------------------
$comments = extract_match($html, [
    '/"commentCount":"(\d+)"/',
    '/"simpleText":"([\d,.]+) comments"/i',
    '/"commentsCount":{"simpleText":"([\d,.]+)"/',
    '/"accessibilityData":{.*?"label":"([\d,.]+) Comments"/si',
    '/"Comments":\{"simpleText":"([\d,.]+)"/',
    '/"commentCountText":\{"simpleText":"([\d,.]+)"/',
    '/"countText":\{"simpleText":"([\d,.]+)"/'
]);

$comments = $comments ? intval(str_replace([",","."], "", $comments)) : 0;

// ----------------------------------------------
// TÍTULO
// ----------------------------------------------
$title = extract_match($html, [
    '/"title":"(.*?)"/',
    '/"title":\{"simpleText":"(.*?)"/'
]);

// ----------------------------------------------
// CANAL
// ----------------------------------------------
$channel = extract_match($html, [
    '/"ownerChannelName":"(.*?)"/',
    '/"channel":"(.*?)"/'
]);

// ----------------------------------------------
// REGISTRO FINAL
// ----------------------------------------------
$new = [
    "data"      => date("Y-m-d H:i:s"),
    "views"     => $views,
    "likes"     => $likes,
    "comments"  => $comments,
    "title"     => $title ?: "Sem título",
    "channel"   => $channel ?: "Desconhecido"
];

// ----------------------------------------------
// GRAVA NO JSON
// ----------------------------------------------
if (!file_exists($JSON_FILE)) {
    file_put_contents($JSON_FILE, "[]");
}

$old = json_decode(file_get_contents($JSON_FILE), true);
$old[] = $new;

file_put_contents($JSON_FILE, json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Coleta registrada em {$new['data']}\n";
echo "👀 Views: {$new['views']} | 👍 Likes: {$new['likes']} | 💬 Comentários: {$new['comments']}\n";
