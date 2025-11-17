<?php

date_default_timezone_set('America/Sao_Paulo');

// CONFIGURAÇÃO
$API_KEY = "56d51dcab377a0365e0728c36c342e7f";
$VIDEO_ID = "7b9TyVtVeJo";
$JSON_FILE = "dados.json";

$target = "https://www.youtube.com/watch?v={$VIDEO_ID}";
$url = "https://api.scraperapi.com/?api_key={$API_KEY}&url=" . urlencode($target);

$html = @file_get_contents($url);

if (!$html) {
    die("❌ Erro ao acessar o YouTube via ScraperAPI");
}

// ================================
// EXTRAI AS VIEWS (confiável)
// ================================
preg_match('/"viewCount":"(\d+)"/', $html, $mv);
$views = isset($mv[1]) ? intval($mv[1]) : 0;

// ================================
// EXTRAÇÃO ROBUSTA DE LIKES
// ================================

// Tentativa 1 – Formato comum
if (!isset($likes)) {
    preg_match('/"label":"([\d,.]+) Likes"/', $html, $ml);
    if (isset($ml[1])) {
        $likes = intval(str_replace([",","."], "", $ml[1]));
    }
}

// Tentativa 2 – Novo formato de 2024/2025
if (!isset($likes)) {
    preg_match('/"defaultText":"([\d,.]+)"/', $html, $ml2);
    if (isset($ml2[1])) {
        $likes = intval(str_replace([",","."], "", $ml2[1]));
    }
}

// Tentativa 3 – Outro padrão possível
if (!isset($likes)) {
    preg_match('/"likeCount":"(\d+)"/', $html, $ml3);
    if (isset($ml3[1])) {
        $likes = intval($ml3[1]);
    }
}

// Se nada encontrado
if (!isset($likes)) {
    $likes = 0;
}

// ================================
// EXTRAÇÃO DE COMENTÁRIOS
// ================================

// Tentativa 1
preg_match('/"commentCount":"(\d+)"/', $html, $mcom);
$comments = isset($mcom[1]) ? intval($mcom[1]) : null;

// Tentativa 2 – outro formato
if ($comments === null) {
    preg_match('/"commentsCount":{"simpleText":"([\d,.]+)"/', $html, $mcom2);
    if (isset($mcom2[1])) {
        $comments = intval(str_replace([",","."], "", $mcom2[1]));
    }
}

// Tentativa 3 – fallback
if ($comments === null) {
    $comments = 0;
}

// ================================
// TÍTULO E CANAL
// ================================

preg_match('/"title":"(.*?)"/', $html, $mt);
$title = isset($mt[1]) ? $mt[1] : "";

preg_match('/"ownerChannelName":"(.*?)"/', $html, $mc);
$channel = isset($mc[1]) ? $mc[1] : "";

// ================================
// MONTA REGISTRO
// ================================

$new = [
    "data"      => date("Y-m-d H:i:s"),
    "views"     => $views,
    "likes"     => $likes,
    "comments"  => $comments,
    "title"     => $title,
    "channel"   => $channel
];

// ================================
// ADICIONA AO JSON
// ================================

if (!file_exists($JSON_FILE)) {
    file_put_contents($JSON_FILE, "[]");
}

$old = json_decode(file_get_contents($JSON_FILE), true);
$old[] = $new;

file_put_contents($JSON_FILE, json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Coleta registrada em {$new['data']}\n";
echo "👀 Views: {$new['views']} | 👍 Likes: {$new['likes']} | 💬 Comentários: {$new['comments']}\n";
