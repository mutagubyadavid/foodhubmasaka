<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Refund / Return Policy';
$page_description = 'Food Hub Masaka refund and return policy for food orders, wrong items and undelivered orders.';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div><nav class="breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Refund / Return Policy</span></nav><h1>Refund / Return Policy</h1><p>Last updated: <?= date('F Y') ?></p></div></div></section>
<div class="container"><div class="policy">
<h2>Food safety first</h2><p>Because food is perishable, we cannot accept returns of food that was delivered correctly and in good condition.</p>
<h2>When you can get a refund or replacement</h2>
<table><tr><th>Situation</th><th>What we do</th></tr>
<tr><td>Wrong item delivered</td><td>Replacement or refund of the item.</td></tr>
<tr><td>Item missing from your order</td><td>Delivery of the missing item or a refund of its price.</td></tr>
<tr><td>Food damaged, spoiled or not as described</td><td>Replacement or refund after we check your photo/description.</td></tr>
<tr><td>Order not delivered</td><td>Full refund (including the delivery fee) or re-delivery.</td></tr>
<tr><td>Customer unavailable at delivery</td><td>We call you and wait a few minutes. If delivery fails the order may be returned to the kitchen; a refund may be partial (delivery fee and prepared food may not be refundable).</td></tr>
<tr><td>Order cancelled by you before "Out for Delivery"</td><td>Full refund.</td></tr></table>
<h2>How to ask</h2><p>Contact us within <strong>2 hours</strong> of delivery through the <a href="<?= e(url('contact.php')) ?>">Contact page</a> or on <?= e(SITE_PHONE) ?> with your order number.</p>
<h2>How refunds are paid</h2><p>Cash on Delivery orders are refunded by Mobile Money or as credit on a future order. Mobile Money payments are refunded through the store's refund process; changing a payment status in the admin area does not itself send a refund through PesaJet.</p>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
