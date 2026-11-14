<?php
/**
 * index.php - HOME PAGE
 * Sections: hero slider, featured products, popular categories, special offers,
 * how ordering works, delivery/support info. (Newsletter + footer come from footer.php)
 */
require_once __DIR__ . '/includes/init.php';

$page_title       = 'Order Food Online in Masaka';
$page_description = 'Food Hub Masaka - fresh fast food, snacks, drinks, bakery and local dishes delivered to your door in Masaka, Uganda. Order online today.';
$active_nav       = 'home';

// Featured products (marked is_featured = 1 by the admin)
$featured = db()->query('SELECT * FROM products WHERE status = "active" AND is_featured = 1 ORDER BY date_added DESC LIMIT 5')->fetchAll();
// Hot deals = products that have an old (crossed-out) price
$deals = db()->query('SELECT * FROM products WHERE status = "active" AND old_price IS NOT NULL AND old_price > price ORDER BY date_added DESC LIMIT 5')->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO SLIDER ============ -->
<section class="hero" aria-label="Featured promotions">
    <div class="hero-slide">
        <img class="hero-img" src="<?= e(url('images/hero/hero-1.jpg')) ?>" alt="Grilled chicken with chips and salad">
        <div class="container hero-content">
            <p class="hero-eyebrow">Fresh &bull; Tasty &bull; Always Nearby</p>
            <h1>Delicious Food <span>Delivered to Your Door</span></h1>
            <p class="lead">Order your favorite meals, snacks and drinks from Food Hub Masaka. Fast, easy and convenient!</p>
            <a href="<?= e(url('products.php')) ?>" class="btn btn-primary btn-pill btn-large">Shop Now <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="sticker">Good Food<br>Happy Mood &#9786;</div>
    </div>
    <div class="hero-slide">
        <img class="hero-img" src="<?= e(url('images/hero/hero-2.jpg')) ?>" alt="Cheese burger with chips">
        <div class="container hero-content">
            <p class="hero-eyebrow">Burgers &bull; Chips &bull; Drinks</p>
            <h2 class="hero-title">Hungry? <span>Order in Minutes</span></h2>
            <p class="lead">Browse our menu, add your favourites to the cart and pay by Mobile Money or cash on delivery.</p>
            <a href="<?= e(url('products.php?category=fast-food')) ?>" class="btn btn-primary btn-pill btn-large">Order Fast Food <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
    <div class="hero-slide">
        <img class="hero-img" src="<?= e(url('images/hero/hero-3.jpg')) ?>" alt="Crispy fried chicken and chips">
        <div class="container hero-content">
            <p class="hero-eyebrow">Special Offers</p>
            <h2 class="hero-title">Tasty Meals <span>Up to 20% Off</span></h2>
            <p class="lead">Check this week's deals and use code <strong>WELCOME10</strong> for 10% off your first order.</p>
            <a href="<?= e(url('products.php?category=special-offers')) ?>" class="btn btn-yellow btn-pill btn-large">See Offers <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
    <button class="hero-arrow hero-prev" type="button" aria-label="Previous slide"><i class="fa-solid fa-chevron-left"></i></button>
    <button class="hero-arrow hero-next" type="button" aria-label="Next slide"><i class="fa-solid fa-chevron-right"></i></button>
    <div class="hero-dots"></div>
</section>

<!-- ============ FEATURED PRODUCTS ============ -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title">Featured Products</h2>
            <a class="link-arrow" href="<?= e(url('products.php')) ?>">View All Products <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="product-grid five">
            <?php foreach ($featured as $p) render_product_card($p); ?>
        </div>
    </div>
</section>

<!-- ============ POPULAR CATEGORIES ============ -->
<section class="section" style="padding-top:10px">
    <div class="container">
        <div class="section-head">
            <h2 class="section-title">Popular Categories</h2>
            <a class="link-arrow" href="<?= e(url('categories.php')) ?>">View All Categories <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="popular-cats">
            <?php foreach ($categories as $c): ?>
                <a class="pop-cat" href="<?= e(url('products.php?category=' . $c['slug'])) ?>">
                    <img src="<?= e(url('images/icons/circle-' . $c['slug'] . '.jpg')) ?>" alt="" loading="lazy">
                    <?= e($c['category_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ PROMOTION BANNERS ============ -->
<section class="section" style="padding-top:10px">
    <div class="container promo-row">
        <div class="promo promo-offer">
            <div class="promo-content">
                <span class="script-title">Special Offer</span>
                <h3>Tasty Meals <em>Up to 20% Off</em></h3>
                <a href="<?= e(url('products.php?category=special-offers')) ?>" class="btn btn-yellow btn-pill btn-small">Order Now <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
        <div class="promo promo-healthy">
            <div class="promo-content">
                <h3>Fresh &amp; Healthy Food Choices</h3>
                <p>Eat well, live better!</p>
                <a href="<?= e(url('products.php?category=fresh-food')) ?>" class="btn btn-green btn-pill btn-small">Shop Fresh <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<?php if ($deals): ?>
<!-- ============ HOT DEALS (products with a discount) ============ -->
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head"><h2 class="section-title">Hot Deals This Week</h2></div>
        <div class="product-grid five">
            <?php foreach ($deals as $p) render_product_card($p); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ HOW ORDERING WORKS ============ -->
<section class="section" style="padding-top:0">
    <div class="container">
        <h2 class="section-title centered" style="margin-bottom:20px">How Ordering Works</h2>
        <div class="steps">
            <div class="step"><div class="step-icon"><i class="fa-solid fa-magnifying-glass"></i></div><h3>1. Browse</h3><p>Choose your favorite food and drinks</p></div>
            <div class="step"><div class="step-icon"><i class="fa-solid fa-cart-shopping"></i></div><h3>2. Add to Cart</h3><p>Select items and set quantity</p></div>
            <div class="step"><div class="step-icon"><i class="fa-solid fa-credit-card"></i></div><h3>3. Checkout</h3><p>Provide delivery details and make payment</p></div>
            <div class="step"><div class="step-icon"><i class="fa-solid fa-truck"></i></div><h3>4. We Prepare</h3><p>Your order is prepared with care</p></div>
            <div class="step"><div class="step-icon"><i class="fa-solid fa-house"></i></div><h3>5. Fast Delivery</h3><p>Get your food at your doorstep</p></div>
        </div>
    </div>
</section>

<!-- ============ DELIVERY / SUPPORT / PAYMENT INFO ============ -->
<section class="info-strip">
    <div class="container">
        <div class="info-item"><i class="fa-solid fa-truck-fast"></i><div><h3>Delivery Across Masaka</h3><p>Fast, reliable and safe delivery within Masaka town and nearby areas.</p></div></div>
        <div class="info-item"><i class="fa-solid fa-headset"></i><div><h3>Friendly Customer Support</h3><p>Mon - Sat, 8 AM - 8 PM. Call, email or send us a message.</p></div></div>
        <div class="info-item"><i class="fa-solid fa-shield-halved"></i><div><h3>Secure &amp; Safe Payments</h3><p>Pay by MTN Mobile Money or Cash on Delivery. We never ask for your PIN.</p></div></div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
