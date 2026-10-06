<?php
// =====================================================================
// Patient Self-Registration Interface
// =====================================================================

$page_title = "Patient Registration";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = get_db_connection();

// Fetch available clinicians for assignment
$clinicians = [];
try {
    $stmt = $pdo->query("SELECT clinician_id, full_name, specialization FROM clinicians ORDER BY full_name ASC");
    $clinicians = $stmt->fetchAll();
} catch (Exception $e) {
    // Handled silently if DB is being initialized
}

$error = '';
$form_data = [
    'full_name'    => '',
    'username'     => '',
    'email'        => '',
    'phone'        => '',
    'date_of_birth'=> '',
    'gender'       => '',
    'clinician_id' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Session expired. Please try again.';
    } else {
        $form_data['full_name']     = trim($_POST['full_name'] ?? '');
        $form_data['username']      = trim($_POST['username'] ?? '');
        $form_data['email']         = trim($_POST['email'] ?? '');
        $form_data['phone']         = trim($_POST['phone'] ?? '');
        $form_data['date_of_birth'] = trim($_POST['date_of_birth'] ?? '');
        $form_data['gender']        = trim($_POST['gender'] ?? '');
        $form_data['clinician_id']  = !empty($_POST['clinician_id']) ? (int)$_POST['clinician_id'] : null;
        $password                   = $_POST['password'] ?? '';
        $confirm_password           = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($form_data['full_name']) || empty($form_data['username']) || empty($form_data['email']) || empty($password)) {
            $error = 'All primary fields (Name, Username, Email, Password) are required.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Check if username or email already exists
                $chk = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
                $chk->execute([$form_data['username'], $form_data['email']]);
                if ($chk->fetch()) {
                    $error = 'Username or Email is already registered.';
                } else {
                    $pdo->beginTransaction();

                    // 1. Insert into users
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare("
                        INSERT INTO users (username, password_hash, role, email, created_at)
                        VALUES (?, ?, 'patient', ?, NOW())
                    ");
                    $stmtUser->execute([$form_data['username'], $hash, $form_data['email']]);
                    $newUserId = (int)$pdo->lastInsertId();

                    // 2. Insert into patients
                    $stmtPat = $pdo->prepare("
                        INSERT INTO patients (user_id, clinician_id, full_name, date_of_birth, gender, phone)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmtPat->execute([
                        $newUserId,
                        $form_data['clinician_id'],
                        $form_data['full_name'],
                        $form_data['date_of_birth'] ?: '2000-01-01',
                        $form_data['gender'] ?: 'other',
                        $form_data['phone'] ?: 'N/A'
                    ]);
                    $newPatientId = (int)$pdo->lastInsertId();

                    // 3. Initialize default ADA thresholds for patient
                    $stmtThresh = $pdo->prepare("
                        INSERT INTO patient_thresholds (patient_id, target_fasting_min, target_fasting_max, target_postprandial_max, hypo_threshold, severe_hypo_threshold)
                        VALUES (?, 80.00, 130.00, 180.00, 70.00, 54.00)
                    ");
                    $stmtThresh->execute([$newPatientId]);

                    // 4. Log audit action
                    log_audit($pdo, $newUserId, 'patients', 'REGISTER', "New patient registered: {$form_data['full_name']}");

                    $pdo->commit();

                    set_flash('success', 'Registration successful! You can now sign in with your credentials.');
                    header('Location: ' . BASE_URL . 'login.php');
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3 rounded-top">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2"></i>Patient Self-Registration</h4>
                    <p class="small text-white-50 mb-0">Sir Albert Cook Hospital Diabetes Clinic Enrollment</p>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Full Legal Name *</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($form_data['full_name']) ?>" required placeholder="e.g. Alaba Rita Sophie">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Username *</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($form_data['username']) ?>" required placeholder="e.g. sophie_256">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($form_data['email']) ?>" required placeholder="sophie@example.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($form_data['phone']) ?>" placeholder="+256 701 234567">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" 
                                       value="<?= htmlspecialchars($form_data['date_of_birth']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="female" <?= $form_data['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="male" <?= $form_data['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="other" <?= $form_data['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Attending Clinician</label>
                                <select name="clinician_id" class="form-select">
                                    <option value="">-- Select Assigned Doctor --</option>
                                    <?php foreach ($clinicians as $doc): ?>
                                        <option value="<?= $doc['clinician_id'] ?>" <?= $form_data['clinician_id'] == $doc['clinician_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($doc['full_name']) ?> (<?= htmlspecialchars($doc['specialization']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password *</label>
                                <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required placeholder="Re-type password">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> Complete Patient Registration
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <span class="text-muted small">Already enrolled?</span> 
                        <a href="login.php" class="small fw-semibold">Sign In Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
