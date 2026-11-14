<?php
/** Authenticated MTN MoMo request-to-pay and status page. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/mtn-momo.php';
require_login();
$user = current_user();
$order = get_order_by_number($_GET['order'] ?? '', (int)$user['user_id']);
if (!$order) { flash_set('error', 'Order not found.'); redirect('account.php?tab=orders'); }
if ($order['payment_method'] !== 'mobile_money' || $order['order_status'] === 'cancelled') {
    redirect('order-confirmation.php?order=' . urlencode($order['order_number']));
}

$error = '';
$notice = '';
if (is_post()) {
    csrf_check();
    $pdo = db();
    try {
        if (!mtn_momo_is_configured()) {
            throw new RuntimeException('MTN Mobile Money is not configured. Please contact the site administrator.');
        }
        if ($order['payment_status'] === 'paid') {
            redirect('order-confirmation.php?order=' . urlencode($order['order_number']));
        }

        if (($_POST['action'] ?? '') === 'check') {
            if (empty($order['transaction_ref']) || !preg_match('/^[0-9a-f-]{36}$/i', $order['transaction_ref'])) {
                throw new RuntimeException('Send a payment request before checking its status.');
            }
            $providerStatus = mtn_momo_get_payment_status($order['transaction_ref']);
            if ($providerStatus === 'SUCCESSFUL') {
                $pdo->prepare('UPDATE payments SET payment_status = "paid", paid_at = COALESCE(paid_at, NOW()) WHERE order_id = ? AND payment_status <> "paid"')
                    ->execute([$order['order_id']]);
                redirect('order-confirmation.php?order=' . urlencode($order['order_number']));
            }
            if (in_array($providerStatus, ['FAILED', 'REJECTED'], true)) {
                $pdo->prepare('UPDATE payments SET payment_status = "failed" WHERE order_id = ? AND payment_status <> "paid"')
                    ->execute([$order['order_id']]);
                $notice = 'MTN reports that this payment was not completed. You can request payment again below.';
            } else {
                $notice = 'Payment is still pending. Approve the request on your MTN phone, then check again.';
            }
        } elseif (($_POST['action'] ?? '') === 'start') {
            $reference = $order['payment_status'] === 'pending'
                && preg_match('/^[0-9a-f-]{36}$/i', (string)$order['transaction_ref'])
                ? $order['transaction_ref']
                : mtn_momo_new_reference();
            $isTest = MTN_MOMO_TARGET_ENVIRONMENT === 'sandbox' ? 1 : 0;
            $pdo->prepare('UPDATE payments SET payment_status = "pending", transaction_ref = ?, is_test = ? WHERE order_id = ? AND payment_status <> "paid"')
                ->execute([$reference, $isTest, $order['order_id']]);
            mtn_momo_request_to_pay($reference, $order);
            $notice = 'Payment request sent. Approve it on your MTN phone, then check the payment status here.';
        } else {
            throw new RuntimeException('Invalid payment action.');
        }
    } catch (Throwable $ex) {
        $error = $ex instanceof RuntimeException
            ? $ex->getMessage()
            : 'We could not process the MTN payment request. Please try again.';
        if (!($ex instanceof RuntimeException)) error_log('MTN MoMo payment error: ' . $ex->getMessage());
    }

    $order = get_order_by_number($order['order_number'], (int)$user['user_id']);
}

$page_title = 'MTN Mobile Money';
include __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:760px;padding-top:32px;padding-bottom:50px">
    <div class="form-card">
        <h1 style="font-size:26px">MTN Mobile Money</h1>
        <p>Order <strong><?= e($order['order_number']) ?></strong> for <strong><?= e(format_ugx($order['total_amount'])) ?></strong></p>
        <p>A payment request will be sent to <?= e($order['customer_phone']) ?>. Approve it using your MTN Mobile Money PIN on your phone. This site will never ask you for your PIN.</p>

        <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($notice): ?><div class="alert alert-warning" role="status"><?= e($notice) ?></div><?php endif; ?>
        <p>Payment status: <?= status_badge($order['payment_status'], true) ?></p>
        <?php if ($order['transaction_ref']): ?><p class="muted">Payment reference: <?= e($order['transaction_ref']) ?></p><?php endif; ?>

        <?php if ($order['payment_status'] !== 'paid'): ?>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <form method="post" data-loading><?= csrf_field() ?><input type="hidden" name="action" value="start">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-mobile-screen-button"></i> <?= $order['payment_status'] === 'failed' ? 'Retry payment request' : ($order['transaction_ref'] ? 'Retry payment request' : 'Send payment request') ?></button>
                </form>
                <?php if ($order['transaction_ref'] && preg_match('/^[0-9a-f-]{36}$/i', $order['transaction_ref'])): ?>
                    <form method="post" data-loading><?= csrf_field() ?><input type="hidden" name="action" value="check">
                        <button class="btn btn-outline" type="submit"><i class="fa-solid fa-rotate"></i> Check payment status</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <p style="margin-top:18px"><a href="<?= e(url('order-confirmation.php?order=' . urlencode($order['order_number']))) ?>">Return to order details</a></p>
    </div>
</div>
<?php $show_newsletter = false; include __DIR__ . '/includes/footer.php'; ?>