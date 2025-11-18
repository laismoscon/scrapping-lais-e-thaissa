<?php

date_default_timezone_set('America/Sao_Paulo');

// CONFIGURAÇÃO
$API_KEY  = "56d51dcab377a0365e0728c36c342e7f";
$VIDEO_ID = "7b9TyVtVeJo";
$JSON_FILE = "dados.json";

$target = "https://www.youtube.com/watch?v={$VIDEO_ID}";
$url = "https://api.scraperapi.com/?api_key={$API_KEY}&render=true&autoparse=true&url=" . urlencode($target);

$html = @file_get_contents($url);

if (!$html) {
    die("❌ Erro ao acessar via ScraperAPI");
}

// ================================
// EXTRAI AS VIEWS (funciona sempre)
// ================================
preg_match('/"viewCount":"(\d+)"/', $html, $mv);
$views = isset($mv[1]) ? intval($mv[1]) : 0;


// ================================
// EXTRAÇÃO DE LIKES (FUNCIONA NO SEU VÍDEO)
// ================================
$likes = null;

// Padrão principal → estava funcionando
preg_match('/"label":"([\d,.]+) Likes"/', $html, $ml);
if (isset($ml[1])) {
    $likes = intval(str_replace([",","."], "", $ml[1]));
}

// Tentativa secundária
if (!$likes) {
    preg_match('/"likeCount":"(\d+)"/', $html, $ml2);
    if (isset($ml2[1])) {
        $likes = intval($ml2[1]);
    }
}

// fallback
if (!$likes) $likes = 0;


// ================================
// EXTRAÇÃO DE COMENTÁRIOS (PODE VIR 0 NO SCRAPERAPI FREE)
// ================================
$comments = null;

// Padrão principal
preg_match('/"commentCount":"(\d+)"/', $html, $mc);
if (isset($mc[1])) {
    $comments = intval($mc[1]);
}

// fallback
if (!$comments) $comments = 0;


// ================================
// TÍTULO E CANAL
// ================================
preg_match('/"title":"(.*?)"/', $html, $mt);
$title = isset($mt[1]) ? $mt[1] : "Sem título";

preg_match('/"ownerChannelName":"(.*?)"/', $html, $mc);
$channel = isset($mc[1]) ? $mc[1] : "Desconhecido";


// ================================
// MONTAR REGISTRO
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
// SALVAR NO JSON
// ================================
if (!file_exists($JSON_FILE)) {
    file_put_contents($JSON_FILE, "[]");
}

$old = json_decode(file_get_contents($JSON_FILE), true);
$old[] = $new;

file_put_contents($JSON_FILE, json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "=====================================\n";
echo " ScraperAPI (HTML mode) - Coleta OK\n";
echo "=====================================\n";
echo "📅 {$new['data']}\n";
echo "👀 Views: {$new['views']}\n";
echo "👍 Likes: {$new['likes']}\n";
echo "💬 Comentários: {$new['comments']}\n";
