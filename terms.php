<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Terms and Conditions';
$page_description = 'Terms and conditions for ordering food and using the Food Hub Masaka website.';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div><nav class="breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Terms and Conditions</span></nav><h1>Terms and Conditions</h1><p>Last updated: <?= date('F Y') ?></p></div></div></section>
<div class="container"><div class="policy">
<h2>1. Using the website</h2><p>By using Food Hub Masaka you agree to these terms. You must give correct information and keep your password private.</p>
<h2>2. Products and prices</h2><p>We try to describe products honestly and show clear prices in Uganda Shillings (UGX). Photos are for illustration and food may look slightly different. Prices and availability can change; the price shown at checkout is the price you pay. Please ask us before ordering if you have allergies or dietary needs.</p>
<h2>3. Orders</h2><p>An order is accepted when we confirm it. We may cancel an order if items are unavailable, the address is outside our delivery area or we cannot reach you. If we cancel a paid order you will be refunded.</p>
<h2>4. Payment</h2><p>Mobile Money payments are processed securely through PesaJet using the selected network. A payment is complete only after confirmation from the payment provider. Cash on Delivery is paid to the rider on arrival.</p>
<h2>5. Delivery</h2><p>Delivery is described on the <a href="<?= e(url('delivery.php')) ?>">Delivery Information</a> page. Delivery times are estimates.</p>
<h2>6. Refunds</h2><p>See the <a href="<?= e(url('refund-policy.php')) ?>">Refund / Return Policy</a>.</p>
<h2>7. Acceptable use</h2><p>Do not misuse the site, attempt to break its security or place false orders.</p>
<h2>8. Liability</h2><p>We are responsible for delivering the food you order in good condition. We are not responsible for delays caused by events outside our control such as weather or road closures.</p>
<h2>9. Governing law</h2><p>These terms are intended to follow the laws of Uganda. <em>(To be reviewed before commercial use.)</em></p>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
