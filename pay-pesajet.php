<?php
/**
 * pay-pesajet.php - starts / resumes a PesaJet payment for an order the customer just placed.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pesajet.php';
require_login();
$user    = current_user();
$orderNo = clean($_GET['order'] ?? '');
$pdo     = db();
$debug   = true;   // TEMPORARY: remove after fixing

$st = $pdo->prepare('SELECT o.*, p.payment_id, p.payment_method, p.provider, p.payment_status
                     FROM orders o JOIN payments p ON p.order_id = o.order_id
                     WHERE o.order_number = ? AND o.user_id = ? LIMIT 1');
$st->execute([$orderNo, $user['user_id']]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { flash_set('error', 'Order not found.'); redirect('index.php'); }

if ($order['payment_method'] === 'cash_on_delivery' || $order['payment_status'] === 'paid') redirect('order-confirmation.php?order=' . $orderNo);
if (!pesajet_enabled()) redirect('pay-sandbox.php?order=' . $orderNo);        // old behaviour if gateway switched off

$errors = [];
$activeSql = "SELECT * FROM pesajet_transactions WHERE order_id = ? AND status IN ('initiated','pending')
              AND created_at > (NOW() - INTERVAL 15 MINUTE) ORDER BY id DESC LIMIT 1";

if (is_post()) {
    csrf_check();
    $phoneInput = clean($_POST['pay_phone'] ?? ($order['customer_phone'] ?? ''));
    $msisdn     = pesajet_msisdn($phoneInput);
    if ($order['payment_method'] === 'mobile_money' && !preg_match('/^2567\d{8}$/', $msisdn)) {
        $errors[] = 'Enter the Mobile Money number to charge (e.g. 0772 123 456).';
    }
    if (!$errors) {
        $created = null;
        try {
            $pdo->beginTransaction();
            $pdo->prepare('SELECT order_id FROM orders WHERE order_id = ? FOR UPDATE')->execute([$order['order_id']]);
            $paid = $pdo->prepare("SELECT COUNT(*) FROM pesajet_transactions WHERE order_id = ? AND status = 'paid'");
            $paid->execute([$order['order_id']]);
            if ((int)$paid->fetchColumn() > 0) { $pdo->commit(); redirect('order-confirmation.php?order=' . $orderNo); }

            $act = $pdo->prepare($activeSql); $act->execute([$order['order_id']]);
            if (!$act->fetch()) {                                         // only create if no live attempt
                $ref = 'PJ-' . $orderNo . '-' . strtoupper(bin2hex(random_bytes(4)));
                $pdo->prepare("INSERT INTO pesajet_transactions
                    (order_id, payment_id, user_id, merchant_reference, method, network, amount, currency,
                     customer_name, customer_phone, customer_email, status, is_test)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?, 'initiated', ?)")
                    ->execute([
                        $order['order_id'], $order['payment_id'], $user['user_id'], $ref, $order['payment_method'],
                        $order['payment_method'] === 'mobile_money' ? strtoupper((string)$order['provider']) : null,
                        $order['total_amount'], pesajet_cfg('PESAJET_CURRENCY', 'UGX'),
                        $order['customer_name'] ?? ($user['full_name'] ?? ($user['name'] ?? '')),
                        $msisdn ?: ($order['customer_phone'] ?? ''),
                        $order['customer_email'] ?? ($user['email'] ?? ''),
                        pesajet_is_test()
                    ]);
                $created = ['id' => (int)$pdo->lastInsertId(), 'merchant_reference' => $ref];
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('PesaJet init DB error: ' . $ex->getMessage());
            $errors[] = 'Could not start the payment. Please try again.'
                      . ($debug ? ' [DEBUG: ' . $ex->getMessage() . ']' : '');
        }

        if ($created && !$errors) {   // call the gateway OUTSIDE the DB transaction
            $row = $pdo->prepare('SELECT t.*, o.order_number FROM pesajet_transactions t JOIN orders o ON o.order_id=t.order_id WHERE t.id=?');
            $row->execute([$created['id']]);
            $txn = $row->fetch(PDO::FETCH_ASSOC);
            try {
                $res = pesajet_initiate($txn, $msisdn);
            } catch (Throwable $ex) {
                error_log('PesaJet initiate exception: ' . $ex->getMessage());
                $res = ['ok' => false, 'error' => $ex->getMessage(), 'data' => []];
            }
            if ($res['ok']) {
                $gwRef = $res['data']['id'] ?? null;                      // ADAPT
                $url   = $res['data']['checkout_url'] ?? null;            // ADAPT (card only)
                $pdo->prepare("UPDATE pesajet_transactions SET status='pending', gateway_reference=?, checkout_url=? WHERE id=?")
                    ->execute([$gwRef, $url, $created['id']]);
                $pdo->prepare("UPDATE payments SET payment_status='pending', transaction_ref=?, is_test=?, failure_reason=NULL WHERE order_id=?")
                    ->execute([$created['merchant_reference'], pesajet_is_test(), $order['order_id']]);
                if ($order['payment_method'] === 'card' && $url && parse_url($url, PHP_URL_SCHEME) === 'https') {
                    header('Location: ' . $url); exit;
                }
            } else {
                $pdo->prepare("UPDATE pesajet_transactions SET status='failed', failure_reason=? WHERE id=?")
                    ->execute([mb_substr((string)$res['error'], 0, 250), $created['id']]);
                error_log('PesaJet initiate failed: ' . $res['error']);
                $errors[] = 'The payment provider could not start this payment. Please try again or choose another method.'
                          . ($debug ? ' [DEBUG: ' . $res['error'] . ']' : '');
            }
        }
        if (!$errors) redirect('pay-pesajet.php?order=' . urlencode($orderNo));   // PRG: avoids re-POST on refresh
    }
}

$act = $pdo->prepare($activeSql); $act->execute([$order['order_id']]);
$active = $act->fetch(PDO::FETCH_ASSOC);
$last = $pdo->prepare('SELECT status, failure_reason FROM pesajet_transactions WHERE order_id = ? ORDER BY id DESC LIMIT 1');
$last->execute([$order['order_id']]);
$lastTxn = $last->fetch(PDO::FETCH_ASSOC);

$page_title = 'Pay for your order';
$active_nav = '';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div class="banner-title-with-icon"><i class="fa-solid fa-credit-card"></i><div><h1>Pay for order <?= e($orderNo) ?></h1><p>Total: <strong><?= e(format_ugx($order['total_amount'])) ?></strong></p></div></div></div></section>
<div class="container" style="max-width:640px;margin-bottom:40px">
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if (!$errors && !$active && $lastTxn && $lastTxn['status'] !== 'initiated'): ?>
    <div class="alert alert-warning">Your last payment attempt was <strong><?= e($lastTxn['status']) ?></strong><?= $lastTxn['failure_reason'] ? ': ' . e($lastTxn['failure_reason']) : '' ?>. You can try again below.</div>
<?php endif; ?>

<div class="form-card">
<?php if ($active): ?>
    <?php if ($order['payment_method'] === 'mobile_money'): ?>
        <h2><i class="fa-solid fa-mobile-screen-button"></i> Approve the payment on your phone</h2>
        <p>We sent a prompt to <strong><?= e($active['customer_phone']) ?></strong>. Enter your Mobile Money PIN to approve. Do not close this page.</p>
    <?php else: ?>
        <h2>Payment in progress</h2>
        <?php if ($active['checkout_url']): ?><p><a class="btn btn-primary" href="<?= e($active['checkout_url']) ?>">Continue to secure card payment</a></p><?php endif; ?>
    <?php endif; ?>
    <p id="pj-msg" class="form-hint"><i class="fa-solid fa-spinner fa-spin"></i> Waiting for confirmation&hellip;</p>
    <script>
    (function () {
        var ref = <?= json_encode($active['merchant_reference']) ?>, tries = 0, msg = document.getElementById('pj-msg');
        function poll() {
            fetch('pesajet-status.php?ref=' + encodeURIComponent(ref), {credentials: 'same-origin'})
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.status === 'paid' && d.redirect) { location.href = d.redirect; return; }
                    if (['failed', 'cancelled', 'expired', 'flagged'].indexOf(d.status) > -1) { location.reload(); return; }
                    if (++tries > 45) { msg.textContent = 'Still waiting. If you approved the payment, refresh in a minute.'; return; }
                    setTimeout(poll, 4000);
                })
                .catch(function () { setTimeout(poll, 6000); });
        }
        setTimeout(poll, 3000);
    })();
    </script>
<?php else: ?>
    <form method="post" data-loading>
        <?= csrf_field() ?>
        <?php if ($order['payment_method'] === 'mobile_money'): ?>
            <h2><i class="fa-solid fa-mobile-screen-button"></i> <?= e($order['provider']) ?> Mobile Money</h2>
            <div class="form-group"><label for="pay_phone">Number to charge</label>
                <input class="form-control" id="pay_phone" name="pay_phone" type="tel" required value="<?= e($order['customer_phone'] ?? '') ?>" placeholder="0772 123 456"></div>
            <button class="btn btn-primary btn-large" type="submit">Send payment prompt</button>
        <?php else: ?>
            <h2><i class="fa-regular fa-credit-card"></i> Card payment</h2>
            <p>You will be sent to PesaJet's secure page. Your card details never touch this website.</p>
            <button class="btn btn-primary btn-large" type="submit">Continue to secure payment</button>
        <?php endif; ?>
    </form>
<?php endif; ?>
</div>
<p class="text-center"><a href="<?= e(url('transaction-history.php')) ?>">My payments</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>