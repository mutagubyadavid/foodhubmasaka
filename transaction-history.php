<?php
/** transaction-history.php - the logged-in customer's own payment attempts. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pesajet.php';
require_login();
$user = current_user();
$pdo  = db();

// Customer "delete": hides the row from their history; the record is kept for accounting/disputes.
if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'hide') {
        $st = $pdo->prepare("UPDATE pesajet_transactions SET hidden_by_user = 1
                             WHERE id = ? AND user_id = ? AND status NOT IN ('initiated','pending')");
        $st->execute([(int)($_POST['id'] ?? 0), $user['user_id']]);
        flash_set($st->rowCount() ? 'success' : 'error',
                  $st->rowCount() ? 'Payment removed from your history.' : 'That payment cannot be removed (it may still be in progress).');
    } elseif ($action === 'hide_all') {
        $st = $pdo->prepare("UPDATE pesajet_transactions SET hidden_by_user = 1
                             WHERE user_id = ? AND hidden_by_user = 0 AND status NOT IN ('initiated','pending')");
        $st->execute([$user['user_id']]);
        flash_set('success', 'Your payment history was cleared.');
    }
    redirect('transaction-history.php');
}

$st = $pdo->prepare('SELECT t.*, o.order_number, o.order_status
                     FROM pesajet_transactions t JOIN orders o ON o.order_id = t.order_id
                     WHERE t.user_id = ? AND t.hidden_by_user = 0 ORDER BY t.id DESC LIMIT 100');
$st->execute([$user['user_id']]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'My Payments';
$active_nav = '';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div class="banner-title-with-icon"><i class="fa-solid fa-receipt"></i><div><h1>My Payments</h1><p>Your payment history.</p></div></div></div></section>
<div class="container" style="margin-bottom:40px">
<?php if (!$rows): ?>
    <p>No online payments yet.</p>
<?php else: ?>
<form method="post" style="text-align:right;margin-bottom:10px" onsubmit="return confirm('Remove all completed, failed and expired payments from your history?');">
    <?= csrf_field() ?><input type="hidden" name="action" value="hide_all">
    <button class="btn btn-small" type="submit"><i class="fa-solid fa-trash"></i> Clear history</button>
</form>
<div style="overflow-x:auto"><table class="table" style="width:100%;border-collapse:collapse">
    <thead><tr><th align="left">Date</th><th align="left">Reference</th><th align="left">Order</th><th align="left">Method</th><th align="right">Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $active = in_array($r['status'], PJ_ACTIVE_STATUSES, true); ?>
        <tr style="border-top:1px solid #ddd">
            <td><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></td>
            <td><code><?= e($r['merchant_reference']) ?></code></td>
            <td><?= e($r['order_number']) ?></td>
            <td><?= e($r['method'] === 'mobile_money' ? ($r['network'] . ' Mobile Money') : 'Card') ?></td>
            <td align="right"><?= e(format_ugx($r['amount'])) ?></td>
            <td align="center"><?= pesajet_status_badge($r['status']) ?><?= $r['failure_reason'] && $r['status'] !== 'paid' ? '<br><small>' . e($r['failure_reason']) . '</small>' : '' ?></td>
            <td style="white-space:nowrap">
                <?php if ($r['status'] === 'paid'): ?><a href="<?= e(url('order-confirmation.php?order=' . $r['order_number'])) ?>">View order</a>
                <?php elseif ($r['order_status'] === 'pending'): ?><a href="<?= e(url('pay-pesajet.php?order=' . $r['order_number'])) ?>">Pay / retry</a><?php endif; ?>
                <?php if (!$active): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('Remove this payment from your history?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="hide"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-small" type="submit" title="Delete"><i class="fa-solid fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>