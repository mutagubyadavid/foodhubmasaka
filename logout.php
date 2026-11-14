<?php
/**
 * logout.php - ends the session, then shows the "You've logged out" page.
 */
require_once __DIR__ . '/includes/init.php';

$wasLoggedIn = is_logged_in();
if ($wasLoggedIn) {
    logout_user();
    session_start();                 // fresh, empty session so the header can still show messages
    session_regenerate_id(true);
}
$page_title = 'Logged out';
$show_newsletter = false;
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner" style="--banner-img:url('<?= e(url('images/banners/burger.jpg')) ?>')">
    <div class="container">
        <div>
            <nav class="breadcrumb"><a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Home</a> <i class="fa-solid fa-chevron-right"></i> <span>Logout</span></nav>
            <h1>Logout</h1>
            <p>You have been successfully logged out of your account.</p>
        </div>
        <div class="banner-script">See you soon!</div>
    </div>
</section>
<div class="container">
    <div class="logout-card">
        <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i><span class="ok"><i class="fa-solid fa-check"></i></span></div>
        <h2 style="font-size:30px">You've Logged Out!</h2>
        <p class="muted">Thank you for visiting Food Hub Masaka.<br>We hope to see you again soon!</p>
        <p><a class="btn btn-primary btn-pill btn-large" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-house"></i> Go to Homepage <i class="fa-solid fa-arrow-right"></i></a></p>
        <p><a href="<?= e(url('login.php')) ?>"><i class="fa-solid fa-user-plus"></i> Log in Again <i class="fa-solid fa-arrow-right"></i></a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
