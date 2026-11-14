<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Delivery Information';
$page_description = 'How Food Hub Masaka delivery works: delivery areas in Masaka, delivery charges, expected delivery time, order tracking and what happens if you are unavailable.';
$active_nav = 'delivery';
include __DIR__ . '/includes/header.php';
$steps = [
    ['1. Select Products', 'Browse and choose your favourite food and drinks.', 'fa-bag-shopping', '#e5332a'],
    ['2. Add to Cart', 'Review your items and proceed to checkout.', 'fa-cart-shopping', '#f5b400'],
    ['3. Checkout', 'Provide your delivery details and payment method.', 'fa-file-invoice', '#2e9e5b'],
    ['4. Payment', 'Pay with MTN Mobile Money or Cash on Delivery.', 'fa-credit-card', '#1a73d1'],
    ['5. Prepare Order', 'Our team prepares your fresh and tasty food.', 'fa-kitchen-set', '#8b3fd1'],
    ['6. Picked by Rider', 'Our delivery personnel collects your order.', 'fa-motorcycle', '#f28c1a'],
    ['7. On the Way', 'Track your order status in your account.', 'fa-location-dot', '#12a6a6'],
    ['8. Delivered', 'Enjoy your food at your doorstep!', 'fa-check', '#e64c9b'],
];
?>
<section class="page-banner" style="--banner-img:url('<?= e(url('images/banners/rider.jpg')) ?>')">
    <div class="container">
        <div><p class="eyebrow" style="font-family:var(--font);font-size:20px;font-style:italic;font-weight:600">Fast &bull; Safe &bull; Reliable</p><h1>Delivery Information</h1>
            <p>We bring your favourite food to your door in Masaka! Fresh, hot and on time &ndash; because you deserve the best.</p></div>
        <div class="banner-script white">Your Food<br>Our Priority!</div>
    </div>
</section>

<div class="container delivery-layout">
    <div>
        <h2 class="section-title" style="margin-bottom:6px">How Delivery Works</h2>
        <p class="muted">Getting your food is simple and convenient. Just follow these easy steps:</p>
        <div class="delivery-steps">
            <?php foreach ($steps as $s): ?>
                <div class="d-step"><div class="d-icon" style="background:<?= e($s[3]) ?>"><i class="fa-solid <?= e($s[2]) ?>"></i></div><h3><?= e($s[0]) ?></h3><p><?= e($s[1]) ?></p></div>
            <?php endforeach; ?>
        </div>

        <div class="info-cards">
            <div class="info-card"><h3><i class="fa-solid fa-map-location-dot"></i> Delivery Areas</h3>
                <p style="font-size:14px">We currently deliver within Masaka town and nearby areas, including:</p>
                <ul class="check-list"><?php foreach (array_keys(DELIVERY_AREAS) as $a): ?><li><i class="fa-solid fa-check"></i> <?= e($a) ?></li><?php endforeach; ?></ul></div>
            <div class="info-card"><h3><i class="fa-solid fa-coins"></i> Delivery Charges</h3>
                <p style="font-size:14px">Delivery fees depend on your order value.</p>
                <div class="fee-table">
                    <div><span>Orders below <?= e(format_ugx(DELIVERY_SMALL_LIMIT)) ?></span><strong><?= e(format_ugx(DELIVERY_FEE_SMALL_ORDER)) ?></strong></div>
                    <div><span><?= e(format_ugx(DELIVERY_SMALL_LIMIT)) ?> &ndash; <?= e(format_ugx(FREE_DELIVERY_ABOVE)) ?></span><strong><?= e(format_ugx(DELIVERY_FEE_MEDIUM_ORDER)) ?></strong></div>
                    <div><span>Orders above <?= e(format_ugx(FREE_DELIVERY_ABOVE)) ?></span><strong>FREE</strong></div>
                </div>
                <p class="muted" style="font-size:13px"><em>Charges may change. The exact fee is always shown in your cart and at checkout.</em></p></div>
            <div class="info-card"><h3><i class="fa-regular fa-clock"></i> Expected Delivery Time</h3>
                <p style="font-size:14px">Your order will usually arrive within:</p>
                <ul class="check-list" style="list-style:disc;padding-left:20px"><li>Masaka Town Centre &amp; Kitovu: 30 - 60 minutes</li><li>Nearby areas: 45 - 90 minutes</li></ul>
                <p class="muted" style="font-size:13px"><em>Times may vary during peak hours or bad weather.</em></p></div>
        </div>
        <div class="info-cards" style="grid-template-columns:1fr 2fr">
            <div class="info-card"><h3><i class="fa-solid fa-box"></i> Order Tracking</h3><p style="font-size:14px;margin:0">You can track your order status in <a href="<?= e(url('account.php?tab=orders')) ?>">your account</a>. Statuses: Pending &rarr; Processing &rarr; Out for Delivery &rarr; Delivered.</p></div>
            <div class="info-card"><h3><i class="fa-solid fa-user"></i> What If You're Unavailable?</h3><p style="font-size:14px;margin:0">If you are not available when we arrive, our rider will call you and wait a few minutes. If delivery is not possible, the order may be returned to our kitchen and refunded or rescheduled according to our <a href="<?= e(url('refund-policy.php')) ?>">refund policy</a>. Please keep your phone on and give clear directions.</p></div>
        </div>
    </div>
    <aside>
        <div class="delivery-photo"><img src="<?= e(url('images/banners/rider.jpg')) ?>" alt="Food Hub Masaka delivery rider on a motorbike"></div>
        <div class="help-card" style="flex-direction:column"><div style="display:flex;gap:14px"><i class="fa-solid fa-headset"></i><div><h3>Need Help?</h3><p style="margin:0">Our customer support team is here for you.</p></div></div>
            <ul style="font-size:14px;margin:12px 0"><li><i class="fa-solid fa-phone text-red"></i> <?= e(SITE_PHONE) ?></li><li><i class="fa-solid fa-envelope text-red"></i> <?= e(SITE_EMAIL) ?></li><li><i class="fa-solid fa-location-dot text-red"></i> Masaka, Uganda</li></ul>
            <a class="btn btn-primary btn-pill" href="<?= e(url('contact.php')) ?>">Contact Us <i class="fa-solid fa-arrow-right"></i></a></div>
    </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
