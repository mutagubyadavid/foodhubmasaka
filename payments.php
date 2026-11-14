<?php
/** admin/payments.php - monitor all PesaJet transactions, re-verify pending ones, spot problems. */
require_once dirname(__DIR__) . '/includes/init.php';
require_once dirname(__DIR__) . '/includes/pesajet.php';
pesajet_require_admin();
$pdo = db();

// Action: re-verify one transaction with PesaJet
if (is_post() && ($_POST['action'] ?? '') === 'verify') {
    csrf_check();
    try {
        $r = pesajet_refresh((int)($_POST['id'] ?? 0), 'admin');
        flash_set('success', 'Verified with PesaJet. Status: ' . $r['status'] . (isset($r['error']) ? ' (gateway error: ' . $r['error'] . ')' : ''));
    } catch (Throwable $e) { error_log($e->getMessage()); flash_set('error', 'Verification failed. See server log.'); }
    redirect('admin/payments.php?' . http_build_query(array_filter(['status' => $_POST['f_status'] ?? '', 'q' => $_POST['f_q'] ?? ''])));
}

// Filters
$status = clean($_GET['status'] ?? '');
$q      = clean($_GET['q'] ?? '');
$from   = clean($_GET['from'] ?? '');
$to     = clean($_GET['to'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 50;

$where = []; $args = [];
if (in_array($status, ['initiated','pending','paid','failed','cancelled','expired','flagged'], true)) { $where[] = 't.status = ?'; $args[] = $status; }
if ($q !== '') { $where[] = '(t.merchant_reference LIKE ? OR t.gateway_reference LIKE ? OR o.order_number LIKE ? OR t.customer_phone LIKE ? OR t.customer_name LIKE ?)'; $like = "%$q%"; array_push($args, $like, $like, $like, $like, $like); }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 't.created_at >= ?'; $args[] = $from . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $where[] = 't.created_at <= ?'; $args[] = $to . ' 23:59:59'; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sum = $pdo->prepare("SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN t.status='paid' THEN t.amount END),0) paid_total,
        SUM(t.status='paid') paid_n, SUM(t.status IN ('initiated','pending')) pending_n, SUM(t.status IN ('failed','cancelled','expired')) failed_n, SUM(t.status='flagged') flagged_n
        FROM pesajet_transactions t JOIN orders o ON o.order_id=t.order_id $w");
$sum->execute($args);
$s = $sum->fetch(PDO::FETCH_ASSOC);

$list = $pdo->prepare("SELECT t.*, o.order_number, o.order_status FROM pesajet_transactions t JOIN orders o ON o.order_id=t.order_id
                       $w ORDER BY t.id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$list->execute($args);
$rows = $list->fetchAll(PDO::FETCH_ASSOC);
$pages = max(1, (int)ceil(((int)$s['n']) / $per));

$wh = $pdo->query('SELECT received_at, merchant_reference, signature_valid, result FROM pesajet_webhook_events ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Payments (Admin)';
$active_nav = '';
include dirname(__DIR__) . '/includes/header.php';   // swap for your admin layout if you have one
?>
<div class="container" style="margin:30px auto">
<h1>PesaJet Payments</h1>
<p>
    <strong>Paid:</strong> <?= e(format_ugx($s['paid_total'])) ?> (<?= (int)$s['paid_n'] ?>) &nbsp;|&nbsp;
    <strong>Pending:</strong> <?= (int)$s['pending_n'] ?> &nbsp;|&nbsp;
    <strong>Failed/expired:</strong> <?= (int)$s['failed_n'] ?> &nbsp;|&nbsp;
    <strong style="color:#8e24aa">Needs review:</strong> <?= (int)$s['flagged_n'] ?>
</p>

<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin:12px 0">
    <select name="status" class="form-control" style="max-width:160px"><option value="">All statuses</option>
        <?php foreach (['initiated','pending','paid','failed','cancelled','expired','flagged'] as $o): ?><option <?= $status === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select>
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Reference, order, phone, name" style="max-width:260px">
    <input class="form-control" type="date" name="from" value="<?= e($from) ?>" style="max-width:160px">
    <input class="form-control" type="date" name="to" value="<?= e($to) ?>" style="max-width:160px">
    <button class="btn btn-primary" type="submit">Filter</button>
</form>

<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:14px">
<thead><tr><th align="left">Created</th><th align="left">Reference / Gateway ID</th><th align="left">Order</th><th align="left">Customer</th><th align="left">Method</th><th align="right">Amount</th><th>Status</th><th align="left">Paid at</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr style="border-top:1px solid #ddd;<?= $r['status'] === 'flagged' ? 'background:#f8e8fc' : '' ?>">
    <td><?= e(date('d M H:i', strtotime($r['created_at']))) ?></td>
    <td><code><?= e($r['merchant_reference']) ?></code><br><small><?= e($r['gateway_reference'] ?? '-') ?></small></td>
    <td><?= e($r['order_number']) ?><br><small><?= e($r['order_status']) ?></small></td>
    <td><?= e($r['customer_name']) ?><br><small><?= e($r['customer_phone']) ?></small></td>
    <td><?= e($r['method']) ?><?= $r['network'] ? ' / ' . e($r['network']) : '' ?></td>
    <td align="right"><?= e(format_ugx($r['amount'])) ?></td>
    <td align="center"><?= pesajet_status_badge($r['status']) ?><?= $r['failure_reason'] ? '<br><small>' . e($r['failure_reason']) . '</small>' : '' ?></td>
    <td><?= $r['paid_at'] ? e(date('d M H:i', strtotime($r['paid_at']))) : '-' ?></td>
    <td><?php if ($r['status'] !== 'paid'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="f_status" value="<?= e($status) ?>"><input type="hidden" name="f_q" value="<?= e($q) ?>">
            <button class="btn btn-small" type="submit">Re-verify</button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9">No transactions found.</td></tr><?php endif; ?>
</tbody></table></div>

<p style="margin-top:12px">Page <?= $page ?> of <?= $pages ?>
    <?php $qs = ['status' => $status, 'q' => $q, 'from' => $from, 'to' => $to]; ?>
    <?php if ($page > 1): ?> &middot; <a href="?<?= e(http_build_query($qs + ['page' => $page - 1])) ?>">Previous</a><?php endif; ?>
    <?php if ($page < $pages): ?> &middot; <a href="?<?= e(http_build_query($qs + ['page' => $page + 1])) ?>">Next</a><?php endif; ?></p>

<h2 style="margin-top:30px">Recent webhooks</h2>
<table style="width:100%;font-size:13px;border-collapse:collapse"><thead><tr><th align="left">Received</th><th align="left">Reference</th><th>Signature</th><th align="left">Result</th></tr></thead><tbody>
<?php foreach ($wh as $h): ?><tr style="border-top:1px solid #eee"><td><?= e($h['received_at']) ?></td><td><?= e($h['merchant_reference'] ?? '-') ?></td><td align="center"><?= $h['signature_valid'] ? 'OK' : '<strong style="color:#c62828">BAD</strong>' ?></td><td><?= e($h['result'] ?? '') ?></td></tr><?php endforeach; ?>
</tbody></table>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
