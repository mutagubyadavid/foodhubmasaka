<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Privacy Policy';
$page_description = 'How Food Hub Masaka collects, uses and protects your personal information.';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div><nav class="breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Privacy Policy</span></nav><h1>Privacy Policy</h1><p>Last updated: <?= date('F Y') ?></p></div></div></section>
<div class="container"><div class="policy">
<h2>1. Who we are</h2>
<p>Food Hub Masaka ("we", "us") is an online food ordering and delivery service for customers in Masaka, Uganda. Contact: <?= e(SITE_EMAIL) ?>.</p>
<h2>2. Information we collect</h2>
<ul>
  <li><strong>Account details:</strong> full name, email, phone number, delivery address and a password (stored only as a secure hash).</li>
  <li><strong>Order details:</strong> items ordered, delivery address and area, order notes, payment method and payment status.</li>
  <li><strong>Support messages:</strong> what you write to us through the contact form.</li>
  <li><strong>Newsletter:</strong> your email address if you subscribe.</li>
  <li><strong>Website usage:</strong> anonymous statistics through Google Analytics 4, only if you accept analytics cookies.</li>
</ul>
<p>We do <strong>not</strong> collect or store card numbers, CVV codes or Mobile Money PINs.</p>
<h2>3. How we use your information</h2>
<ul><li>To process and deliver your orders and send order updates.</li><li>To answer your questions and provide customer support.</li><li>To send offers you asked for (you can unsubscribe at any time).</li><li>To improve the website and keep it secure.</li></ul>
<h2>4. Sharing</h2>
<p>We share only what is needed with delivery riders (name, phone, address and order) and service providers such as hosting and analytics. We do not sell personal information.</p>
<h2>5. Security and retention</h2>
<p>We use hashed passwords, prepared database queries, session protection and access control for administrators. We keep order records for as long as needed for business and legal reasons, and delete other data on request where possible.</p>
<h2>6. Your rights</h2>
<p>You may ask to see, correct or delete your personal information, or withdraw consent for marketing, by contacting us at <?= e(SITE_EMAIL) ?>.</p>
<h2>7. Cookies</h2>
<p>See our <a href="<?= e(url('cookies.php')) ?>">Cookie Policy</a>.</p>
<h2>8. Changes</h2><p>We may update this policy and will show the date of the latest update at the top of this page.</p>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
