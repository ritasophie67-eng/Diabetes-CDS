<?php
// =====================================================================
// System Administration - Full Audit Trail View
// =====================================================================

$page_title = "Security Audit Trail";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pdo = get_db_connection();

// Filter parameters
$action_filter = trim($_GET['action'] ?? '');
$table_filter  = trim($_GET['table'] ?? '');

$sql = "
    SELECT a.*, u.username, u.role
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.user_id
    WHERE 1=1
";
$params = [];

if (!empty($action_filter)) {
    $sql .= " AND a.action = ?";
    $params[] = $action_filter;
}
if (!empty($table_filter)) {
    $sql .= " AND a.target_table = ?";
    $params[] = $table_filter;
}

$sql .= " ORDER BY a.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions and tables for filter dropdowns
$actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
$tables  = $pdo->query("SELECT DISTINCT target_table FROM audit_logs ORDER BY target_table ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-journal-text text-primary me-2"></i>Security Audit Trail</h3>
            <p class="text-muted mb-0">Immutable, timestamped event log for regulatory compliance and access monitoring</p>
        </div>
        <span class="badge bg-dark py-2 px-3">
            <i class="bi bi-shield-lock-fill me-1"></i> Audit Records: <?= count($logs) ?>
        </span>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="audit_trail.php" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="action" class="form-select">
                        <option value="">-- All Actions --</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= $action_filter === $act ? 'selected' : '' ?>>
                                Action: <?= htmlspecialchars($act) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="table" class="form-select">
                        <option value="">-- All Target Tables --</option>
                        <?php foreach ($tables as $tbl): ?>
                            <option value="<?= htmlspecialchars($tbl) ?>" <?= $table_filter === $tbl ? 'selected' : '' ?>>
                                Table: <?= htmlspecialchars($tbl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> Filter Logs
                    </button>
                    <a href="audit_trail.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Timestamp</th>
                            <th>Operator (Role)</th>
                            <th>Target Table</th>
                            <th>Action Performed</th>
                            <th class="pe-3">Details / Audit Payload</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No matching audit logs found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
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
