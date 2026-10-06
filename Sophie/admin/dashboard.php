<?php
// =====================================================================
// System Administration & Governance Dashboard (Phase 1 Baseline)
// =====================================================================

$page_title = "Administration Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = get_db_connection();

// Aggregated counts
$users_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$clinicians_count = (int)$pdo->query("SELECT COUNT(*) FROM clinicians")->fetchColumn();
$patients_count = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$readings_count = (int)$pdo->query("SELECT COUNT(*) FROM glucose_readings")->fetchColumn();

// Recent audit logs
$stmtLogs = $pdo->query("
    SELECT a.*, u.username, u.role
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.user_id
    ORDER BY a.created_at DESC
    LIMIT 15
");
$recent_logs = $stmtLogs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-3">
    <!-- Admin Header Banner -->
    <div class="card bg-white shadow-sm mb-4 border-start border-4 border-dark">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Hospital Administration & Governance</h3>
                <p class="text-muted mb-0">Sir Albert Cook Hospital CDSS &bull; Role-Based Security & Audit Monitoring</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/manage_clinicians.php" class="btn btn-primary fw-semibold shadow-sm">
                    <i class="bi bi-person-plus-fill me-1"></i> Provision Clinician
                </a>
                <span class="badge bg-success py-2 px-3 align-self-center">System Online</span>
            </div>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Total Accounts</div>
                    <div class="display-6 fw-bold"><?= $users_count ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Active Clinicians</div>
                    <div class="display-6 fw-bold text-primary"><?= $clinicians_count ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Registered Patients</div>
                    <div class="display-6 fw-bold text-success"><?= $patients_count ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Glucose Logs Total</div>
                    <div class="display-6 fw-bold text-info"><?= $readings_count ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Trail Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text text-primary me-2"></i>Security Audit Trail (Immutable System Logs)</h5>
            <span class="badge bg-light text-dark border">Recent 15 Events</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Log ID</th>
                            <th>Timestamp</th>
                            <th>User (Role)</th>
                            <th>Target Table</th>
                            <th>Action</th>
                            <th class="pe-3">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_logs)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No audit activity recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td class="ps-3"><code>#<?= $log['log_id'] ?></code></td>
                                    <td><?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <?php if ($log['username']): ?>
                                            <strong><?= htmlspecialchars($log['username']) ?></strong>
                                            <span class="badge bg-secondary badge-role ms-1"><?= htmlspecialchars($log['role']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['target_table']) ?></code></td>
                                    <td>
                                        <span class="badge bg-info text-dark fw-bold"><?= htmlspecialchars($log['action']) ?></span>
                                    </td>
                                    <td class="pe-3 small text-muted"><?= htmlspecialchars($log['details']) ?></td>
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
