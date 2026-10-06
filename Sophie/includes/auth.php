<?php
// =====================================================================
// Authentication & Role-Based Access Control (RBAC) Module
// =====================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently authenticated
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current session user data
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'user_id'   => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'role'      => $_SESSION['role'],
        'email'     => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['username']
    ];
}

/**
 * Login user with username & password verification
 */
function attempt_login($pdo, $username, $password) {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.password_hash, u.role, u.email,
               COALESCE(c.full_name, p.full_name, u.username) AS full_name,
               c.clinician_id, p.patient_id
        FROM users u
        LEFT JOIN clinicians c ON u.user_id = c.user_id
        LEFT JOIN patients p ON u.user_id = p.user_id
        WHERE u.username = ? OR u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Prevent session fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id']      = (int)$user['user_id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['full_name']    = $user['full_name'];
        $_SESSION['clinician_id'] = $user['clinician_id'] ? (int)$user['clinician_id'] : null;
        $_SESSION['patient_id']   = $user['patient_id'] ? (int)$user['patient_id'] : null;

        // Audit log login
        log_audit($pdo, $user['user_id'], 'users', 'LOGIN', "User {$user['username']} logged in with role {$user['role']}.");

        return ['success' => true, 'role' => $user['role']];
    }

    return ['success' => false, 'message' => 'Invalid username or password.'];
}

/**
 * Terminate user session and log action
 */
function logout_user($pdo) {
    if (is_logged_in()) {
        $userId = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        log_audit($pdo, $userId, 'users', 'LOGOUT', "User {$username} logged out.");
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Access Guard: Require logged in user
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access this page.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Access Guard: Restrict access by Role
 * @param array|string $allowed_roles
 */
function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        set_flash('danger', 'Unauthorized access! You do not have permission to view that page.');
        
        // Redirect to their respective authorized area
        if ($_SESSION['role'] === 'patient') {
            header('Location: ' . BASE_URL . 'patient/dashboard.php');
        } elseif ($_SESSION['role'] === 'clinician') {
            header('Location: ' . BASE_URL . 'clinician/dashboard.php');
        } elseif ($_SESSION['role'] === 'admin') {
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . 'login.php');
        }
        exit;
    }
}
