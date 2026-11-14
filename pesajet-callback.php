<?php
/**
 * pesajet-callback.php - PesaJet webhook receiver.
 * Webhook URL to register in the PesaJet dashboard:  https://foodhubmasaka.gt.tc/pesajet-callback.php
 * The webhook body is NEVER trusted: we find our transaction, then ask the PesaJet API for the real status.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pesajet.php';

http_response_code(200);
header('Content-Type: application/json');

$raw     = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) { echo json_encode(['ok' => false, 'error' => 'bad json']); exit; }

// Signature: HMAC-SHA256 (hex) of the JSON body without its "signature" field, header x-webhook-signature
$secret = pesajet_cfg('PESAJET_WEBHOOK_SECRET');
$sent   = strtolower(trim($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? ($payload['signature'] ?? '')));
$sigOk  = false;
if ($secret !== '' && $sent !== '') {
    $copy = $payload; unset($copy['signature']);
    foreach ([json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json_encode($copy), $raw] as $body) {
        if (hash_equals(hash_hmac('sha256', $body, $secret), $sent)) { $sigOk = true; break; }
    }
}

$pdo    = db();
$flat   = pesajet_normalize($payload);
$ref    = (string)($flat['merchant_reference'] ?? '');
$gwId   = (string)($flat['id'] ?? '');
$txn    = null;
$result = ($payload['event'] ?? '') === 'ping' ? 'ping received' : 'ignored: no matching transaction';

if (($payload['event'] ?? '') !== 'ping' && ($ref !== '' || $gwId !== '')) {
    $st = $pdo->prepare('SELECT id, merchant_reference FROM pesajet_transactions WHERE merchant_reference = ? OR gateway_reference = ? LIMIT 1');
    $st->execute([$ref, $gwId]);
    $txn = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($txn) {
        try {
            $r = pesajet_refresh((int)$txn['id'], 'webhook');
            $result = 'verified via API: ' . ($r['status'] ?? 'unknown');
        } catch (Throwable $e) {
            error_log('PesaJet webhook error: ' . $e->getMessage());
            $result = 'error: ' . mb_substr($e->getMessage(), 0, 150);
        }
    }
}

try {
    $pdo->prepare('INSERT INTO pesajet_webhook_events (merchant_reference, signature_valid, result, payload) VALUES (?,?,?,?)')
        ->execute([$txn['merchant_reference'] ?? ($ref ?: null), $sigOk ? 1 : 0, $result, mb_substr($raw, 0, 4000)]);
} catch (Throwable $e) { error_log('PesaJet webhook log failed: ' . $e->getMessage()); }

echo json_encode(['ok' => true]);
