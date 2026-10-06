<?php
// =====================================================================
// User Login Interface
// =====================================================================

$page_title = "Sign In";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username_val = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username_val) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $pdo = get_db_connection();
            $result = attempt_login($pdo, $username_val, $password);

            if ($result['success']) {
                set_flash('success', 'Welcome back, ' . htmlspecialchars($_SESSION['full_name']) . '!');
                
                if ($result['role'] === 'clinician') {
                    header('Location: ' . BASE_URL . 'clinician/dashboard.php');
                } elseif ($result['role'] === 'patient') {
                    header('Location: ' . BASE_URL . 'patient/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . 'admin/dashboard.php');
                }
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center py-4 rounded-top">
                    <i class="bi bi-hospital fs-1 mb-2 d-inline-block"></i>
                    <h4 class="mb-0 fw-bold">Sign In to CDSS</h4>
                    <p class="small text-white-50 mb-0">Sir Albert Cook Hospital Clinical Portal</p>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($username_val) ?>" placeholder="Enter username or email" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Access System
                        </button>
                    </form>

                    <hr class="my-4">

                    <!-- Demonstration Quick-Fill Helper (Ideal for Academic Defense) -->
                    <div class="bg-light p-3 rounded border">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-mortarboard text-primary me-2"></i>
                            <span class="small fw-bold text-secondary">Academic Defense Quick-Fill:</span>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm text-start" 
                                    onclick="fillCredentials('dr_kakooza', 'Password123!')">
                                <i class="bi bi-clipboard2-pulse me-1"></i> Clinician: <strong>Dr. Tobias Kakooza</strong>
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm text-start" 
                                    onclick="fillCredentials('sophie_patient', 'Password123!')">
                                <i class="bi bi-person-heart me-1"></i> Patient: <strong>Alaba Rita Sophie</strong>
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm text-start" 
                                    onclick="fillCredentials('admin', 'Password123!')">
                                <i class="bi bi-shield-lock me-1"></i> Hospital Admin: <strong>admin</strong>
                            </button>
                        </div>
                        <div class="mt-2 text-center">
                            <small class="text-muted" style="font-size: 0.75rem;">Password for all demo accounts: <code>Password123!</code></small>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <span class="text-muted small">New patient?</span> 
                        <a href="register.php" class="small fw-semibold">Create an Account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCredentials(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
