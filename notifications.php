<?php
/** notifications.php - the customer's notification inbox. */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/notifications.php';
require_login();
$user = current_user();
$uid  = (int)$user['user_id'];
$pdo  = db();
$rows = [];
$dbError = null;
$icons = ['order' => 'fa-receipt', 'payment' => 'fa-credit-card', 'promo' => 'fa-tags', 'info' => 'fa-circle-info'];

try {
    // Open one notification: mark it read, then go to its link
    if (isset($_GET['go'])) {
        $st = $pdo->prepare('SELECT link FROM notifications WHERE notification_id = ? AND user_id = ?');
        $st->execute([(int)$_GET['go'], $uid]);
        $n = $st->fetch();
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?')->execute([(int)$_GET['go'], $uid]);
        redirect($n && $n['link'] ? safe_next($n['link'], 'notifications.php') : 'notifications.php');
    }

    if (is_post()) {
        csrf_check();
        $a  = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        if ($a === 'read')           $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?')->execute([$id, $uid]);
        elseif ($a === 'read_all')   $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
        elseif ($a === 'delete')     $pdo->prepare('DELETE FROM notifications WHERE notification_id = ? AND user_id = ?')->execute([$id, $uid]);
        elseif ($a === 'delete_all') $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$uid]);
        redirect('notifications.php');
    }

    $st = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY notification_id DESC LIMIT 100');
    $st->execute([$uid]);
    $rows = $st->fetchAll();
} catch (Throwable $e) {
    error_log('notifications page: ' . $e->getMessage());
    $dbError = $e->getMessage();
}

$page_title = 'Notifications';
$active_nav = '';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner"><div class="container"><div class="banner-title-with-icon"><i class="fa-regular fa-bell"></i><div><h1>Notifications</h1><p>Updates about your orders and offers.</p></div></div></div></section>
<div class="container" style="max-width:760px;margin-bottom:40px">
<?php if ($dbError): ?>
    <div class="alert alert-error">Notifications are not available right now. Please try again later.<?= (defined('DEBUG_MODE') && DEBUG_MODE) ? ' [DEBUG: ' . e($dbError) . ']' : '' ?></div>
<?php elseif (!$rows): ?>
    <p>You have no notifications yet.</p>
<?php else: ?>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:12px">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn btn-small" type="submit"><i class="fa-solid fa-check-double"></i> Mark all read</button></form>
        <form method="post" onsubmit="return confirm('Delete all notifications?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete_all"><button class="btn btn-small" type="submit"><i class="fa-solid fa-trash"></i> Clear all</button></form>
    </div>
    <?php foreach ($rows as $n): $unread = !$n['is_read']; ?>
    <div style="display:flex;gap:12px;align-items:flex-start;padding:14px;border:1px solid #eee;border-radius:10px;margin-bottom:10px;background:<?= $unread ? '#fff5f5' : '#fff' ?>">
        <i class="fa-solid <?= e($icons[$n['type']] ?? 'fa-circle-info') ?>" style="color:#c40d0d;font-size:20px;margin-top:3px"></i>
        <div style="flex:1;min-width:0">
            <div style="font-weight:<?= $unread ? '700' : '600' ?>"><?= e($n['title']) ?><?= $unread ? ' <span style="color:#c40d0d;font-size:11px">&bull; NEW</span>' : '' ?></div>
            <div style="color:#444;margin:4px 0;white-space:pre-line"><?= e($n['message']) ?></div>
            <small style="color:#888"><?= e(date('d M Y H:i', strtotime($n['created_at']))) ?></small>
            <?php if ($n['link']): ?> &middot; <a href="<?= e(url('notifications.php?go=' . (int)$n['notification_id'])) ?>">Open</a><?php endif; ?>
        </div>
        <div style="display:flex;gap:6px">
            <?php if ($unread): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int)$n['notification_id'] ?>"><button class="btn btn-small" title="Mark read" type="submit"><i class="fa-solid fa-check"></i></button></form><?php endif; ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$n['notification_id'] ?>"><button class="btn btn-small" title="Delete" type="submit"><i class="fa-solid fa-trash"></i></button></form>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>