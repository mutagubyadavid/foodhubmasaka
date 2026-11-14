<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Ethics, Sustainability & International Considerations';
$page_description = 'Food Hub Masaka commitments: honest descriptions, fair treatment, reducing waste and packaging, and plans for currency and expansion beyond Masaka.';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div><nav class="breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Ethics, Sustainability & International Considerations</span></nav><h1>Ethics, Sustainability & International Considerations</h1><p>Last updated: <?= date('F Y') ?></p></div></div></section>
<div class="container"><div class="policy">
<div class="resp-cards" style="grid-template-columns:1fr">
<div class="resp-card"><i class="fa-solid fa-scale-balanced"></i><h3>Ethical considerations</h3><ul>
<li><strong>Honest product descriptions:</strong> descriptions say what is really in the product, and note when contents or flavours may vary.</li>
<li><strong>Transparent pricing:</strong> the price, delivery fee and any discount are shown before you pay. Sale prices compare with a real previous price.</li>
<li><strong>Customer privacy:</strong> we collect only what we need and never store card numbers or PINs (see the <a href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>).</li>
<li><strong>Responsible handling of customer information:</strong> only authorised staff can see orders; riders see only what they need to deliver.</li>
<li><strong>Fair treatment:</strong> customers get clear refund rules; delivery workers should be paid fairly, get safety equipment and not be pressured to ride dangerously to meet delivery times.</li></ul></div>
<div class="resp-card"><i class="fa-solid fa-leaf"></i><h3>Sustainability</h3><ul>
<li><strong>Less packaging:</strong> no unnecessary bags, cutlery only when requested; use recyclable or reusable packaging where possible.</li>
<li><strong>Less food waste:</strong> stock is tracked to avoid over-preparing; near-expiry items can be offered as special offers.</li>
<li><strong>Efficient delivery routes:</strong> nearby orders are grouped so riders make fewer trips.</li>
<li><strong>Digital receipts:</strong> your receipt is shown on screen in your account instead of on paper.</li></ul></div>
<div class="resp-card"><i class="fa-solid fa-earth-africa"></i><h3>International considerations</h3><ul>
<li><strong>Currency and payments:</strong> prices are in Uganda Shillings (UGX). International customers would need a currency display/conversion and a payment provider that supports foreign cards; Mobile Money mainly works within East Africa.</li>
<li><strong>Expansion:</strong> after Masaka the service could grow to nearby towns and, later, other cities by adding delivery areas and local supplier partners.</li>
<li><strong>International customers/orders:</strong> visitors from abroad can order for delivery to someone in Masaka (for example gifts for family). Cross-border shipping of fresh food is <em>not</em> offered because of food-safety, customs and delivery-time limits.</li>
<li><strong>Legal differences:</strong> each new country needs its own data-protection, tax and food-safety review.</li></ul></div>
</div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
