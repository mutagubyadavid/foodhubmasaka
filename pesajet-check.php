<?php
require_once __DIR__ . '/includes/pesajet.php';
header('Content-Type: text/plain');

function mask($k){ return $k === '' ? '(empty)' : substr($k,0,5).'...'.substr($k,-3).'  (len '.strlen($k).')'; }
function call($headers, $label){
    $ch = curl_init('https://payments.pesajet.com/api/v1/payments/preview?amount=1000&provider=airtel&type=COLLECTION');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20, CURLOPT_HTTPHEADER=>$headers]);
    $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    echo str_pad($label, 34) . "HTTP $code  " . ($raw === false ? "curl: $err" : substr(preg_replace('/\s+/', ' ', $raw), 0, 110)) . "\n";
}

$main = pesajet_cfg('PESAJET_SECRET_KEY');
$alt  = pesajet_cfg('PESAJET_ALT_KEY');
echo "MAIN key: " . mask($main) . "\nALT  key: " . mask($alt) . "\n\n";

call(['Accept: application/json'], 'no key at all');
call(['X-API-Key: '.$main, 'Accept: application/json'], 'MAIN via X-API-Key');
call(['Authorization: Bearer '.$main, 'Accept: application/json'], 'MAIN via Bearer');
if ($alt !== '') {
    call(['X-API-Key: '.$alt, 'Accept: application/json'], 'ALT via X-API-Key');
    call(['Authorization: Bearer '.$alt, 'Accept: application/json'], 'ALT via Bearer');
}