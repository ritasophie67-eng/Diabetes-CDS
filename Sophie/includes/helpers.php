<?php
// =====================================================================
// Helper Utilities & Security Functions
// =====================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize string output
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or get existing CSRF Token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * Render hidden CSRF form input
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Set flash message (success, danger, warning, info)
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display flash message
 */
function display_flash() {
    if (isset($_SESSION['flash'])) {
        $type = htmlspecialchars($_SESSION['flash']['type']);
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);

        return '
        <div class="alert alert-' . $type . ' alert-dismissible fade show shadow-sm" role="alert">
            ' . $message . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    return '';
}

/**
 * Immutable Audit Logger
 */
function log_audit($pdo, $user_id, $target_table, $action, $details) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, target_table, action, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id ? (int)$user_id : null,
            $target_table,
            strtoupper($action),
            is_array($details) ? json_encode($details) : (string)$details
        ]);
        return true;
    } catch (Exception $e) {
        // Never break execution if audit logging encounters a passive warning
        error_log("Audit log failed: " . $e->getMessage());
        return false;
    }
}
