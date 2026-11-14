<?php
/**
 * products.php - SHOP PAGE
 * Lists all active products. Supports:  ?q=search  ?category=slug  ?sort=...  ?page=2
 */
require_once __DIR__ . '/includes/init.php';

$q        = clean($_GET['q'] ?? '');
$catSlug  = clean($_GET['category'] ?? '');
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));

// Only allow known sort options (never put user text into ORDER BY!)
$sortOptions = [
    'newest'     => ['Newest first',        'p.date_added DESC'],
    'price_low'  => ['Price: low to high',  'p.price ASC'],
    'price_high' => ['Price: high to low',  'p.price DESC'],
    'name'       => ['Name: A - Z',         'p.product_name ASC'],
];
if (!isset($sortOptions[$sort])) $sort = 'newest';

// Build the WHERE part with prepared-statement parameters
$where  = ['p.status = "active"'];
$params = [];
$currentCat = null;
$categories = get_categories();
foreach ($categories as $c) if ($c['slug'] === $catSlug) $currentCat = $c;
if ($currentCat) { $where[] = 'p.category_id = ?'; $params[] = $currentCat['category_id']; }
if ($q !== '') {
    $where[] = '(p.product_name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
    array_push($params, $like, $like, $like);
}
$whereSql = implode(' AND ', $where);

$stmt = db()->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.category_id = p.category_id WHERE $whereSql");
$stmt->execute($params);
$total      = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / ITEMS_PER_PAGE));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * ITEMS_PER_PAGE;

$sql = "SELECT p.*, c.category_name FROM products p JOIN categories c ON c.category_id = p.category_id
        WHERE $whereSql ORDER BY " . $sortOptions[$sort][1] . " LIMIT " . (int)ITEMS_PER_PAGE . " OFFSET " . (int)$offset;
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Page title / SEO
if ($q !== '')          { $page_title = 'Search results for "' . $q . '"'; ga_event('search', ['search_term' => $q]); }
elseif ($currentCat)    { $page_title = $currentCat['category_name']; }
else                    { $page_title = 'Shop All Food & Drinks'; }
$page_description = $currentCat ? ($currentCat['description'] . ' Order ' . $currentCat['category_name'] . ' online from Food Hub Masaka.') : 'Browse all food, snacks, drinks and bakery items available for delivery in Masaka.';
$active_nav = 'shop';

$queryForLinks = array_filter(['q' => $q, 'category' => $catSlug, 'sort' => $sort !== 'newest' ? $sort : '']);
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container">
        <div>
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Shop</span></nav>
            <h1><?= $q !== '' ? 'Search Results' : ($currentCat ? e($currentCat['category_name']) : 'Shop') ?></h1>
            <p><?= $q !== '' ? 'Showing results for "' . e($q) . '"' : 'Fresh food, snacks and drinks - delivered to your door in Masaka.' ?></p>
        </div>
    </div>
</section>

<div class="container shop-layout">
    <aside class="shop-sidebar" aria-label="Filter by category">
        <div class="sidebar-box">
            <h2 class="sidebar-title"><i class="fa-solid fa-table-cells-large"></i> Categories</h2>
            <div class="sidebar-list">
                <a href="<?= e(url('products.php')) ?>" class="<?= !$currentCat ? 'active' : '' ?>"><i class="fa-solid fa-utensils cat-i"></i> All Products</a>
                <?php foreach ($categories as $c): ?>
                    <a href="<?= e(url('products.php?category=' . $c['slug'])) ?>" class="<?= ($currentCat && $currentCat['slug'] === $c['slug']) ? 'active' : '' ?>">
                        <i class="fa-solid <?= e($c['icon']) ?> cat-i"></i> <?= e($c['category_name']) ?> <span class="count"><?= (int)$c['product_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="sidebar-promo"><i class="fa-solid fa-motorcycle"></i><div><h3>Fast &amp; Fresh Delivery</h3><p>From our kitchen to your door in Masaka!</p></div></div>
    </aside>

    <section aria-label="Products">
        <div class="shop-toolbar">
            <div>
                <h2><?= $currentCat ? e($currentCat['category_name']) : 'All Products' ?></h2>
                <span class="result-count"><?= $total ?> product<?= $total === 1 ? '' : 's' ?> found</span>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <button type="button" class="btn btn-outline btn-small mobile-filter"><i class="fa-solid fa-filter"></i> Categories</button>
                <form class="sort-form" method="get">
                    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
                    <?php if ($catSlug !== ''): ?><input type="hidden" name="category" value="<?= e($catSlug) ?>"><?php endif; ?>
                    <label for="sort" class="sr-only">Sort by</label>
                    <select id="sort" name="sort" onchange="this.form.submit()">
                        <?php foreach ($sortOptions as $key => $opt): ?>
                            <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($opt[0]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button class="btn btn-small btn-dark" type="submit">Sort</button></noscript>
                </form>
            </div>
        </div>

        <?php if (!$products): ?>
            <div class="empty-state">
                <i class="fa-solid fa-magnifying-glass"></i>
                <h3>No products found</h3>
                <p class="muted">Try a different word or browse all our categories.</p>
                <a class="btn btn-primary" href="<?= e(url('products.php')) ?>">View all products</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p) render_product_card($p); ?>
            </div>
            <?php render_pagination($page, $totalPages, $queryForLinks); ?>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
