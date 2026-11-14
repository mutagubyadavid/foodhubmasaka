<?php
/** pesajet-status.php - polled by pay-pesajet.php every few seconds. Returns JSON. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pesajet.php';
require_login();
header('Content-Type: application/json');
header('Cache-Control: no-store');

$user = current_user();
$ref  = clean($_GET['ref'] ?? '');
$txn  = pesajet_find_txn_for_user($ref, (int)$user['user_id']);
if (!$txn) { echo json_encode(['status' => 'unknown']); exit; }

session_write_close();   // don't block other pages while we call the gateway
$status = $txn['status'];

if (in_array($status, PJ_ACTIVE_STATUSES, true)) {
    $st = db()->prepare('SELECT (last_checked_at IS NULL OR last_checked_at < (NOW() - INTERVAL 3 SECOND)) FROM pesajet_transactions WHERE id = ?');
    $st->execute([$txn['id']]);
    if ((int)$st->fetchColumn() === 1) {          // ask PesaJet at most every 3 seconds
        try {
            $r = pesajet_refresh((int)$txn['id'], 'poll');
            $status = $r['status'];
        } catch (Throwable $e) { error_log('PesaJet status poll: ' . $e->getMessage()); }
    }
}

$out = ['status' => $status];
if ($status === 'paid') $out['redirect'] = url('order-confirmation.php?order=' . $txn['order_number']);
echo json_encode($out);
