<?php
/**
 * checkout.php - CHECKOUT
 * 1. Customer must be logged in.  2. Shows the form + order summary.
 * 3. On submit: validate -> create order, order_items, payment, delivery in ONE
 *    database transaction -> reduce stock -> empty the cart -> go to payment/confirmation.
 */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/pesajet.php';   // PesaJet integration
require_login();
$user = current_user();

$cart = cart_summary();
if (!$cart['items']) { flash_set('info', 'Your cart is empty.'); redirect('cart.php'); }

$errors = [];
$old = [
    'name'  => $user['full_name'], 'phone' => $user['phone'], 'email' => $user['email'],
    'area'  => $user['delivery_area'] ?: 'Masaka Town Centre', 'address' => $user['delivery_address'],
    'time'  => 'As soon as possible', 'notes' => '', 'payment' => 'mobile_money', 'provider' => 'MTN',
];
$timeOptions = ['As soon as possible', 'Within 1 hour', 'Within 2 hours', 'Later today (write the time in the notes)'];
$areas = array_keys(DELIVERY_AREAS);

if (is_post()) {
    csrf_check();
    $old['name']    = clean($_POST['name'] ?? '');
    $old['phone']   = clean($_POST['phone'] ?? '');
    $old['email']   = strtolower(clean($_POST['email'] ?? ''));
    $old['area']    = clean($_POST['area'] ?? '');
    $old['address'] = clean($_POST['address'] ?? '');
    $old['time']    = clean($_POST['time'] ?? '');
    $old['notes']   = trim($_POST['notes'] ?? '');
    $old['payment'] = $_POST['payment'] ?? '';
    $old['provider']= $_POST['provider'] ?? 'MTN';

    // ----- Server-side validation (never trust the browser) -----
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) $errors[] = 'Please enter your full name.';
    $phone = normalize_phone($old['phone']);
    if ($phone === '')                                  $errors[] = 'Please enter a valid Uganda phone number (e.g. 0700 123 456).';
    if (!valid_email($old['email']))                    $errors[] = 'Please enter a valid email address.';
    if (!in_array($old['area'], $areas, true))          $errors[] = 'Please choose a delivery area.';
    if (mb_strlen($old['address']) < 8 || mb_strlen($old['address']) > 255) $errors[] = 'Please enter a clear delivery address (street, landmark, house).';
    if (!in_array($old['time'], $timeOptions, true))    $errors[] = 'Please choose a delivery time.';
    if (mb_strlen($old['notes']) > 500)                 $errors[] = 'Order notes are too long (maximum 500 characters).';
    if (!in_array($old['payment'], ['mobile_money', 'card', 'cash_on_delivery'], true)) $errors[] = 'Please choose a payment method.';
    if (!in_array($old['provider'], ['MTN', 'Airtel'], true)) $old['provider'] = 'MTN';

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // Re-read the cart inside the transaction and check stock
            $orderNumber = generate_order_number();
            $ins = $pdo->prepare('INSERT INTO orders (order_number, user_id, customer_name, customer_phone, customer_email,
                                   subtotal, delivery_fee, discount_amount, total_amount, coupon_code, order_status, order_notes)
                                  VALUES (?,?,?,?,?,?,?,?,?,?, "pending", ?)');
            $ins->execute([$orderNumber, $user['user_id'], $old['name'], $phone, $old['email'],
                           $cart['subtotal'], $cart['delivery_fee'], $cart['discount'], $cart['total'],
                           $cart['coupon']['code'] ?? null, $old['notes'] !== '' ? $old['notes'] : null]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt  = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES (?,?,?,?,?,?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ? AND status = "active"');
            foreach ($cart['items'] as $it) {
                $stockStmt->execute([$it['quantity'], $it['product_id'], $it['quantity']]);
                if ($stockStmt->rowCount() === 0) {
                    throw new RuntimeException('Sorry, "' . $it['product_name'] . '" no longer has enough stock. Please update your cart.');
                }
                $itemStmt->execute([$orderId, $it['product_id'], $it['product_name'], $it['price'], $it['quantity'], $it['line_total']]);
            }

            $method = $old['payment'];
            $pdo->prepare('INSERT INTO payments (order_id, payment_method, provider, amount, payment_status, is_test) VALUES (?,?,?,?, "pending", ?)')
                ->execute([$orderId, $method, $method === 'mobile_money' ? $old['provider'] : null, $cart['total'], pesajet_is_test()]);

            $pdo->prepare('INSERT INTO delivery (order_id, delivery_area, delivery_address, preferred_time) VALUES (?,?,?,?)')
                ->execute([$orderId, $old['area'], $old['address'], $old['time']]);

            if (!empty($cart['coupon'])) {
                $pdo->prepare('UPDATE coupons SET times_used = times_used + 1 WHERE code = ?')->execute([$cart['coupon']['code']]);
            }
            // Empty the cart
            $cartId = cart_get_id(false);
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
            $pdo->prepare('UPDATE cart SET coupon_code = NULL WHERE cart_id = ?')->execute([$cartId]);

            // Remember the address for next time
            $pdo->prepare('UPDATE users SET delivery_address = ?, delivery_area = ? WHERE user_id = ?')
                ->execute([$old['address'], $old['area'], $user['user_id']]);

            $pdo->commit();
            $_SESSION['last_order'] = $orderNumber;
            if ($method === 'cash_on_delivery') {
                redirect('order-confirmation.php?order=' . $orderNumber);
            }
            // Online payment: PesaJet when enabled, otherwise the old sandbox page (nothing breaks if the gateway is off)
            redirect((pesajet_enabled() ? 'pay-pesajet.php?order=' : 'pay-sandbox.php?order=') . $orderNumber);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = ($ex instanceof RuntimeException) ? $ex->getMessage() : 'Something went wrong while placing your order. Please try again.';
            if (!($ex instanceof RuntimeException)) error_log('Checkout error: ' . $ex->getMessage());
        }
    }
}

