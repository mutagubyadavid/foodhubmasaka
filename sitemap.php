<?php
/**
 * sitemap.php - dynamic XML sitemap for Google Search Console.
 * Upload to the site root (htdocs/), then submit:  sitemap.php   (or sitemap.xml via the .htaccess rule)
 * Product and category URLs are read from the database, so new products appear automatically.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = rtrim(BASE_URL, '/');
$urls = [];

/* Static public pages: [path, changefreq, priority] */
$static = [
    ['',                   'daily',   '1.0'],
    ['products.php',       'daily',   '0.9'],
    ['categories.php',     'weekly',  '0.8'],
    ['delivery.php',       'monthly', '0.7'],
    ['about.php',          'monthly', '0.6'],
    ['contact.php',        'monthly', '0.6'],
    ['faq.php',            'monthly', '0.5'],
    ['register.php',       'yearly',  '0.4'],
    ['login.php',          'yearly',  '0.3'],
    ['privacy.php',        'yearly',  '0.3'],
    ['terms.php',          'yearly',  '0.3'],
    ['refund-policy.php',  'yearly',  '0.3'],
    ['cookies.php',        'yearly',  '0.3'],
    ['responsibility.php', 'yearly',  '0.3'],
];
foreach ($static as [$path, $freq, $prio]) {
    $urls[] = ['loc' => $base . '/' . $path, 'freq' => $freq, 'prio' => $prio, 'mod' => null];
}

try {
    /* Category pages */
    foreach (db()->query('SELECT slug FROM categories ORDER BY sort_order')->fetchAll() as $c) {
        $urls[] = ['loc' => $base . '/products.php?category=' . rawurlencode($c['slug']),
                   'freq' => 'weekly', 'prio' => '0.8', 'mod' => null];
    }
    /* Active product pages */
    $q = db()->query("SELECT product_id, updated_at FROM products WHERE status = 'active' ORDER BY product_id");
    foreach ($q->fetchAll() as $p) {
        $urls[] = ['loc' => $base . '/product.php?id=' . (int)$p['product_id'],
                   'freq' => 'weekly', 'prio' => '0.7',
                   'mod'  => $p['updated_at'] ? date('c', strtotime($p['updated_at'])) : null];
    }
} catch (Throwable $ex) {
    /* If the database is unreachable, still output the static pages. */
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    if ($u['mod']) echo '    <lastmod>' . $u['mod'] . "</lastmod>\n";
    echo '    <changefreq>' . $u['freq'] . "</changefreq>\n";
    echo '    <priority>' . $u['prio'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
