<?php
/** pesajet-return.php - where a customer lands after leaving PesaJet. Sends them back to the payment page. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pesajet.php';
require_login();
$user = current_user();
$txn  = pesajet_find_txn_for_user(clean($_GET['ref'] ?? ''), (int)$user['user_id']);
if (!$txn) redirect('account.php?tab=orders');
redirect('pay-pesajet.php?order=' . urlencode($txn['order_number']));
