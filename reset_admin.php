<?php
require_once __DIR__ . '/includes/init.php';

$password = 'Admin123!';
$hash = password_hash($password, PASSWORD_DEFAULT);
$email = 'admin@foodhubmasaka.com';

// Update or insert into admins table
$stmt = db()->prepare('SELECT admin_id FROM admins WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$admin = $stmt->fetch();

if ($admin) {
    db()->prepare('UPDATE admins SET password_hash = ? WHERE admin_id = ?')
        ->execute([$hash, $admin['admin_id']]);
    echo "<h2>Admin password updated successfully!</h2>";
} else {
    db()->prepare('INSERT INTO admins (full_name, email, password_hash) VALUES (?, ?, ?)')
        ->execute(['Store Administrator', $email, $hash]);
    echo "<h2>Admin account created successfully!</h2>";
}

echo "<p><strong>Email:</strong> $email</p>";
echo "<p><strong>Password:</strong> $password</p>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";