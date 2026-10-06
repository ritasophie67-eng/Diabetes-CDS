<?php
// =====================================================================
// Entry Point Router
// =====================================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    $role = $_SESSION['role'];
    if ($role === 'patient') {
        header('Location: ' . BASE_URL . 'patient/dashboard.php');
        exit;
    } elseif ($role === 'clinician') {
        header('Location: ' . BASE_URL . 'clinician/dashboard.php');
        exit;
    } elseif ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit;
    }
}

// If guest, direct to login
header('Location: ' . BASE_URL . 'login.php');
exit;
