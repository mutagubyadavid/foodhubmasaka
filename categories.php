<?php
/**
 * categories.php - SHOP BY CATEGORY
 */
require_once __DIR__ . '/includes/init.php';

$page_title       = 'Shop by Category';
$page_description = 'Explore Food Hub Masaka categories: fast food, snacks, beverages, bakery, local food, fresh food and special offers.';
$active_nav       = 'categories';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container">
        <div>
            <p class="banner-eyebrow-yellow">Explore Our Delicious Categories</p>
            <h1>Find Your Favourite Food</h1>
            <p>From fast food to fresh produce, we have everything you need. Discover your perfect meal today!</p>
        </div>
        <div class="sticker" style="position:static;font-size:30px">Good Food<br>Happy Mood &#9786;</div>
    </div>
</section>

<div class="container shop-layout">
    <aside class="shop-sidebar">
        <div class="sidebar-box">
            <h2 class="sidebar-title"><i class="fa-solid fa-table-cells-large"></i> All Categories</h2>
            <div class="sidebar-list">
                <?php foreach ($categories as $c): ?>
                    <a href="<?= e(url('products.php?category=' . $c['slug'])) ?>"><i class="fa-solid <?= e($c['icon']) ?> cat-i"></i> <?= e($c['category_name']) ?> <i class="fa-solid fa-chevron-right chev"></i></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="sidebar-promo"><i class="fa-solid fa-motorcycle"></i><div><h3>Fast &amp; Fresh Delivery</h3><p>From our kitchen to your door in Masaka!</p></div></div>
    </aside>

    <section>
        <div class="shop-toolbar">
            <h2>Shop by Category</h2>
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Categories</span></nav>
        </div>
        <div class="cat-grid">
            <?php foreach ($categories as $c): ?>
                <a class="cat-card" href="<?= e(url('products.php?category=' . $c['slug'])) ?>" style="text-decoration:none;color:inherit">
                    <div class="cat-card-img">
                        <img src="<?= e(url('images/' . $c['image'])) ?>" alt="<?= e($c['category_name']) ?>" loading="lazy">
                                                <span class="cat-badge" style="background:<?= e($c['color']) ?>"><i class="fa-solid <?= e($c['icon']) ?>"></i></span>
                    </div>
                    <h3><?= e($c['category_name']) ?></h3>
                    <p class="count"><?= (int)$c['product_count'] ?> Product<?= $c['product_count'] == 1 ? '' : 's' ?></p>
                    <span class="btn btn-primary btn-small">View Products <i class="fa-solid fa-arrow-right"></i></span>
                </a>
            <?php endforeach; ?>
            <div class="cat-promo">
                <div class="cat-promo-text">
                    <h3>Good Food<br>Great Choices<br>Best Prices</h3>
                    <a href="<?= e(url('products.php')) ?>" class="btn btn-primary btn-pill btn-small">Shop Now <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
