<?php
// =====================================================================
// Hospital Administration - Clinician Credentialing & Provisioning
// =====================================================================

$page_title = "Clinician Provisioning & Management";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = get_db_connection();
$error = '';
$form_data = [
    'full_name'      => '',
    'specialization' => '',
    'license_no'     => '',
    'username'       => '',
    'email'          => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $form_data['full_name']      = trim($_POST['full_name'] ?? '');
        $form_data['specialization'] = trim($_POST['specialization'] ?? '');
        $form_data['license_no']     = trim($_POST['license_no'] ?? '');
        $form_data['username']       = trim($_POST['username'] ?? '');
        $form_data['email']          = trim($_POST['email'] ?? '');
        $password                    = $_POST['password'] ?? '';

        if (empty($form_data['full_name']) || empty($form_data['specialization']) || 
            empty($form_data['license_no']) || empty($form_data['username']) || 
            empty($form_data['email']) || empty($password)) {
            $error = 'All fields are mandatory for clinical credential verification.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid official email address.';
        } else {
            try {
                // Check if username, email, or license number already exists
                $chkUser = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
                $chkUser->execute([$form_data['username'], $form_data['email']]);
                if ($chkUser->fetch()) {
                    $error = 'Username or Email is already registered in the system.';
                } else {
                    $chkLic = $pdo->prepare("SELECT clinician_id FROM clinicians WHERE license_no = ?");
                    $chkLic->execute([$form_data['license_no']]);
                    if ($chkLic->fetch()) {
                        $error = 'A clinician with this UMDPC License Number is already registered.';
                    } else {
                        $pdo->beginTransaction();

                        // 1. Create User account
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmtU = $pdo->prepare("
                            INSERT INTO users (username, password_hash, role, email, created_at)
                            VALUES (?, ?, 'clinician', ?, NOW())
                        ");
                        $stmtU->execute([$form_data['username'], $hash, $form_data['email']]);
                        $newUserId = (int)$pdo->lastInsertId();

                        // 2. Create Clinician profile
                        $stmtC = $pdo->prepare("
                            INSERT INTO clinicians (user_id, full_name, specialization, license_no)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmtC->execute([
                            $newUserId,
                            $form_data['full_name'],
                            $form_data['specialization'],
                            $form_data['license_no']
                        ]);

                        // 3. Log Audit Trail
                        $adminId = $_SESSION['user_id'] ?? null;
                        log_audit($pdo, $adminId, 'clinicians', 'PROVISION', 
                            "Administrator provisioned verified clinician: {$form_data['full_name']} (License: {$form_data['license_no']}, Spec: {$form_data['specialization']})"
                        );

                        $pdo->commit();

                        set_flash('success', "Clinician <strong>" . htmlspecialchars($form_data['full_name']) . "</strong> successfully credentialed and activated.");
                        header('Location: ' . BASE_URL . 'admin/manage_clinicians.php');
                        exit;
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Failed to provision clinician: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all registered clinicians with patient counts
$stmt = $pdo->query("
    SELECT c.*, u.username, u.email, u.created_at AS account_created_at,
           (SELECT COUNT(*) FROM patients WHERE clinician_id = c.clinician_id) AS patient_count
    FROM clinicians c
    JOIN users u ON c.user_id = u.user_id
    ORDER BY c.full_name ASC
");
$clinicians = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-3">
    <!-- Breadcrumb / Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i>Clinician Governance & Provisioning</h3>
            <p class="text-muted mb-0">Authorized hospital administrator controls for credentialing medical personnel</p>
        </div>
        <button class="btn btn-primary fw-semibold" data-bs-toggle="collapse" data-bs-target="#provisionCollapse">
            <i class="bi bi-plus-lg me-1"></i> Provision New Clinician
        </button>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Provisioning Form (Collapsible or Open if error) -->
    <div class="collapse <?= !empty($error) ? 'show' : '' ?> mb-4" id="provisionCollapse">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-shield-plus text-primary me-2"></i>Medical Practitioner Credential Verification Form</h5>
                <small class="text-muted">Ensure physician medical license (UMDPC) is verified before issuing system credentials.</small>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="manage_clinicians.php">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Doctor Full Legal Name *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($form_data['full_name']) ?>" required placeholder="e.g. Dr. Jane Nakamya">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Medical License No. (UMDPC) *</label>
                            <input type="text" name="license_no" class="form-control" 
                                   value="<?= htmlspecialchars($form_data['license_no']) ?>" required placeholder="e.g. UMDPC-2024-7819">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Medical Specialization *</label>
                            <select name="specialization" class="form-select" required>
                                <option value="">-- Select Specialization --</option>
                                <option value="Endocrinology & Diabetology" <?= $form_data['specialization'] === 'Endocrinology & Diabetology' ? 'selected' : '' ?>>Endocrinology & Diabetology</option>
                                <option value="Internal Medicine" <?= $form_data['specialization'] === 'Internal Medicine' ? 'selected' : '' ?>>Internal Medicine</option>
                                <option value="Family Medicine / General Practice" <?= $form_data['specialization'] === 'Family Medicine / General Practice' ? 'selected' : '' ?>>Family Medicine / General Practice</option>
                                <option value="Diabetic Foot & Wound Care" <?= $form_data['specialization'] === 'Diabetic Foot & Wound Care' ? 'selected' : '' ?>>Diabetic Foot & Wound Care</option>
                                <option value="Clinical Diabetology Nurse Specialist" <?= $form_data['specialization'] === 'Clinical Diabetology Nurse Specialist' ? 'selected' : '' ?>>Clinical Diabetology Nurse Specialist</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Hospital Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($form_data['email']) ?>" required placeholder="doctor@sir-albert-cook.org">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Portal Username *</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($form_data['username']) ?>" required placeholder="e.g. dr_nakamya">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Initial Password *</label>
                            <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-light me-2" data-bs-toggle="collapse" data-bs-target="#provisionCollapse">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> Verify & Activate Clinician Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Active Clinicians Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-hospital text-primary me-2"></i>Verified Medical Staff Roster (<?= count($clinicians) ?> Doctors)</h5>
            <span class="badge bg-success bg-opacity-75">UMDPC Regulated</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Doctor Name</th>
                            <th>Specialization</th>
                            <th>UMDPC License</th>
                            <th>Username / Email</th>
                            <th>Assigned Patients</th>
                            <th>Enrolled Date</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clinicians)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No clinicians registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clinicians as $doc): ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <i class="bi bi-person-circle text-primary me-1"></i>
                                        <?= htmlspecialchars($doc['full_name']) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($doc['specialization']) ?></span>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($doc['license_no']) ?></code>
                                    </td>
                                    <td>
                                        <div><strong><?= htmlspecialchars($doc['username']) ?></strong></div>
                                        <small class="text-muted"><?= htmlspecialchars($doc['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill"><?= $doc['patient_count'] ?> Patients</span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('d M Y', strtotime($doc['account_created_at'])) ?></small>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i> Verified</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