// GA4: begin_checkout (only when the form is first opened)
if (!is_post()) {
    $gaItems = [];
    foreach ($cart['items'] as $it) $gaItems[] = ['item_id' => (string)$it['product_id'], 'item_name' => $it['product_name'], 'price' => (float)$it['price'], 'quantity' => (int)$it['quantity']];
    ga_event('begin_checkout', ['currency' => 'UGX', 'value' => (float)$cart['total'], 'items' => $gaItems]);
}

$page_title = 'Checkout';
$active_nav = '';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container">
        <div class="banner-title-with-icon"><i class="fa-solid fa-lock"></i><div><h1>Checkout</h1><p>Just a few details and your food is on its way.</p></div></div>
        <div class="banner-script">Your Food<br>Our Priority</div>
    </div>
</section>

<div class="container">
<form method="post" class="checkout-layout" data-loading>
    <?= csrf_field() ?>
    <div>
        <?php if ($errors): ?>
            <div class="alert alert-error" role="alert"><strong>Please fix the following:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <div class="form-card">
            <h2><span class="num">1</span> Contact details</h2>
            <div class="form-group"><label for="name">Full name <span class="req">*</span></label>
                <input class="form-control" id="name" name="name" required maxlength="100" value="<?= e($old['name']) ?>" autocomplete="name"></div>
            <div class="form-row">
                <div class="form-group"><label for="phone">Phone number <span class="req">*</span></label>
                    <input class="form-control" id="phone" name="phone" type="tel" required maxlength="20" value="<?= e($old['phone']) ?>" autocomplete="tel" placeholder="0700 123 456"></div>
                <div class="form-group"><label for="email">Email <span class="req">*</span></label>
                    <input class="form-control" id="email" name="email" type="email" required maxlength="150" value="<?= e($old['email']) ?>" autocomplete="email"></div>
            </div>
        </div>

        <div class="form-card">
            <h2><span class="num">2</span> Delivery details</h2>
            <div class="form-row">
                <div class="form-group"><label for="area">Delivery area <span class="req">*</span></label>
                    <select class="form-control" id="area" name="area" required>
                        <?php foreach (DELIVERY_AREAS as $a => $t): ?><option value="<?= e($a) ?>" <?= $old['area'] === $a ? 'selected' : '' ?>><?= e($a) ?> (<?= e($t) ?>)</option><?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label for="time">Preferred delivery time</label>
                    <select class="form-control" id="time" name="time">
                        <?php foreach ($timeOptions as $t): ?><option <?= $old['time'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                    </select></div>
            </div>
            <div class="form-group"><label for="address">Delivery address <span class="req">*</span></label>
                <textarea class="form-control" id="address" name="address" required minlength="8" maxlength="255" rows="2" placeholder="Street, landmark, house or room number"><?= e($old['address']) ?></textarea></div>
            <div class="form-group"><label for="notes">Order notes (optional)</label>
                <textarea class="form-control" id="notes" name="notes" maxlength="500" rows="2" placeholder="e.g. No pepper, call when you arrive"><?= e($old['notes']) ?></textarea></div>
        </div>

        <div class="form-card">
            <h2><span class="num">3</span> Payment method</h2>
            <div class="alert alert-warning" style="font-size:13px"><i class="fa-solid fa-shield-halved"></i> Mobile Money PINs and card details are never collected or stored on this website.</div>

            <label class="pay-option">
                <span class="pay-option-head"><input type="radio" name="payment" value="mobile_money" <?= $old['payment'] === 'mobile_money' ? 'checked' : '' ?>><i class="fa-solid fa-mobile-screen-button"></i> Mobile Money</span>
                <small>Pay with MTN Mobile Money or Airtel Money.</small>
                <span class="pay-extra">
                    <label for="provider" class="sr-only">Network</label>
                    <select class="form-control" id="provider" name="provider" style="max-width:220px">
                        <option value="MTN" <?= $old['provider'] === 'MTN' ? 'selected' : '' ?>>MTN Mobile Money</option>
                        <option value="Airtel" <?= $old['provider'] === 'Airtel' ? 'selected' : '' ?>>Airtel Money</option>
                    </select>
                </span>
            </label>
            <label class="pay-option">
                <span class="pay-option-head"><input type="radio" name="payment" value="card" <?= $old['payment'] === 'card' ? 'checked' : '' ?>><i class="fa-regular fa-credit-card"></i> Card Payment</span>
                <small>Continue to the secure payment provider to complete your payment.</small>
            </label>
            <label class="pay-option">
                <span class="pay-option-head"><input type="radio" name="payment" value="cash_on_delivery" <?= $old['payment'] === 'cash_on_delivery' ? 'checked' : '' ?>><i class="fa-solid fa-money-bill-wave"></i> Cash on Delivery</span>
                <small>Pay the rider in cash when your order arrives.</small>
            </label>
        </div>
    </div>

    <aside class="summary-box">
        <h2>Your Order</h2>
        <ul class="mini-items">
            <?php foreach ($cart['items'] as $it): ?>
                <li><img src="<?= e(product_image($it['image'])) ?>" alt=""><span class="mi-name"><?= e($it['product_name']) ?><small><?= (int)$it['quantity'] ?> &times; <?= e(format_ugx($it['price'])) ?></small></span><strong><?= e(format_ugx($it['line_total'])) ?></strong></li>
            <?php endforeach; ?>
        </ul>
        <div class="summary-row" style="margin-top:10px"><span>Subtotal</span><strong><?= e(format_ugx($cart['subtotal'])) ?></strong></div>
        <?php if ($cart['discount'] > 0): ?><div class="summary-row discount"><span>Discount (<?= e($cart['coupon']['code']) ?>)</span><strong>- <?= e(format_ugx($cart['discount'])) ?></strong></div><?php endif; ?>
        <div class="summary-row"><span>Delivery Fee</span><strong><?= $cart['delivery_fee'] > 0 ? e(format_ugx($cart['delivery_fee'])) : 'FREE' ?></strong></div>
        <div class="summary-total"><span>Total</span><strong><?= e(format_ugx($cart['total'])) ?></strong></div>
        <button type="submit" class="btn btn-primary btn-block btn-large"><i class="fa-solid fa-lock"></i> Place Order</button>
        <p class="form-hint text-center" style="margin-top:10px">By placing your order you agree to our <a href="<?= e(url('terms.php')) ?>">Terms</a> and <a href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>.</p>
        <p class="text-center"><a href="<?= e(url('cart.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back to cart</a></p>
    </aside>
</form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
