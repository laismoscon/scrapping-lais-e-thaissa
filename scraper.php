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

preg_match('/"viewCount":"(\d+)"/', $html, $mv);
preg_match('/"label":"([\d,.]+) Likes"/', $html, $ml);
preg_match('/"title":"(.*?)"/', $html, $mt);
preg_match('/"ownerChannelName":"(.*?)"/', $html, $mc);
preg_match('/"commentCount":"(\d+)"/', $html, $mcom);

$new = [
    "data"      => date("Y-m-d H:i:s"),
    "views"     => isset($mv[1]) ? intval($mv[1]) : 0,
    "likes"     => isset($ml[1]) ? intval(str_replace([",","."], "", $ml[1])) : 0,
    "comments"  => isset($mcom[1]) ? intval($mcom[1]) : 0,
    "title"     => isset($mt[1]) ? $mt[1] : "",
    "channel"   => isset($mc[1]) ? $mc[1] : ""
];

if (!file_exists($JSON_FILE)) {
    file_put_contents($JSON_FILE, "[]");
}

$old = json_decode(file_get_contents($JSON_FILE), true);
$old[] = $new;

file_put_contents($JSON_FILE, json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Coleta registrada em {$new['data']}";
