<?php
// =====================================================================
// Patient Portal Dashboard (Phase 1 Baseline)
// =====================================================================

$page_title = "Patient Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('patient');

$pdo = get_db_connection();
$user = current_user();

// Fetch patient detailed profile
$stmt = $pdo->prepare("
    SELECT p.*, c.full_name AS doctor_name, c.specialization AS doctor_specialization,
           t.target_fasting_min, t.target_fasting_max, t.target_postprandial_max, t.hypo_threshold
    FROM patients p
    LEFT JOIN clinicians c ON p.clinician_id = c.clinician_id
    LEFT JOIN patient_thresholds t ON p.patient_id = t.patient_id
    WHERE p.user_id = ?
");
$stmt->execute([$user['user_id']]);
$patient = $stmt->fetch();

$patient_id = $patient['patient_id'] ?? null;

// Fetch latest glucose reading
$latest_reading = null;
$reading_count = 0;
if ($patient_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM glucose_readings 
        WHERE patient_id = ? 
        ORDER BY logged_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$patient_id]);
    $latest_reading = $stmt->fetch();

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM glucose_readings WHERE patient_id = ?");
    $stmtCount->execute([$patient_id]);
    $reading_count = (int)$stmtCount->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-3">
    <!-- Welcome Banner -->
    <div class="card bg-white shadow-sm mb-4 border-start border-4 border-primary">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1">Welcome, <?= htmlspecialchars($patient['full_name'] ?? $user['username']) ?></h3>
                <p class="text-muted mb-0">
                    <i class="bi bi-hospital me-1"></i> Sir Albert Cook Hospital Diabetes Program
                    &bull; Doctor: <strong><?= htmlspecialchars($patient['doctor_name'] ?? 'Not Assigned') ?></strong>
                    <?= !empty($patient['doctor_specialization']) ? '(' . htmlspecialchars($patient['doctor_specialization']) . ')' : '' ?>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>patient/log_glucose.php" class="btn btn-primary fw-semibold shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Log Glucose Reading
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Most Recent Reading</div>
                    <?php if ($latest_reading): ?>
                        <div class="d-flex align-items-baseline">
                            <span class="display-6 fw-bold text-dark"><?= htmlspecialchars($latest_reading['glucose_value']) ?></span>
                            <span class="text-muted ms-2">mg/dL</span>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($latest_reading['logged_at'])) ?>
                            </span>
                            <span class="badge bg-primary text-capitalize ms-1">
                                <?= str_replace('_', ' ', $latest_reading['meal_context']) ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="text-muted py-2">
                            <i class="bi bi-info-circle me-1"></i> No readings logged yet.
                        </div>
                        <a href="<?= BASE_URL ?>patient/log_glucose.php" class="btn btn-outline-primary btn-sm mt-2">Log First Entry</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Total Logs Submitted</div>
                    <div class="display-6 fw-bold text-primary"><?= $reading_count ?></div>
                    <p class="text-muted small mt-2 mb-0">Total finger-stick logs recorded in hospital system</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Target Glycemic Bounds</div>
                    <div class="small mt-2">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Fasting Target:</span>
                            <span class="fw-bold"><?= $patient['target_fasting_min'] ?? 80 ?> - <?= $patient['target_fasting_max'] ?? 130 ?> mg/dL</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Post-Meal Target:</span>
                            <span class="fw-bold">&lt; <?= $patient['target_postprandial_max'] ?? 180 ?> mg/dL</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Hypo Alert Level:</span>
                            <span class="fw-bold text-danger">&lt; <?= $patient['hypo_threshold'] ?? 70 ?> mg/dL</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Details Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill text-primary me-2"></i>My Registered Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <span class="text-muted small d-block">Full Name</span>
                    <span class="fw-semibold"><?= htmlspecialchars($patient['full_name'] ?? 'N/A') ?></span>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="text-muted small d-block">Date of Birth</span>
                    <span class="fw-semibold"><?= htmlspecialchars($patient['date_of_birth'] ?? 'N/A') ?></span>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="text-muted small d-block">Gender</span>
                    <span class="fw-semibold text-capitalize"><?= htmlspecialchars($patient['gender'] ?? 'N/A') ?></span>
                </div>
                <div class="col-md-3 mb-2">
                    <span class="text-muted small d-block">Phone Number</span>
                    <span class="fw-semibold"><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
