<?php
// =====================================================================
// Clinician Triage Portal Dashboard (Phase 1 Baseline)
// =====================================================================

$page_title = "Clinical Triage Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('clinician');

$pdo = get_db_connection();
$user = current_user();

// Fetch clinician details
$stmt = $pdo->prepare("SELECT * FROM clinicians WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$clinician = $stmt->fetch();
$clinician_id = $clinician['clinician_id'] ?? null;

// Summary metrics
$total_patients = 0;
$pending_alerts = 0;
$critical_alerts = 0;
$patients_list = [];

if ($clinician_id) {
    // Total assigned patients
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE clinician_id = ?");
    $stmt->execute([$clinician_id]);
    $total_patients = (int)$stmt->fetchColumn();

    // Critical and pending alerts
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_total,
            COUNT(CASE WHEN status = 'pending' AND risk_level = 'critical' THEN 1 END) AS critical_total
        FROM clinical_alerts
        WHERE clinician_id = ? OR patient_id IN (SELECT patient_id FROM patients WHERE clinician_id = ?)
    ");
    $stmt->execute([$clinician_id, $clinician_id]);
    $alert_counts = $stmt->fetch();
    $pending_alerts = (int)($alert_counts['pending_total'] ?? 0);
    $critical_alerts = (int)($alert_counts['critical_total'] ?? 0);

    // Patients list with their latest reading
    $stmt = $pdo->prepare("
        SELECT p.patient_id, p.full_name, p.gender, p.phone, p.date_of_birth,
               (SELECT glucose_value FROM glucose_readings WHERE patient_id = p.patient_id ORDER BY logged_at DESC LIMIT 1) AS last_glucose,
               (SELECT logged_at FROM glucose_readings WHERE patient_id = p.patient_id ORDER BY logged_at DESC LIMIT 1) AS last_logged_at,
               (SELECT COUNT(*) FROM clinical_alerts WHERE patient_id = p.patient_id AND status = 'pending') AS active_alerts
        FROM patients p
        WHERE p.clinician_id = ?
        ORDER BY active_alerts DESC, p.full_name ASC
    ");
    $stmt->execute([$clinician_id]);
    $patients_list = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-3">
    <!-- Doctor Header Banner -->
    <div class="card bg-white shadow-sm mb-4 border-start border-4 border-primary">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($clinician['full_name'] ?? 'Clinician Portal') ?></h3>
                <p class="text-muted mb-0">
                    <span class="badge bg-primary text-uppercase me-2"><?= htmlspecialchars($clinician['specialization'] ?? 'Internal Medicine') ?></span>
                    <span>License No: <code><?= htmlspecialchars($clinician['license_no'] ?? 'N/A') ?></code></span>
                    &bull; Sir Albert Cook Hospital
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="badge bg-light text-dark border p-2">
                    <i class="bi bi-clock-history me-1"></i> Triage Session Active
                </span>
            </div>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded me-3">
                        <i class="bi bi-people-fill fs-2"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Assigned Patients</div>
                        <div class="display-6 fw-bold"><?= $total_patients ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded me-3">
                        <i class="bi bi-exclamation-octagon-fill fs-2"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Critical Risk Alerts</div>
                        <div class="display-6 fw-bold text-danger"><?= $critical_alerts ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded me-3">
                        <i class="bi bi-bell-fill fs-2 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Pending CDS Triage</div>
                        <div class="display-6 fw-bold text-dark"><?= $pending_alerts ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Triage Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill text-primary me-2"></i>Monitored Patients Clinical Roster</h5>
            <span class="badge bg-secondary"><?= count($patients_list) ?> Records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Patient Name</th>
                            <th>Gender / Age</th>
                            <th>Phone Contact</th>
                            <th>Latest Blood Glucose</th>
                            <th>Last Monitored</th>
                            <th>Active Alerts</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients_list)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                    No patients currently assigned to your roster.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($patients_list as $pat): 
                                $dob = new DateTime($pat['date_of_birth']);
                                $now = new DateTime();
                                $age = $now->diff($dob)->y;
                            ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <?= htmlspecialchars($pat['full_name']) ?>
                                    </td>
                                    <td>
                                        <span class="text-capitalize"><?= htmlspecialchars($pat['gender']) ?></span>, <?= $age ?> yrs
                                    </td>
                                    <td><?= htmlspecialchars($pat['phone']) ?></td>
                                    <td>
                                        <?php if ($pat['last_glucose'] !== null): ?>
                                            <span class="fw-bold <?= ($pat['last_glucose'] < 70 || $pat['last_glucose'] > 180) ? 'text-danger' : 'text-success' ?>">
                                                <?= htmlspecialchars($pat['last_glucose']) ?> mg/dL
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">No logs yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $pat['last_logged_at'] ? date('d M, h:i A', strtotime($pat['last_logged_at'])) : '<span class="text-muted small">None</span>' ?>
                                    </td>
                                    <td>
                                        <?php if ($pat['active_alerts'] > 0): ?>
                                            <span class="badge bg-danger rounded-pill"><?= $pat['active_alerts'] ?> Alerts</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-75 rounded-pill">Stable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-outline-primary btn-sm" disabled title="Deep-dive triage available in Phase 3">
                                            <i class="bi bi-eye me-1"></i> Triage
                                        </button>
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
