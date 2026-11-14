<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Cookie Policy';
$page_description = 'How Food Hub Masaka uses cookies and Google Analytics.';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div><nav class="breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Cookie Policy</span></nav><h1>Cookie Policy</h1><p>Last updated: <?= date('F Y') ?></p></div></div></section>
<div class="container"><div class="policy">
<h2>What are cookies?</h2><p>Cookies are small files stored on your device that help a website work and remember choices.</p>
<table><tr><th>Cookie</th><th>Purpose</th><th>Type</th></tr>
<tr><td>FHMSESSID</td><td>Keeps you logged in and keeps your shopping cart during your visit.</td><td>Essential (always on, ends when you close the browser)</td></tr>
<tr><td>fh_cookie_consent (browser storage)</td><td>Remembers your cookie choice.</td><td>Essential</td></tr>
<tr><td>_ga, _ga_* (Google Analytics 4)</td><td>Anonymous statistics: pages viewed, products viewed, searches, add to cart, checkout and orders.</td><td>Analytics (only after you click Accept)</td></tr></table>
<h2>Your choice</h2><p>If you click <strong>Decline</strong>, no analytics data is sent. You can clear your browser data to see the cookie notice again.</p>
<h2>Google Analytics</h2><p>Analytics data is processed by Google. We use it only to understand how the site is used and to improve it.</p>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
