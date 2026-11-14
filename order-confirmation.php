<?php
/**
 * order-confirmation.php - shown after checkout (and reachable from "My Orders").
 * Also fires the GA4 "purchase" event.
 */
require_once __DIR__ . '/includes/init.php';
require_login();
$user  = current_user();
$order = get_order_by_number($_GET['order'] ?? '', (int)$user['user_id']);
if (!$order) { flash_set('error', 'Order not found.'); redirect('account.php?tab=orders'); }

$fresh = (($_SESSION['last_order'] ?? '') === $order['order_number']);
if ($fresh) {
    $gaItems = [];
    foreach ($order['items'] as $it) $gaItems[] = ['item_id' => (string)$it['product_id'], 'item_name' => $it['product_name'], 'price' => (float)$it['unit_price'], 'quantity' => (int)$it['quantity']];
    ga_event('purchase', ['transaction_id' => $order['order_number'], 'currency' => 'UGX', 'value' => (float)$order['total_amount'],
                          'shipping' => (float)$order['delivery_fee'], 'items' => $gaItems]);
}

$page_title = 'Order ' . $order['order_number'];
include __DIR__ . '/includes/header.php';
$needsPayment = ($order['payment_method'] !== 'cash_on_delivery' && in_array($order['payment_status'], ['pending', 'failed'], true) && $order['order_status'] !== 'cancelled');
?>
<div class="container" style="max-width:900px;padding-bottom:50px">
    <div class="confirm-hero">
        <?php if ($needsPayment): ?>
            <div class="big-check" style="background:#fff3cd;color:#b56a00"><i class="fa-solid fa-hourglass-half"></i></div>
            <h1>Order received - payment pending</h1>
            <p class="muted">Your order is saved but payment has not yet been confirmed by MTN.</p>
        <?php else: ?>
            <div class="big-check"><i class="fa-solid fa-check"></i></div>
            <h1>Thank you, <?= e(explode(' ', $order['customer_name'])[0]) ?>! Your order is confirmed.</h1>
            <p class="muted">A digital receipt is shown below. We will prepare your food right away.</p>
        <?php endif; ?>
        <p>Order number: <strong class="text-red" style="font-size:20px"><?= e($order['order_number']) ?></strong></p>
    </div>

    <?php if ($needsPayment): ?>
        <div class="alert alert-warning" style="text-align:center">
            <?php if ($order['payment_method'] === 'mobile_money'): ?>
                <a class="btn btn-primary" href="<?= e(url('mtn-momo-payment.php?order=' . urlencode($order['order_number']))) ?>"><i class="fa-solid fa-mobile-screen-button"></i> Complete MTN payment</a>
            <?php else: ?>
                <p>This payment method is no longer available. Please contact us to arrange payment.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <h2>Order status</h2>
        <?php render_order_tracker($order['order_status']); ?>
        <p style="margin:0">Order: <?= status_badge($order['order_status']) ?> &nbsp; Payment: <?= status_badge($order['payment_status'], true) ?></p>
    </div>

    <div class="form-card">
        <h2>Items</h2>
        <div class="table-scroll"><table class="data-table">
            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th style="text-align:right">Total</th></tr></thead>
            <tbody>
            <?php foreach ($order['items'] as $it): ?>
                <tr><td><?= e($it['product_name']) ?></td><td><?= e(format_ugx($it['unit_price'])) ?></td><td><?= (int)$it['quantity'] ?></td><td style="text-align:right"><?= e(format_ugx($it['line_total'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <div style="max-width:320px;margin-left:auto;margin-top:12px">
            <div class="summary-row"><span>Subtotal</span><strong><?= e(format_ugx($order['subtotal'])) ?></strong></div>
            <?php if ($order['discount_amount'] > 0): ?><div class="summary-row discount"><span>Discount<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></span><strong>- <?= e(format_ugx($order['discount_amount'])) ?></strong></div><?php endif; ?>
            <div class="summary-row"><span>Delivery fee</span><strong><?= $order['delivery_fee'] > 0 ? e(format_ugx($order['delivery_fee'])) : 'FREE' ?></strong></div>
            <div class="summary-total" style="margin-bottom:0"><span>Total</span><strong><?= e(format_ugx($order['total_amount'])) ?></strong></div>
        </div>
    </div>

    <div class="form-card">
        <h2>Delivery &amp; payment</h2>
        <dl class="kv">
            <dt>Deliver to</dt><dd><?= e($order['customer_name']) ?>, <?= e($order['customer_phone']) ?></dd>
            <dt>Address</dt><dd><?= e($order['delivery_address']) ?> (<?= e($order['delivery_area']) ?>)</dd>
            <dt>Preferred time</dt><dd><?= e($order['preferred_time']) ?></dd>
            <?php if ($order['order_notes']): ?><dt>Your notes</dt><dd><?= e($order['order_notes']) ?></dd><?php endif; ?>
            <dt>Payment method</dt><dd><?= e(payment_method_label($order['payment_method'])) ?><?= $order['provider'] ? ' - ' . e($order['provider']) : '' ?></dd>
            <?php if ($order['transaction_ref']): ?><dt>Payment reference</dt><dd><?= e($order['transaction_ref']) ?></dd><?php endif; ?>
            <?php if ($order['rider_name']): ?><dt>Rider</dt><dd><?= e($order['rider_name']) ?> <?= e($order['rider_phone']) ?></dd><?php endif; ?>
            <dt>Placed on</dt><dd><?= e(format_date($order['created_at'])) ?></dd>
        </dl>
    </div>

    <p class="text-center">
        <a class="btn btn-primary" href="<?= e(url('products.php')) ?>">Continue Shopping</a>
        <a class="btn btn-outline" href="<?= e(url('account.php?tab=orders')) ?>">View My Orders</a>
    </p>
</div>
<?php $show_newsletter = false; include __DIR__ . '/includes/footer.php'; unset($_SESSION['last_order']); ?>
