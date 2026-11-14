<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'About Us';
$page_description = 'About Food Hub Masaka - a local online food ordering and delivery business bringing good food to students, families and workers in Masaka, Uganda.';
$active_nav = 'about';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container">
        <div><p class="banner-eyebrow-yellow">About Us</p><h1>Good Food. Great People.</h1>
            <p>Food Hub Masaka is more than just a food delivery service &mdash; we're a local business with a passion for great food and happy customers.</p><span class="banner-underline"></span></div>
        <div class="banner-script white">Fresh Food<br>Delivered to<br>Your Door</div>
    </div>
</section>
<div class="container">
    <div class="about-layout">
        <div class="about-story">
            <h2 class="section-title" style="font-size:30px;margin-bottom:16px">Our Story</h2>
            <p>Food Hub Masaka was founded with a simple goal &mdash; to make delicious food easily accessible to everyone in Masaka. We noticed that many people, especially students, busy professionals and families, found it difficult to get their favourite meals without going to the shop.</p>
            <p>So we created Food Hub Masaka &mdash; an online platform where you can browse, order and get your favourite food, snacks and beverages delivered right to your door. Our aim is to work with local restaurants and suppliers to bring fresh, tasty and affordable meals, all in one place.</p>
            <a class="btn btn-primary btn-pill" href="<?= e(url('products.php')) ?>"><i class="fa-solid fa-cart-shopping"></i> Shop Now <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="about-photo"><img src="<?= e(url('images/about/team-member.jpg')) ?>" alt="Food Hub Masaka team member holding a branded paper bag"><div class="script-lg">Local Food<br>Local People<br>Local Delivery</div></div>
    </div>

    <div class="about-layout" style="padding-top:0">
        <div>
            <div class="mission-box">
                <div><i class="fa-solid fa-bullseye"></i><div><h3>Our Mission</h3><p>To provide convenient, affordable and reliable online food delivery services that bring happiness to our customers in Masaka and beyond.</p></div></div>
                <div><i class="fa-solid fa-eye"></i><div><h3>Our Vision</h3><p>To become the leading food delivery platform in Masaka, known for quality, convenience and excellent customer service.</p></div></div>
            </div>
            <div class="values-head" style="margin-bottom:10px"><h2 class="section-title" style="font-size:20px">Our Values</h2></div>
            <div class="values" style="grid-template-columns:repeat(5,1fr)">
                <div class="v"><i class="fa-solid fa-leaf"></i><h3>Freshness</h3><p>We always serve fresh and quality food.</p></div>
                <div class="v"><i class="fa-solid fa-users"></i><h3>Customer First</h3><p>Your satisfaction is our priority.</p></div>
                <div class="v"><i class="fa-solid fa-handshake"></i><h3>Integrity</h3><p>We operate with honesty and transparency.</p></div>
                <div class="v"><i class="fa-regular fa-lightbulb"></i><h3>Innovation</h3><p>We keep improving for a better experience.</p></div>
                <div class="v"><i class="fa-solid fa-heart"></i><h3>Community</h3><p>We support local businesses and our Masaka community.</p></div>
            </div>
        </div>
        <div>
            <div class="why-box">
                <h2>Why Choose Us?</h2>
                <div class="why-item"><span class="ic"><i class="fa-solid fa-utensils"></i></span><div><h3>Quality Food</h3><p>We serve fresh, tasty and carefully selected food products.</p></div></div>
                <div class="why-item"><span class="ic"><i class="fa-solid fa-truck-fast"></i></span><div><h3>Fast Delivery</h3><p>Your food is delivered quickly and safely to your doorstep.</p></div></div>
                <div class="why-item"><span class="ic"><i class="fa-solid fa-shield-halved"></i></span><div><h3>Safe &amp; Secure</h3><p>Mobile Money payments are processed through PesaJet. We never collect your PIN.</p></div></div>
                <div class="why-item"><span class="ic"><i class="fa-solid fa-heart"></i></span><div><h3>Affordable Prices</h3><p>Great food at prices you'll love.</p></div></div>
                <div class="why-item"><span class="ic"><i class="fa-solid fa-location-dot"></i></span><div><h3>Supporting Local</h3><p>We aim to work with local suppliers in Masaka.</p></div></div>
            </div>
        </div>
    </div>
    <p class="muted" style="font-size:13px;padding-bottom:30px">Read about our <a href="<?= e(url('responsibility.php')) ?>">ethics and sustainability commitments</a>.</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
