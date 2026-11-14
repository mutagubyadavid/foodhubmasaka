<?php
/**
 * account.php - CUSTOMER ACCOUNT
 *   ?tab=overview (default) | orders | profile | password      ?order=FH-... shows one order
 */
require_once __DIR__ . '/includes/init.php';
require_login();
$user = current_user();
$uid  = (int)$user['user_id'];

$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'orders', 'profile', 'password'], true)) $tab = 'overview';
$errors = [];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // Customer "delete order": the order is hidden from their list (the record is kept for the shop's accounts).
    // Allowed: delivered or cancelled orders, and unpaid pending orders (these are cancelled first and the stock put back).
    if ($action === 'delete_order') {
        $o = get_order_by_number(clean($_POST['order'] ?? ''), $uid);
        if (!$o) {
            flash_set('error', 'Order not found.');
        } else {
            $st  = $o['order_status'];
            $pay = $o['payment_status'] ?? 'pending';
            $pdo = db();
            if (in_array($st, ['delivered', 'cancelled'], true)) {
                $pdo->prepare('UPDATE orders SET hidden_by_user = 1 WHERE order_id = ? AND user_id = ?')->execute([$o['order_id'], $uid]);
                flash_set('success', 'Order removed from your list.');
            } elseif ($st === 'pending' && !in_array($pay, ['paid', 'refunded'], true)) {
                $busy = 0;
                try {
                    $b = $pdo->prepare("SELECT COUNT(*) FROM pesajet_transactions WHERE order_id = ? AND status IN ('initiated','pending') AND created_at > (NOW() - INTERVAL 15 MINUTE)");
                    $b->execute([$o['order_id']]);
                    $busy = (int)$b->fetchColumn();
                } catch (Throwable $e) {}
                if ($busy) {
                    flash_set('error', 'A payment for this order is still in progress. Try again in a few minutes.');
                } else {
                    $pdo->beginTransaction();
                    try {
                        $up = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?');
                        foreach ($o['items'] as $it) if ($it['product_id']) $up->execute([$it['quantity'], $it['product_id']]);
                        $pdo->prepare("UPDATE orders SET order_status = 'cancelled', hidden_by_user = 1 WHERE order_id = ? AND user_id = ? AND order_status = 'pending'")
                            ->execute([$o['order_id'], $uid]);
                        $pdo->commit();
                        flash_set('success', 'Order cancelled and removed from your list.');
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        error_log('delete_order: ' . $e->getMessage());
                        flash_set('error', 'Could not remove the order. Please try again.');
                    }
                }
            } else {
                flash_set('error', 'This order cannot be deleted right now (it is being prepared, on its way, or already paid). Please contact us.');
            }
        }
        redirect('account.php?tab=orders');
    }

    if ($action === 'profile') {
        $name = clean($_POST['name'] ?? ''); $phone = normalize_phone($_POST['phone'] ?? '');
        $address = clean($_POST['address'] ?? ''); $area = clean($_POST['area'] ?? '');
        if (mb_strlen($name) < 2)                 $errors[] = 'Please enter your full name.';
        if ($phone === '')                        $errors[] = 'Please enter a valid Uganda phone number.';
        if (mb_strlen($address) < 5)              $errors[] = 'Please enter your delivery address.';
        if ($area !== '' && !isset(DELIVERY_AREAS[$area])) $area = '';
        if (!$errors) {
            $chk = db()->prepare('SELECT COUNT(*) FROM users WHERE phone = ? AND user_id <> ?'); $chk->execute([$phone, $uid]);
            if ($chk->fetchColumn()) $errors[] = 'That phone number is used by another account.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET full_name = ?, phone = ?, delivery_address = ?, delivery_area = ? WHERE user_id = ?')
                ->execute([$name, $phone, $address, $area ?: null, $uid]);
            flash_set('success', 'Your profile has been updated.');
            redirect('account.php?tab=profile');
        }
        $tab = 'profile';
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? '';
        $row = db()->prepare('SELECT password_hash FROM users WHERE user_id = ?'); $row->execute([$uid]);
        if (!password_verify($current, (string)$row->fetchColumn())) $errors[] = 'Your current password is not correct.';
        if (!valid_password($new))                                    $errors[] = 'New password must be at least 8 characters with a letter and a number.';
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash_set('success', 'Your password has been changed.');
            redirect('account.php?tab=password');
        }
        $tab = 'password';
    }
}

