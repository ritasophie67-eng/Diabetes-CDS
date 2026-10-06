<?php
// =====================================================================
// Logout Action Handler
// =====================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = get_db_connection();
logout_user($pdo);

set_flash('info', 'You have been safely signed out. Thank you.');
header('Location: ' . BASE_URL . 'login.php');
exit;
