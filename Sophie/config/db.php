<?php
// =====================================================================
// Clinical Decision-Support & Self-Management System for Diabetes Mellitus
// Database Connection Configuration (PHP PDO)
// =====================================================================

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'diabetes_cdss');
define('DB_USER', 'root');
define('DB_PASS', '#Bwambale6');
define('DB_CHARSET', 'utf8mb4');

// Project base URL path (for XAMPP alias or localhost)
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = preg_replace('#/(patient|clinician|admin|config|includes|api)$#i', '', $scriptDir);
    define('BASE_URL', rtrim($base, '/') . '/');
}

/**
 * Get PDO Database Connection
 * @return PDO
 */
function get_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if ($e->getCode() == 1049) {
                die(render_db_error_page("Database <strong>" . DB_NAME . "</strong> does not exist yet. Please import <code>database/schema.sql</code> into MySQL Workbench."));
            } else {
                die(render_db_error_page("Database Connection Failed: " . htmlspecialchars($e->getMessage())));
            }
        }
    }

    return $pdo;
}

/**
 * Render user-friendly DB setup/error page
 */
function render_db_error_page($errorMessage) {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Setup Required - Diabetes CDSS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7">
                    <div class="card shadow border-danger">
                        <div class="card-header bg-danger text-white py-3">
                            <h5 class="mb-0 fw-bold">Database Setup Notice</h5>
                        </div>
                        <div class="card-body p-4">
                            <p class="fs-5 text-dark">' . $errorMessage . '</p>
                            <hr>
                            <h6 class="fw-bold">How to resolve in 3 steps:</h6>
                            <ol class="small text-muted">
                                <li>Open <strong>MySQL Workbench</strong>.</li>
                                <li>Open file: <code>' . htmlspecialchars(realpath(__DIR__ . '/../database/schema.sql')) . '</code></li>
                                <li>Execute the script to create <code>diabetes_cdss</code> tables and seed demo accounts.</li>
                            </ol>
                            <a href="" class="btn btn-outline-primary mt-2">Retry Connection</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
}
