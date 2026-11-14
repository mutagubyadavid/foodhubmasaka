<?php
/**
 * product.php - PRODUCT DETAILS   (product.php?id=5)
 */
require_once __DIR__ . '/includes/init.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT p.*, c.category_name, c.slug AS category_slug FROM products p
                       JOIN categories c ON c.category_id = p.category_id
                       WHERE p.product_id = ? AND p.status = "active"');
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    $page_title = 'Product not found';
    $active_nav = 'shop';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state" style="margin:40px 0"><i class="fa-solid fa-plate-wheat"></i><h1>Product not found</h1>
          <p class="muted">This product may have been removed or is no longer available.</p>
          <a class="btn btn-primary" href="' . e(url('products.php')) . '">Back to the shop</a></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$page_title       = $p['product_name'];
$page_description = mb_substr(strip_tags($p['description'] ?: $p['product_name']), 0, 155) . ' - ' . format_ugx($p['price']) . ' at Food Hub Masaka.';
$active_nav       = 'shop';
$stock  = (int)$p['stock_quantity'];
$sale   = discount_percent($p['price'], $p['old_price']);

// Google Analytics 4: "view_item" event
ga_event('view_item', ['currency' => 'UGX', 'value' => (float)$p['price'],
    'items' => [['item_id' => (string)$p['product_id'], 'item_name' => $p['product_name'], 'item_category' => $p['category_name'], 'price' => (float)$p['price']]]]);

// Related products from the same category
$rel = db()->prepare('SELECT * FROM products WHERE category_id = ? AND product_id <> ? AND status = "active" ORDER BY RAND() LIMIT 4');
$rel->execute([$p['category_id'], $p['product_id']]);
$related = $rel->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb" style="padding-top:20px">
        <a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i>
        <a href="<?= e(url('products.php')) ?>">Shop</a> <i class="fa-solid fa-chevron-right"></i>
        <a href="<?= e(url('products.php?category=' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a> <i class="fa-solid fa-chevron-right"></i>
        <span><?= e($p['product_name']) ?></span>
    </nav>

    <div class="product-detail">
        <div class="product-detail-image">
            <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['product_name']) ?>">
            <?php if ($sale > 0): ?><span class="sale-tag" style="font-size:15px">-<?= $sale ?>%</span><?php endif; ?>
        </div>
        <div>
            <span class="chip"><?= e($p['category_name']) ?></span>
            <h1><?= e($p['product_name']) ?></h1>
            <p class="price"><?= e(format_ugx($p['price'])) ?> <?php if ($sale > 0): ?><span class="old-price"><?= e(format_ugx($p['old_price'])) ?></span><?php endif; ?></p>

            <?php $cls = $stock <= 0 ? 'out' : ($stock <= 5 ? 'low' : 'in'); ?>
            <div class="availability <?= $cls ?>">
                <i class="fa-solid <?= $stock <= 0 ? 'fa-circle-xmark' : 'fa-circle-check' ?>"></i> <?= e(stock_text($stock)) ?>
            </div>

            <p><?= nl2br(e($p['description'])) ?></p>

            <?php if ($stock > 0): ?>
                <div class="buy-row">
                    <div class="qty">
                        <button type="button" class="qty-minus" aria-label="Decrease quantity"><i class="fa-solid fa-minus"></i></button>
                        <input type="number" id="product-qty" value="1" min="1" max="<?= min($stock, 50) ?>" aria-label="Quantity">
                        <button type="button" class="qty-plus" aria-label="Increase quantity"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <button type="button" class="btn btn-primary btn-pill btn-large js-add-to-cart" data-use-qty="1"
                            data-product-id="<?= (int)$p['product_id'] ?>" data-name="<?= e($p['product_name']) ?>" data-price="<?= (float)$p['price'] ?>">
                        <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                    </button>
                    <button type="button" class="btn btn-outline btn-pill btn-large js-add-to-cart" data-use-qty="1" data-go-cart="1"
                            data-product-id="<?= (int)$p['product_id'] ?>" data-name="<?= e($p['product_name']) ?>" data-price="<?= (float)$p['price'] ?>">
                        Buy Now
                    </button>
                </div>
            <?php else: ?>
                <p><button class="btn btn-disabled btn-large" disabled>Out of stock</button></p>
            <?php endif; ?>

            <ul class="feature-list">
                <li><i class="fa-solid fa-truck-fast"></i> Delivery within Masaka from <?= e(format_ugx(DELIVERY_FEE_MEDIUM_ORDER)) ?> &ndash; free above <?= e(format_ugx(FREE_DELIVERY_ABOVE)) ?>.</li>
                <li><i class="fa-solid fa-shield-halved"></i> MTN Mobile Money or cash on delivery.</li>
                <li><i class="fa-solid fa-circle-info"></i> Have an allergy or dietary need? <a href="<?= e(url('contact.php?subject=Product+question')) ?>">Ask us before ordering.</a></li>
            </ul>
        </div>
    </div>

    <?php if ($related): ?>
        <h2 class="section-title" style="margin-bottom:20px">You May Also Like</h2>
        <div class="product-grid" style="margin-bottom:50px">
            <?php foreach ($related as $r) render_product_card($r); ?>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
