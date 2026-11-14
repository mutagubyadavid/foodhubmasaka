<?php
/**
 * login.php - CUSTOMER OR ADMIN LOGIN
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/init.php';

if (is_admin_logged_in()) redirect('admin/index.php');
if (is_logged_in()) redirect('account.php');

$errors = [];
$identity = '';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', 'account.php');

if (is_post()) {
    $identity = clean($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identity === '' || $password === '') {
        $errors[] = 'Please enter your email or phone number and your password.';
    } else {
        $adminEmail = strtolower($identity);
        $adminStmt = db()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $adminStmt->execute([$adminEmail]);
        $admin = $adminStmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION['user_id']);
            $_SESSION['admin_id'] = (int)$admin['admin_id'];
            db()->prepare('UPDATE admins SET last_login = NOW() WHERE admin_id = ?')->execute([$admin['admin_id']]);
            flash_set('success', 'Welcome back, ' . explode(' ', $admin['full_name'])[0] . '!');
            redirect($next === 'account.php' ? 'admin/index.php' : $next);
        } else {
            $phone = normalize_phone($identity);
            $stmt  = db()->prepare('SELECT * FROM users WHERE email = ? OR (phone = ? AND ? <> "") LIMIT 1');
            $stmt->execute([$adminEmail, $phone, $phone]);
            $u = $stmt->fetch();

            if ($u && password_verify($password, $u['password_hash'])) {
                if ($u['status'] !== 'active') {
                    $errors[] = 'This account has been suspended. Please contact support.';
                } else {
                    login_user((int)$u['user_id']);
                    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                        db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['user_id']]);
                    }
                    flash_set('success', 'Welcome back, ' . explode(' ', $u['full_name'])[0] . '!');
                    redirect($next);
                }
            } else {
                $errors[] = 'Incorrect email/phone or password.';
            }
        }
    }
}

$page_title = 'Login';
$page_description = 'Log in to your Food Hub Masaka account to order food online and track your deliveries.';
$show_newsletter = false;
include __DIR__ . '/includes/header.php';
$auth_variant = 'login';
?>
<div class="container auth-wrap">
    <?php include __DIR__ . '/includes/auth-side.php'; ?>
    <div class="auth-card">
        <a class="logo" href="<?= e(url('index.php')) ?>" style="justify-content:center;width:100%"><?php include __DIR__ . '/includes/logo.php'; ?></a>
        <h1>Login to Your Account</h1>
        <p class="sub">Welcome back! Please log in to continue.</p>

        <?php if ($errors): ?><div class="alert alert-error" role="alert"><?php foreach ($errors as $er) echo '<div>' . e($er) . '</div>'; ?></div><?php endif; ?>

        <form method="post" data-loading>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="form-group">
                              <label for="identity">Email Address or Phone Number</label>
                <div class="input-icon"><i class="fa-regular fa-envelope"></i>
                    <input class="form-control" id="identity" name="identity" required value="<?= e($identity) ?>" placeholder="Enter your email or phone number" autocomplete="username"></div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon"><i class="fa-solid fa-lock"></i>
                    <input class="form-control" id="password" type="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="pw-toggle" aria-label="Show password"><i class="fa-solid fa-eye-slash"></i></button></div>
            </div>
            <div class="row-between"><span class="muted" style="font-size:14px">&nbsp;</span>
                <a href="<?= e(url('contact.php?subject=Account+help')) ?>" style="font-size:14px;font-weight:600">Forgot password?</a></div>
            <button class="btn btn-primary btn-block btn-large" type="submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
        </form>
        <p class="auth-switch">Don't have an account? <a href="<?= e(url('register.php')) ?>">Register here</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>