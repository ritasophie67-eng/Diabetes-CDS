<?php
// =====================================================================
// Global Layout Header Component
// =====================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'Clinical Decision-Support & Self-Management System';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Sir Albert Cook Hospital</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-med: #0d6efd;
            --med-dark: #0a4275;
            --danger-bg: #dc3545;
            --warning-bg: #ffc107;
            --success-med: #198754;
        }
        body {
            background-color: #f4f7f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
        .hospital-brand {
            font-weight: 700;
            letter-spacing: -0.2px;
        }
        .hospital-subtitle {
            font-size: 0.75rem;
            color: #d1e7dd;
            display: block;
            margin-top: -3px;
        }
        .navbar-med {
            background: linear-gradient(135deg, #0b3d68 0%, #175e9b 100%);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .badge-role {
            font-size: 0.72rem;
            padding: 0.35em 0.65em;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .footer-med {
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-med shadow-sm sticky-top">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>">
            <i class="bi bi-hospital fs-2 me-2 text-warning"></i>
            <div>
                <span class="hospital-brand">Diabetes CDSS</span>
                <span class="hospital-subtitle">Sir Albert Cook Hospital • Sentema Rd, Kampala</span>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <?php if (is_logged_in()): ?>
                    <?php if ($_SESSION['role'] === 'patient'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'patient/dashboard')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>patient/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i> My Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'patient/log_glucose')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>patient/log_glucose.php">
                                <i class="bi bi-plus-circle me-1"></i> Log Reading
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'patient/history')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>patient/history.php">
                                <i class="bi bi-graph-up me-1"></i> Glucose History
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] === 'clinician'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'clinician/dashboard')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>clinician/dashboard.php">
                                <i class="bi bi-clipboard2-pulse me-1"></i> Clinical Triage
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'clinician/patients')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>clinician/patients.php">
                                <i class="bi bi-people me-1"></i> My Patients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'clinician/alerts')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>clinician/alerts.php">
                                <i class="bi bi-bell me-1"></i> CDS Alerts Queue
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'admin/dashboard')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>admin/dashboard.php">
                                <i class="bi bi-shield-check me-1"></i> Administration
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'admin/manage_clinicians')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>admin/manage_clinicians.php">
                                <i class="bi bi-person-badge me-1"></i> Manage Doctors
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (str_contains($_SERVER['REQUEST_URI'], 'admin/audit_trail')) ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>admin/audit_trail.php">
                                <i class="bi bi-journal-text me-1"></i> Audit Trail
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5 me-2 text-info"></i>
                            <div>
                                <span class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></span>
                                <span class="badge bg-warning text-dark ms-1 badge-role"><?= htmlspecialchars($user['role']) ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header">Signed in as <strong><?= htmlspecialchars($user['username']) ?></strong></h6></li>
                            <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($user['email']) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?= BASE_URL ?>login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-warning btn-sm text-dark fw-bold px-3" href="<?= BASE_URL ?>register.php">Patient Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid px-lg-4 py-3">
    <?= display_flash() ?>
</div>

<main class="main-content">