// Load data for the tabs
$viewOrder = null;
if (!empty($_GET['order'])) { $viewOrder = get_order_by_number($_GET['order'], $uid); $tab = 'orders'; }

$orders = [];
if ($tab === 'orders' || $tab === 'overview') {
    $stmt = db()->prepare('SELECT o.*, p.payment_status, p.payment_method FROM orders o LEFT JOIN payments p ON p.order_id = o.order_id
                           WHERE o.user_id = ? AND o.hidden_by_user = 0 ORDER BY o.created_at DESC');
    $stmt->execute([$uid]);
    $orders = $stmt->fetchAll();
}

$page_title = 'My Account';
$show_newsletter = false;
include __DIR__ . '/includes/header.php';
$fullUser = db()->prepare('SELECT * FROM users WHERE user_id = ?'); $fullUser->execute([$uid]); $fullUser = $fullUser->fetch();
?>
<div class="container account-layout">
    <aside>
        <div class="sidebar-box account-nav">
            <a href="?tab=overview" class="<?= $tab === 'overview' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Overview</a>
            <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>"><i class="fa-solid fa-receipt"></i> My Orders</a>
            <a href="?tab=profile" class="<?= $tab === 'profile' ? 'active' : '' ?>"><i class="fa-regular fa-id-card"></i> Profile &amp; Address</a>
            <a href="?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>"><i class="fa-solid fa-key"></i> Change Password</a>
            <a href="<?= e(url('logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <section>
        <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>

        <?php if ($tab === 'overview'): ?>
            <h1 style="font-size:28px">Hello, <?= e(explode(' ', $user['full_name'])[0]) ?>!</h1>
            <p class="muted">Manage your orders and delivery details here.</p>
            <div class="product-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin:20px 0">
                <div class="form-card" style="margin:0;text-align:center"><div class="script text-red" style="font-size:44px;line-height:1"><?= count($orders) ?></div>Orders placed</div>
                <div class="form-card" style="margin:0;text-align:center"><div class="script text-red" style="font-size:44px;line-height:1"><?= count(array_filter($orders, fn($o) => in_array($o['order_status'], ['pending','processing','out_for_delivery'], true))) ?></div>Active orders</div>
                <div class="form-card" style="margin:0;text-align:center"><div class="script text-red" style="font-size:44px;line-height:1"><?= cart_count() ?></div>Items in cart</div>
            </div>
            <div class="form-card"><h2>Delivery address</h2>
                <p style="margin:0"><?= e($fullUser['delivery_address']) ?><?= $fullUser['delivery_area'] ? ' (' . e($fullUser['delivery_area']) . ')' : '' ?></p>
                <p><a href="?tab=profile">Change address</a></p></div>
            <?php if ($orders): ?><h2 style="font-size:20px">Latest order</h2>
                <?php $o = $orders[0]; ?>
                <div class="form-card"><p style="margin:0 0 8px"><strong><?= e($o['order_number']) ?></strong> &middot; <?= e(format_date($o['created_at'])) ?> &middot; <?= e(format_ugx($o['total_amount'])) ?></p>
                    <?php render_order_tracker($o['order_status']); ?>
                    <a class="btn btn-outline btn-small" href="?order=<?= e(urlencode($o['order_number'])) ?>">View details</a></div>
            <?php endif; ?>

        <?php elseif ($tab === 'orders' && $viewOrder): $o = $viewOrder; ?>
            <p><a href="?tab=orders"><i class="fa-solid fa-arrow-left"></i> All orders</a></p>
            <h1 style="font-size:26px">Order <?= e($o['order_number']) ?></h1>
            <div class="form-card"><?php render_order_tracker($o['order_status']); ?>
                <p style="margin:0">Order: <?= status_badge($o['order_status']) ?> &nbsp; Payment: <?= status_badge($o['payment_status'], true) ?> &nbsp; <span class="muted"><?= e(payment_method_label($o['payment_method'])) ?></span></p></div>
            <div class="form-card"><h2>Items</h2><div class="table-scroll"><table class="data-table">
                <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th style="text-align:right">Total</th></tr></thead><tbody>
                <?php foreach ($o['items'] as $it): ?><tr><td><?= e($it['product_name']) ?></td><td><?= e(format_ugx($it['unit_price'])) ?></td><td><?= (int)$it['quantity'] ?></td><td style="text-align:right"><?= e(format_ugx($it['line_total'])) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <div style="max-width:300px;margin-left:auto;margin-top:10px">
                    <div class="summary-row"><span>Subtotal</span><strong><?= e(format_ugx($o['subtotal'])) ?></strong></div>
                    <?php if ($o['discount_amount'] > 0): ?><div class="summary-row discount"><span>Discount</span><strong>- <?= e(format_ugx($o['discount_amount'])) ?></strong></div><?php endif; ?>
                    <div class="summary-row"><span>Delivery</span><strong><?= $o['delivery_fee'] > 0 ? e(format_ugx($o['delivery_fee'])) : 'FREE' ?></strong></div>
                    <div class="summary-total" style="margin-bottom:0"><span>Total</span><strong><?= e(format_ugx($o['total_amount'])) ?></strong></div></div></div>
            <div class="form-card"><h2>Delivery</h2><dl class="kv">
                <dt>Address</dt><dd><?= e($o['delivery_address']) ?> (<?= e($o['delivery_area']) ?>)</dd>
                <dt>Preferred time</dt><dd><?= e($o['preferred_time']) ?></dd>
                <?php if ($o['rider_name']): ?><dt>Rider</dt><dd><?= e($o['rider_name']) ?> <?= e($o['rider_phone']) ?></dd><?php endif; ?>
                <dt>Placed</dt><dd><?= e(format_date($o['created_at'])) ?></dd></dl></div>
            <?php if ($o['payment_method'] === 'mobile_money' && in_array($o['payment_status'], ['pending','failed'], true) && $o['order_status'] !== 'cancelled'): ?>
                <a class="btn btn-primary" href="<?= e(url('mtn-momo-payment.php?order=' . urlencode($o['order_number']))) ?>">Complete MTN payment</a><?php endif; ?>

        <?php elseif ($tab === 'orders'): ?>
            <h1 style="font-size:26px">My Orders</h1>
            <?php if (!$orders): ?>
                <div class="empty-state"><i class="fa-solid fa-receipt"></i><h3>No orders yet</h3><a class="btn btn-primary" href="<?= e(url('products.php')) ?>">Start shopping</a></div>
            <?php else: ?>
                <div class="cart-box"><div class="table-scroll"><table class="data-table">
                    <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr></thead><tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr><td><strong><?= e($o['order_number']) ?></strong></td><td><?= e(format_date($o['created_at'])) ?></td><td><?= e(format_ugx($o['total_amount'])) ?></td>
                            <td><?= status_badge($o['order_status']) ?></td><td><?= status_badge($o['payment_status'], true) ?></td>
                            <td style="white-space:nowrap"><a class="btn btn-outline btn-small" href="?order=<?= e(urlencode($o['order_number'])) ?>">View</a>
                                <?php
                                $canDelete = in_array($o['order_status'], ['delivered', 'cancelled'], true)
                                          || ($o['order_status'] === 'pending' && !in_array($o['payment_status'] ?? 'pending', ['paid', 'refunded'], true));
                                if ($canDelete):
                                    $msg = $o['order_status'] === 'pending' ? 'Cancel and delete this order?' : 'Remove this order from your list?'; ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('<?= e($msg) ?>');">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_order"><input type="hidden" name="order" value="<?= e($o['order_number']) ?>">
                                    <button class="btn btn-outline btn-small" type="submit" title="Delete order"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                <?php endif; ?></td></tr>
                    <?php endforeach; ?></tbody></table></div></div>
            <?php endif; ?>

        <?php elseif ($tab === 'profile'): ?>
            <h1 style="font-size:26px">Profile &amp; Address</h1>
            <form method="post" class="form-card" data-loading>
                <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                <div class="form-group"><label for="name">Full name</label><input class="form-control" id="name" name="name" required maxlength="100" value="<?= e($_POST['name'] ?? $fullUser['full_name']) ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>Email (cannot be changed)</label><input class="form-control" value="<?= e($fullUser['email']) ?>" disabled></div>
                    <div class="form-group"><label for="phone">Phone</label><input class="form-control" id="phone" name="phone" required value="<?= e($_POST['phone'] ?? $fullUser['phone']) ?>"></div>
                </div>
                <div class="form-group"><label for="area">Delivery area</label><select class="form-control" id="area" name="area"><option value="">- choose -</option>
                    <?php foreach (DELIVERY_AREAS as $a => $t): ?><option value="<?= e($a) ?>" <?= (($_POST['area'] ?? $fullUser['delivery_area']) === $a) ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="address">Delivery address</label><textarea class="form-control" id="address" name="address" required rows="2" maxlength="255"><?= e($_POST['address'] ?? $fullUser['delivery_address']) ?></textarea></div>
                <button class="btn btn-primary" type="submit">Save changes</button>
            </form>

        <?php else: ?>
            <h1 style="font-size:26px">Change Password</h1>
            <form method="post" class="form-card" style="max-width:520px" data-loading>
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <div class="form-group"><label for="cp">Current password</label><input class="form-control" id="cp" type="password" name="current_password" required autocomplete="current-password"></div>
                <div class="form-group"><label for="np">New password</label><input class="form-control" id="np" type="password" name="new_password" required minlength="8" autocomplete="new-password"><p class="form-hint">At least 8 characters, with a letter and a number.</p></div>
                <button class="btn btn-primary" type="submit">Update password</button>
            </form>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?><?php
/**
 * account.php - CUSTOMER ACCOUNT
 *   ?tab=overview (default) | orders | profile | password      ?order=FH-... shows one order
 */
require_once __DIR__ . '/includes/init.php';
require_login();
$user = current_user();
$uid  = (int)$user['user_id'];

$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'orders', 'profile', 'password'], true)) $tab = 'overview';
$errors = [];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // Customer "delete order": the order is hidden from their list (the record is kept for the shop's accounts).
    // Allowed: delivered or cancelled orders, and unpaid pending orders (these are cancelled first and the stock put back).
    if ($action === 'delete_order') {
        $o = get_order_by_number(clean($_POST['order'] ?? ''), $uid);
        if (!$o) {
            flash_set('error', 'Order not found.');
        } else {
            $st  = $o['order_status'];
            $pay = $o['payment_status'] ?? 'pending';
            $pdo = db();
            if (in_array($st, ['delivered', 'cancelled'], true)) {
                $pdo->prepare('UPDATE orders SET hidden_by_user = 1 WHERE order_id = ? AND user_id = ?')->execute([$o['order_id'], $uid]);
                flash_set('success', 'Order removed from your list.');
            } elseif ($st === 'pending' && !in_array($pay, ['paid', 'refunded'], true)) {
                $busy = 0;
                try {
                    $b = $pdo->prepare("SELECT COUNT(*) FROM pesajet_transactions WHERE order_id = ? AND status IN ('initiated','pending') AND created_at > (NOW() - INTERVAL 15 MINUTE)");
                    $b->execute([$o['order_id']]);
                    $busy = (int)$b->fetchColumn();
                } catch (Throwable $e) {}
                if ($busy) {
                    flash_set('error', 'A payment for this order is still in progress. Try again in a few minutes.');
                } else {
                    $pdo->beginTransaction();
                    try {
                        $up = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?');
                        foreach ($o['items'] as $it) if ($it['product_id']) $up->execute([$it['quantity'], $it['product_id']]);
                        $pdo->prepare("UPDATE orders SET order_status = 'cancelled', hidden_by_user = 1 WHERE order_id = ? AND user_id = ? AND order_status = 'pending'")
                            ->execute([$o['order_id'], $uid]);
                        $pdo->commit();
                        flash_set('success', 'Order cancelled and removed from your list.');
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        error_log('delete_order: ' . $e->getMessage());
                        flash_set('error', 'Could not remove the order. Please try again.');
                    }
                }
            } else {
                flash_set('error', 'This order cannot be deleted right now (it is being prepared, on its way, or already paid). Please contact us.');
            }
        }
        redirect('account.php?tab=orders');
    }

    if ($action === 'profile') {
        $name = clean($_POST['name'] ?? ''); $phone = normalize_phone($_POST['phone'] ?? '');
        $address = clean($_POST['address'] ?? ''); $area = clean($_POST['area'] ?? '');
        if (mb_strlen($name) < 2)                 $errors[] = 'Please enter your full name.';
        if ($phone === '')                        $errors[] = 'Please enter a valid Uganda phone number.';
        if (mb_strlen($address) < 5)              $errors[] = 'Please enter your delivery address.';
        if ($area !== '' && !isset(DELIVERY_AREAS[$area])) $area = '';
        if (!$errors) {
            $chk = db()->prepare('SELECT COUNT(*) FROM users WHERE phone = ? AND user_id <> ?'); $chk->execute([$phone, $uid]);
            if ($chk->fetchColumn()) $errors[] = 'That phone number is used by another account.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET full_name = ?, phone = ?, delivery_address = ?, delivery_area = ? WHERE user_id = ?')
                ->execute([$name, $phone, $address, $area ?: null, $uid]);
            flash_set('success', 'Your profile has been updated.');
            redirect('account.php?tab=profile');
        }
        $tab = 'profile';
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? '';
        $row = db()->prepare('SELECT password_hash FROM users WHERE user_id = ?'); $row->execute([$uid]);
        if (!password_verify($current, (string)$row->fetchColumn())) $errors[] = 'Your current password is not correct.';
        if (!valid_password($new))                                    $errors[] = 'New password must be at least 8 characters with a letter and a number.';
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash_set('success', 'Your password has been changed.');
            redirect('account.php?tab=password');
        }
        $tab = 'password';
    }
}

