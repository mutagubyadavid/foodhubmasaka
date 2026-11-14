<?php
/**
 * contact.php - CUSTOMER SUPPORT
 * Messages are saved in the support_messages table; the admin reads them in admin/messages.php.
 */
require_once __DIR__ . '/includes/init.php';

$subjects = ['Order question', 'Delivery question', 'Payment question', 'Product question', 'Account help', 'Complaint', 'Suggestion', 'Other'];
$user = current_user();
$old = ['name' => $user['full_name'] ?? '', 'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? '', 'subject' => '', 'message' => ''];
if (!empty($_GET['subject'])) {           // links like contact.php?subject=Account+help
    foreach ($subjects as $s) if (strcasecmp($s, $_GET['subject']) === 0) $old['subject'] = $s;
}
$errors = [];

if (is_post()) {
    csrf_check();
    $old['name'] = clean($_POST['name'] ?? ''); $old['email'] = strtolower(clean($_POST['email'] ?? ''));
    $old['phone'] = clean($_POST['phone'] ?? ''); $old['subject'] = clean($_POST['subject'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    if (!empty($_POST['website']))                       { flash_set('success', 'Thank you! Your message was sent.'); redirect('contact.php'); }   // hidden "honeypot" field: bots fill it in
    if (mb_strlen($old['name']) < 2)                     $errors[] = 'Please enter your name.';
    if (!valid_email($old['email']))                     $errors[] = 'Please enter a valid email address.';
    $phone = null;
    if ($old['phone'] !== '') { $phone = normalize_phone($old['phone']); if ($phone === '') $errors[] = 'Please enter a valid phone number or leave it empty.'; }
    if (!in_array($old['subject'], $subjects, true))     $errors[] = 'Please choose a subject.';
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 2000) $errors[] = 'Your message must be between 10 and 2000 characters.';

    if (!$errors) {
        db()->prepare('INSERT INTO support_messages (user_id, full_name, email, phone, subject, message) VALUES (?,?,?,?,?,?)')
            ->execute([$user['user_id'] ?? null, $old['name'], $old['email'], $phone ?: null, $old['subject'], $old['message']]);
        flash_set('success', 'Thank you! Your message was sent. We will reply within 24 hours.');
        redirect('contact.php');
    }
}

$page_title = 'Contact & Customer Support';
$page_description = 'Contact Food Hub Masaka customer support: phone, email, WhatsApp and message form. Mon - Sat, 8 AM - 8 PM.';
$active_nav = 'contact';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container">
        <div><p class="eyebrow">We'd Love to Hear From You</p><h1>Contact Us</h1>
            <p>Have a question, suggestion or need help? Our team is here for you! Get in touch and we'll be happy to assist you.</p></div>
        <div class="banner-script white">Your Food<br>Our Priority!</div>
    </div>
</section>

<div class="container contact-layout">
    <div class="contact-cards">
        <div class="contact-card"><span class="ic" style="background:#d10a0a"><i class="fa-solid fa-phone"></i></span><div><h3>Phone</h3><p>Call us for quick assistance</p><strong><a href="tel:<?= e(SITE_PHONE_LINK) ?>" style="color:inherit"><?= e(SITE_PHONE) ?></a></strong><p>Mon - Sat: 8:00 AM - 8:00 PM</p></div></div>
        <div class="contact-card"><span class="ic" style="background:#178a4c"><i class="fa-solid fa-envelope"></i></span><div><h3>Email</h3><p>Send us an email anytime</p><strong><a href="mailto:<?= e(SITE_EMAIL) ?>" style="color:inherit"><?= e(SITE_EMAIL) ?></a></strong><p>We'll respond within 24 hours.</p></div></div>
        <div class="contact-card"><span class="ic" style="background:#1a73d1"><i class="fa-solid fa-location-dot"></i></span><div><h3>Our Location</h3><p>Visit us at our office</p><strong><?= e(SITE_LOCATION) ?></strong></div></div>
        <div class="contact-card"><span class="ic" style="background:#8b5cf6"><i class="fa-brands fa-whatsapp"></i></span><div><h3>WhatsApp Chat</h3><p>Chat with our support team</p><strong><a href="https://wa.me/<?= e(ltrim(SITE_PHONE_LINK, '+')) ?>" target="_blank" rel="noopener" style="color:inherit">Start a chat</a></strong><p>8:00 AM - 8:00 PM</p></div></div>
    </div>

    <div class="form-card">
        <h2 style="font-size:24px">Send Us a Message</h2>
        <p class="muted">Fill in the form below and we'll get back to you as soon as possible.</p>
        <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>
        <form method="post" data-loading>
            <?= csrf_field() ?>
            <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="form-row">
                <div class="form-group"><label for="name" class="sr-only">Full name</label><div class="input-icon"><i class="fa-regular fa-user"></i><input class="form-control" id="name" name="name" required maxlength="100" placeholder="Full Name *" value="<?= e($old['name']) ?>"></div></div>
                <div class="form-group"><label for="email" class="sr-only">Email</label><div class="input-icon"><i class="fa-regular fa-envelope"></i><input class="form-control" id="email" type="email" name="email" required maxlength="150" placeholder="Email Address *" value="<?= e($old['email']) ?>"></div></div>
            </div>
            <div class="form-group"><label for="phone" class="sr-only">Phone</label><div class="input-icon"><i class="fa-solid fa-phone"></i><input class="form-control" id="phone" type="tel" name="phone" maxlength="20" placeholder="Phone Number" value="<?= e($old['phone']) ?>"></div></div>
            <div class="form-group"><label for="subject" class="sr-only">Subject</label><div class="input-icon"><i class="fa-solid fa-tag"></i>
                <select class="form-control" id="subject" name="subject" required><option value="">Subject *</option>
                    <?php foreach ($subjects as $s): ?><option <?= $old['subject'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div></div>
            <div class="form-group"><label for="message" class="sr-only">Message</label><div class="input-icon textarea-icon"><i class="fa-solid fa-pen"></i>
                <textarea class="form-control" id="message" name="message" required minlength="10" maxlength="2000" rows="5" placeholder="Your Message *"><?= e($old['message']) ?></textarea></div></div>
            <button class="btn btn-primary btn-block btn-large" type="submit"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
        </form>
    </div>

    <div class="contact-side">
        <div class="map-card">
            <iframe title="Map of Masaka town" loading="lazy" referrerpolicy="no-referrer"
                    src="https://www.openstreetmap.org/export/embed.html?bbox=31.70%2C-0.36%2C31.78%2C-0.30&amp;layer=mapnik&amp;marker=-0.3333%2C31.7333"></iframe>
        </div>
        <div class="help-card"><i class="fa-solid fa-headset"></i><div><h3>Need Immediate Help?</h3><p>Call our support team or chat on WhatsApp for faster assistance.</p>
            <a class="btn btn-primary btn-pill" href="https://wa.me/<?= e(ltrim(SITE_PHONE_LINK, '+')) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Chat Now</a></div></div>
    </div>
</div>
<div class="container" style="padding-bottom:40px"><p class="muted" style="font-size:13px">See our <a href="<?= e(url('faq.php')) ?>">Frequently Asked Questions</a> for more information.</p></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
