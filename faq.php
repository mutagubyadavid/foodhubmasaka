<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Frequently Asked Questions';
$page_description = 'Answers to common questions about ordering, payment, delivery, accounts and refunds at Food Hub Masaka.';
$active_nav = 'faq';

$faqs = [
  'Ordering' => ['fa-cart-shopping', [
    ['How do I place an order?', 'Browse the shop, add products to your cart, then click Proceed to Checkout. You need a free account to complete an order so we can save your address and show your order history.'],
    ['Can I change or cancel my order?', 'Please contact us as soon as possible on ' . SITE_PHONE . '. Orders can only be cancelled before they are marked Out for Delivery.'],
    ['What if a product is out of stock?', 'Out-of-stock products cannot be added to the cart. Stock levels are updated as orders are placed.'],
    ['Do you have discount codes?', 'Yes. Enter a code such as WELCOME10 in the coupon box in your cart. Subscribe to our newsletter to hear about new offers.'],
  ]],
  'Payment' => ['fa-credit-card', [
    ['Which payment methods can I use?', 'MTN Mobile Money and Cash on Delivery. MTN payment requests are verified with the provider. The site does not collect Mobile Money PINs.'],
    ['Do you store my card details?', 'No. The website never asks for or stores card numbers, CVV codes or Mobile Money PINs.'],
    ['When do I pay for Cash on Delivery?', 'You pay the rider in cash when the order arrives. Please have the right amount ready if you can.'],
  ]],
  'Delivery' => ['fa-truck-fast', [
    ['Where do you deliver?', 'Masaka town and nearby areas listed on the Delivery Information page. If your area is not listed, contact us to confirm.'],
    ['How much is delivery?', 'Delivery costs ' . format_ugx(DELIVERY_FEE_SMALL_ORDER) . ' for orders below ' . format_ugx(DELIVERY_SMALL_LIMIT) . ', ' . format_ugx(DELIVERY_FEE_MEDIUM_ORDER) . ' for orders up to ' . format_ugx(FREE_DELIVERY_ABOVE) . ', and is free above ' . format_ugx(FREE_DELIVERY_ABOVE) . '.'],
    ['How long does delivery take?', 'Usually 30 - 60 minutes in Masaka Town Centre and 45 - 90 minutes in nearby areas. Peak hours and bad weather can cause delays.'],
    ['How can I track my order?', 'Log in and open My Orders. You will see the status: Pending, Processing, Out for Delivery or Delivered.'],
  ]],
  'Account & privacy' => ['fa-user', [
    ['I forgot my password. What should I do?', 'Send us a message from the Contact page (subject: Account help) and our team will help you regain access.'],
    ['How do you use my information?', 'Only to process your orders, deliver your food and support you. Read our Privacy Policy for details.'],
  ]],
  'Refunds & problems' => ['fa-rotate-left', [
    ['What if my food arrives wrong or damaged?', 'Contact us within 2 hours with your order number (a photo helps). We will replace the item or refund it under our Refund / Return Policy.'],
    ['Can I return food?', 'For food safety reasons we cannot take back food that was delivered correctly. See the Refund / Return Policy for details.'],
  ]],
];
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container"><div class="banner-title-with-icon"><i class="fa-solid fa-circle-question"></i><div><h1>Frequently Asked Questions</h1><p>Quick answers to the questions we hear most. Can't find yours? <a href="<?= e(url('contact.php')) ?>" style="color:var(--yellow)">Contact us</a>.</p></div></div></div>
</section>
<div class="container" style="max-width:900px;padding-top:34px;padding-bottom:50px">
    <?php foreach ($faqs as $group => [$icon, $items]): ?>
        <div class="faq-group">
            <h2><i class="fa-solid <?= e($icon) ?>"></i> <?= e($group) ?></h2>
            <?php foreach ($items as [$q, $a]): ?>
                <details class="faq"><summary><?= e($q) ?></summary><div class="answer"><?= e($a) ?></div></details>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
