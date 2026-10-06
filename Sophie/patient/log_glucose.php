<?php
$page_title = "Log Glucose Reading";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('patient');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 text-center">
            <i class="bi bi-clock-history fs-1 text-primary mb-3 d-inline-block"></i>
            <h4 class="fw-bold">Patient Glucose Logging Module</h4>
            <p class="text-muted">This module with real-time multi-stage range validation and extreme value modal confirmation is scheduled for <strong>Phase 2</strong>.</p>
            <a href="<?= BASE_URL ?>patient/dashboard.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i> Return to Dashboard</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