// Load data for the tabs
$viewOrder = null;
if (!empty($_GET['order'])) { $viewOrder = get_order_by_number($_GET['order'], $uid); $tab = 'orders'; }

$orders = [];
if ($tab === 'orders' || $tab === 'overview') {
    $stmt = db()->prepare('SELECT o.*, p.payment_status, p.payment_method FROM orders o LEFT JOIN payments p ON p.order_id = o.order_id
                           WHERE o.user_id = ? AND o.hidden_by_user = 0 ORDER BY o.created_at DESC');
    $stmt->execute([$uid]);
    $orders = $stmt->fetchAll();
}

$page_title = 'My Account';
$show_newsletter = false;
include __DIR__ . '/includes/header.php';
$fullUser = db()->prepare('SELECT * FROM users WHERE user_id = ?'); $fullUser->execute([$uid]); $fullUser = $fullUser->fetch();
?>
<div class="container account-layout">
    <aside>
        <div class="sidebar-box account-nav">
            <a href="?tab=overview" class="<?= $tab === 'overview' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Overview</a>
            <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>"><i class="fa-solid fa-receipt"></i> My Orders</a>
            <a href="?tab=profile" class="<?= $tab === 'profile' ? 'active' : '' ?>"><i class="fa-regular fa-id-card"></i> Profile &amp; Address</a>
            <a href="?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>"><i class="fa-solid fa-key"></i> Change Password</a>
            <a href="<?= e(url('logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <section>
        <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>

        <?php if ($tab === 'overview'): ?>
            <h1 style="font-size:28px">Hello, <?= e(explode(' ', $user['full_name'])[0]) ?>!</h1>
            <p class="muted">Manage your orders and delivery details here.</p>
            <div class="product-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin:20px 0">
                <div class="form-card" style="margin:0;text-align:center"><div class="script text-red" style="font-size:44px;line-height:1"><?= count($orders) ?></div>Orders placed</div>
                <div class="form-card" style="margin:0;text-align:center"><div class="script text-red" style="font-size:44px;line-height:1"><?= count(array_filter($orders, fn($o) => in_array($o['order_status'], ['pending','processing','out_for_delivery'], true))) ?></div>Active orders</div>
                <div class="form-card" style="margin:0;text-align:center"><div class="script text-red" style="font-size:44px;line-height:1"><?= cart_count() ?></div>Items in cart</div>
            </div>
            <div class="form-card"><h2>Delivery address</h2>
                <p style="margin:0"><?= e($fullUser['delivery_address']) ?><?= $fullUser['delivery_area'] ? ' (' . e($fullUser['delivery_area']) . ')' : '' ?></p>
                <p><a href="?tab=profile">Change address</a></p></div>
            <?php if ($orders): ?><h2 style="font-size:20px">Latest order</h2>
                <?php $o = $orders[0]; ?>
                <div class="form-card"><p style="margin:0 0 8px"><strong><?= e($o['order_number']) ?></strong> &middot; <?= e(format_date($o['created_at'])) ?> &middot; <?= e(format_ugx($o['total_amount'])) ?></p>
                    <?php render_order_tracker($o['order_status']); ?>
                    <a class="btn btn-outline btn-small" href="?order=<?= e(urlencode($o['order_number'])) ?>">View details</a></div>
            <?php endif; ?>

        <?php elseif ($tab === 'orders' && $viewOrder): $o = $viewOrder; ?>
            <p><a href="?tab=orders"><i class="fa-solid fa-arrow-left"></i> All orders</a></p>
            <h1 style="font-size:26px">Order <?= e($o['order_number']) ?></h1>
            <div class="form-card"><?php render_order_tracker($o['order_status']); ?>
                <p style="margin:0">Order: <?= status_badge($o['order_status']) ?> &nbsp; Payment: <?= status_badge($o['payment_status'], true) ?> &nbsp; <span class="muted"><?= e(payment_method_label($o['payment_method'])) ?></span></p></div>
            <div class="form-card"><h2>Items</h2><div class="table-scroll"><table class="data-table">
                <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th style="text-align:right">Total</th></tr></thead><tbody>
                <?php foreach ($o['items'] as $it): ?><tr><td><?= e($it['product_name']) ?></td><td><?= e(format_ugx($it['unit_price'])) ?></td><td><?= (int)$it['quantity'] ?></td><td style="text-align:right"><?= e(format_ugx($it['line_total'])) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <div style="max-width:300px;margin-left:auto;margin-top:10px">
                    <div class="summary-row"><span>Subtotal</span><strong><?= e(format_ugx($o['subtotal'])) ?></strong></div>
                    <?php if ($o['discount_amount'] > 0): ?><div class="summary-row discount"><span>Discount</span><strong>- <?= e(format_ugx($o['discount_amount'])) ?></strong></div><?php endif; ?>
                    <div class="summary-row"><span>Delivery</span><strong><?= $o['delivery_fee'] > 0 ? e(format_ugx($o['delivery_fee'])) : 'FREE' ?></strong></div>
                    <div class="summary-total" style="margin-bottom:0"><span>Total</span><strong><?= e(format_ugx($o['total_amount'])) ?></strong></div></div></div>
            <div class="form-card"><h2>Delivery</h2><dl class="kv">
                <dt>Address</dt><dd><?= e($o['delivery_address']) ?> (<?= e($o['delivery_area']) ?>)</dd>
                <dt>Preferred time</dt><dd><?= e($o['preferred_time']) ?></dd>
                <?php if ($o['rider_name']): ?><dt>Rider</dt><dd><?= e($o['rider_name']) ?> <?= e($o['rider_phone']) ?></dd><?php endif; ?>
                <dt>Placed</dt><dd><?= e(format_date($o['created_at'])) ?></dd></dl></div>
            <?php if ($o['payment_method'] === 'mobile_money' && in_array($o['payment_status'], ['pending','failed'], true) && $o['order_status'] !== 'cancelled'): ?>
                <a class="btn btn-primary" href="<?= e(url('mtn-momo-payment.php?order=' . urlencode($o['order_number']))) ?>">Complete MTN payment</a><?php endif; ?>

        <?php elseif ($tab === 'orders'): ?>
            <h1 style="font-size:26px">My Orders</h1>
            <?php if (!$orders): ?>
                <div class="empty-state"><i class="fa-solid fa-receipt"></i><h3>No orders yet</h3><a class="btn btn-primary" href="<?= e(url('products.php')) ?>">Start shopping</a></div>
            <?php else: ?>
                <div class="cart-box"><div class="table-scroll"><table class="data-table">
                    <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th><th>Payment</th><th></th></tr></thead><tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr><td><strong><?= e($o['order_number']) ?></strong></td><td><?= e(format_date($o['created_at'])) ?></td><td><?= e(format_ugx($o['total_amount'])) ?></td>
                            <td><?= status_badge($o['order_status']) ?></td><td><?= status_badge($o['payment_status'], true) ?></td>
                            <td style="white-space:nowrap"><a class="btn btn-outline btn-small" href="?order=<?= e(urlencode($o['order_number'])) ?>">View</a>
                                <?php
                                $canDelete = in_array($o['order_status'], ['delivered', 'cancelled'], true)
                                          || ($o['order_status'] === 'pending' && !in_array($o['payment_status'] ?? 'pending', ['paid', 'refunded'], true));
                                if ($canDelete):
                                    $msg = $o['order_status'] === 'pending' ? 'Cancel and delete this order?' : 'Remove this order from your list?'; ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('<?= e($msg) ?>');">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_order"><input type="hidden" name="order" value="<?= e($o['order_number']) ?>">
                                    <button class="btn btn-outline btn-small" type="submit" title="Delete order"><i class="fa-solid fa-trash"></i></button>
                                </form>
                                <?php endif; ?></td></tr>
                    <?php endforeach; ?></tbody></table></div></div>
            <?php endif; ?>

        <?php elseif ($tab === 'profile'): ?>
            <h1 style="font-size:26px">Profile &amp; Address</h1>
            <form method="post" class="form-card" data-loading>
                <?= csrf_field() ?><input type="hidden" name="action" value="profile">
                <div class="form-group"><label for="name">Full name</label><input class="form-control" id="name" name="name" required maxlength="100" value="<?= e($_POST['name'] ?? $fullUser['full_name']) ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>Email (cannot be changed)</label><input class="form-control" value="<?= e($fullUser['email']) ?>" disabled></div>
                    <div class="form-group"><label for="phone">Phone</label><input class="form-control" id="phone" name="phone" required value="<?= e($_POST['phone'] ?? $fullUser['phone']) ?>"></div>
                </div>
                <div class="form-group"><label for="area">Delivery area</label><select class="form-control" id="area" name="area"><option value="">- choose -</option>
                    <?php foreach (DELIVERY_AREAS as $a => $t): ?><option value="<?= e($a) ?>" <?= (($_POST['area'] ?? $fullUser['delivery_area']) === $a) ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="address">Delivery address</label><textarea class="form-control" id="address" name="address" required rows="2" maxlength="255"><?= e($_POST['address'] ?? $fullUser['delivery_address']) ?></textarea></div>
                <button class="btn btn-primary" type="submit">Save changes</button>
            </form>

        <?php else: ?>
            <h1 style="font-size:26px">Change Password</h1>
            <form method="post" class="form-card" style="max-width:520px" data-loading>
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <div class="form-group"><label for="cp">Current password</label><input class="form-control" id="cp" type="password" name="current_password" required autocomplete="current-password"></div>
                <div class="form-group"><label for="np">New password</label><input class="form-control" id="np" type="password" name="new_password" required minlength="8" autocomplete="new-password"><p class="form-hint">At least 8 characters, with a letter and a number.</p></div>
                <button class="btn btn-primary" type="submit">Update password</button>
            </form>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>