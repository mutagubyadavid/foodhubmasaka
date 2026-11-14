<?php
/**
 * cart.php - SHOPPING CART
 * The page is drawn by PHP; quantity buttons call api/cart.php with JavaScript.
 */
require_once __DIR__ . '/includes/init.php';

$page_title = 'Shopping Cart';
$active_nav = '';
$cart = cart_summary();
$freeLeft = max(0, FREE_DELIVERY_ABOVE - $cart['subtotal']);
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container">
        <div class="banner-title-with-icon">
            <i class="fa-solid fa-cart-shopping"></i>
            <div><h1>Shopping Cart</h1><p>Review your items and proceed to checkout when you're ready.</p></div>
        </div>
        <div class="banner-script">Good Food<br>Brings People Together</div>
    </div>
</section>

<div class="container">
<?php if (!$cart['items']): ?>
    <div class="empty-state" style="margin:40px 0">
        <i class="fa-solid fa-cart-shopping"></i>
        <h2>Your cart is empty</h2>
        <p class="muted">Looks like you have not added anything yet. Let's find something tasty!</p>
        <a class="btn btn-primary btn-large" href="<?= e(url('products.php')) ?>">Start Shopping</a>
    </div>
<?php else:
    // GA4 view_cart is not required by the brief; begin_checkout is sent on checkout.php
?>
    <div class="cart-layout">
        <div class="cart-box">
            <table class="cart-table" id="cart-table">
                <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Remove</th></tr></thead>
                <tbody>
                <?php foreach ($cart['items'] as $it): ?>
                    <tr class="cart-row" data-product-id="<?= (int)$it['product_id'] ?>" data-name="<?= e($it['product_name']) ?>">
                        <td>
                            <div class="cart-product">
                                <img src="<?= e(product_image($it['image'])) ?>" alt="<?= e($it['product_name']) ?>">
                                <div>
                                    <strong><a href="<?= e(url('product.php?id=' . $it['product_id'])) ?>" style="color:inherit"><?= e($it['product_name']) ?></a></strong>
                                    <small><?= e($it['category_name']) ?></small>
                                    <?php if ($it['status'] !== 'active' || $it['stock_quantity'] < $it['quantity']): ?>
                                        <small class="text-red"><br>Only <?= (int)$it['stock_quantity'] ?> available</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="cart-price" data-label="Price"><?= e(format_ugx($it['price'])) ?></td>
                        <td class="cell-qty">
                            <div class="qty">
                                <button type="button" class="qty-minus" aria-label="Decrease quantity"><i class="fa-solid fa-minus"></i></button>
                                <input type="number" class="qty-input" value="<?= (int)$it['quantity'] ?>" min="1" max="50" aria-label="Quantity of <?= e($it['product_name']) ?>">
                                <button type="button" class="qty-plus" aria-label="Increase quantity"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </td>
                        <td class="cart-line-total" data-label="Total"><span class="line-total"><?= e(format_ugx($it['line_total'])) ?></span></td>
                        <td class="cell-remove"><button type="button" class="remove-btn" aria-label="Remove <?= e($it['product_name']) ?>"><i class="fa-regular fa-trash-can"></i></button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-footer">
                <a href="<?= e(url('products.php')) ?>" class="link-arrow" style="font-size:15px"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
                <?php if ($cart['coupon']): ?>
                    <div><span class="badge badge-paid"><i class="fa-solid fa-tag"></i> <?= e($cart['coupon']['code']) ?> applied</span>
                        <button type="button" id="remove-coupon" class="btn btn-small btn-outline" style="margin-left:8px">Remove</button></div>
                <?php else: ?>
                    <form class="coupon-form" id="coupon-form">
                        <label for="coupon" class="sr-only">Coupon code</label>
                        <input id="coupon" type="text" name="coupon" placeholder="Enter coupon code" maxlength="30" autocomplete="off">
                        <button type="submit" class="btn btn-primary btn-small">Apply</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($cart['coupon_message']): ?><p class="coupon-note text-red"><?= e($cart['coupon_message']) ?></p><?php endif; ?>
        </div>

        <aside class="summary-box">
            <h2>Order Summary</h2>
            <div class="summary-row"><span>Subtotal <span id="sum-items">(<?= (int)$cart['count'] ?> item<?= $cart['count'] == 1 ? '' : 's' ?>)</span></span><strong id="sum-subtotal"><?= e(format_ugx($cart['subtotal'])) ?></strong></div>
            <div class="summary-row discount" id="row-discount" <?= $cart['discount'] > 0 ? '' : 'hidden' ?>><span>Discount</span><strong id="sum-discount">- <?= e(format_ugx($cart['discount'])) ?></strong></div>
            <div class="summary-row"><span>Delivery Fee</span><strong id="sum-delivery"><?= $cart['delivery_fee'] > 0 ? e(format_ugx($cart['delivery_fee'])) : 'FREE' ?></strong></div>
            <div class="summary-total"><span>Total</span><strong id="sum-total"><?= e(format_ugx($cart['total'])) ?></strong></div>
            <a class="btn btn-primary btn-block btn-large" href="<?= e(url('checkout.php')) ?>"><i class="fa-solid fa-lock"></i> Proceed to Checkout</a>
            <div class="note-box"><i class="fa-solid fa-truck-fast"></i><span>Free delivery on orders above <?= e(format_ugx(FREE_DELIVERY_ABOVE)) ?> (within Masaka town and nearby areas)</span></div>
            <div class="secure-list">
                <h3><i class="fa-solid fa-shield-halved"></i> Secure &amp; Safe Shopping</h3>
                <ul>
                    <li><i class="fa-solid fa-check"></i> Your information is protected</li>
                    <li><i class="fa-solid fa-check"></i> MTN Mobile Money or Cash on Delivery</li>
                    <li><i class="fa-solid fa-check"></i> Easy returns and support</li>
                </ul>
            </div>
        </aside>
    </div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
