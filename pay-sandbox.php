<?php
/** Legacy URL retained for old order links; payment status is checked with MTN. */
require_once __DIR__ . '/includes/init.php';
require_login();
redirect('pesajet.php?order=' . urlencode($_GET['order'] ?? ''));
