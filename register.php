<?php
/**
 * register.php - CUSTOMER REGISTRATION
 * Collects: full name, email, phone, delivery address, password.
 * The password is stored ONLY as a hash created by password_hash().
 */
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) redirect('account.php');

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];

if (is_post()) {
    csrf_check();
    $old['name']    = clean($_POST['name'] ?? '');
    $old['email']   = strtolower(clean($_POST['email'] ?? ''));
    $old['phone']   = clean($_POST['phone'] ?? '');
    $old['address'] = clean($_POST['address'] ?? '');
    $password       = $_POST['password'] ?? '';

    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) $errors[] = 'Please enter your full name (2-100 characters).';
    if (!valid_email($old['email']))                                  $errors[] = 'Please enter a valid email address.';
    $phone = normalize_phone($old['phone']);
    if ($phone === '')                                                $errors[] = 'Please enter a valid Uganda phone number (e.g. 0700 123 456).';
    if (mb_strlen($old['address']) < 5 || mb_strlen($old['address']) > 255) $errors[] = 'Please enter your delivery address.';
    if (!valid_password($password))                                   $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    if (empty($_POST['terms']))                                       $errors[] = 'You must agree to the Terms and Conditions and Privacy Policy.';

    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR phone = ?');
        $stmt->execute([$old['email'], $phone]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'An account with this email or phone number already exists. Try logging in instead.';
    }

    if (!$errors) {
        $ins = db()->prepare('INSERT INTO users (full_name, email, phone, password_hash, delivery_address) VALUES (?,?,?,?,?)');
        $ins->execute([$old['name'], $old['email'], $phone, password_hash($password, PASSWORD_DEFAULT), $old['address']]);
        login_user((int)db()->lastInsertId());
        flash_set('success', 'Your account has been created. Welcome to Food Hub Masaka!');
        redirect('products.php');
    }
}

$page_title = 'Create Your Account';
$page_description = 'Register with Food Hub Masaka to order food online, save your delivery address and track your orders.';
$show_newsletter = false;
include __DIR__ . '/includes/header.php';
$auth_variant = 'register';
?>
<div class="container auth-wrap">
    <?php include __DIR__ . '/includes/auth-side.php'; ?>
    <div class="auth-card">
        <a class="logo" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%"><?php include __DIR__ . '/includes/logo.php'; ?></a>
        <h1>Create Your Account</h1>
        <p class="sub">Fill in your details to get started.</p>

        <?php if ($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>

        <form method="post" data-loading>
            <?= csrf_field() ?>
            <div class="form-group"><label for="name">Full Name <span class="req">*</span></label>
                <div class="input-icon"><i class="fa-regular fa-user"></i><input class="form-control" id="name" name="name" required maxlength="100" value="<?= e($old['name']) ?>" placeholder="Enter your full name" autocomplete="name"></div></div>
            <div class="form-group"><label for="email">Email Address <span class="req">*</span></label>
                <div class="input-icon"><i class="fa-regular fa-envelope"></i><input class="form-control" id="email" type="email" name="email" required maxlength="150" value="<?= e($old['email']) ?>" placeholder="Enter your email address" autocomplete="email"></div></div>
            <div class="form-group"><label for="phone">Phone Number <span class="req">*</span></label>
                <div class="input-icon"><i class="fa-solid fa-phone"></i><input class="form-control" id="phone" type="tel" name="phone" required maxlength="20" value="<?= e($old['phone']) ?>" placeholder="e.g. 0700 123 456" autocomplete="tel"></div></div>
            <div class="form-group"><label for="address">Delivery Address <span class="req">*</span></label>
                <div class="input-icon"><i class="fa-solid fa-location-dot"></i><input class="form-control" id="address" name="address" required maxlength="255" value="<?= e($old['address']) ?>" placeholder="Enter your delivery address" autocomplete="street-address"></div></div>
            <div class="form-group"><label for="password">Password <span class="req">*</span></label>
                <div class="input-icon"><i class="fa-solid fa-lock"></i><input class="form-control" id="password" type="password" name="password" required minlength="8" placeholder="Create a strong password" autocomplete="new-password">
                    <button type="button" class="pw-toggle" aria-label="Show password"><i class="fa-solid fa-eye-slash"></i></button></div>
                <p class="form-hint">At least 8 characters, with a letter and a number.</p></div>
            <div class="form-group"><label class="check"><input type="checkbox" name="terms" value="1" required>
                <span>I agree to the <a href="<?= e(url('terms.php')) ?>" target="_blank">Terms and Conditions</a> and <a href="<?= e(url('privacy.php')) ?>" target="_blank">Privacy Policy</a></span></label></div>
            <button class="btn btn-primary btn-block btn-large" type="submit"><i class="fa-solid fa-user-plus"></i> Register</button>
        </form>
        <p class="auth-switch">Already have an account? <a href="<?= e(url('login.php')) ?>">Login here</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
