<?php
/* ============================================================
   STATUS ADMIN TOOLS – OPS CONTROL PANEL
   ============================================================ */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$BASE_DIR = __DIR__;
require_once $BASE_DIR . '/credentials/db.php';
require_once $BASE_DIR . '/credentials/apis.php';
require_once $BASE_DIR . '/statuses_backend/lib/logger.php';
require_once $BASE_DIR . '/statuses_backend/lib/db_helper.php';

$run = $_GET['run'] ?? null;
$logOutput = '';

if ($run) {
    ob_start();
    require $BASE_DIR . '/statuses_backend/ingest/samsara_ingest.php';
    $logOutput = ob_get_clean();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Status Admin Tools</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
pre {
    background:#111;
    color:#eee;
    padding:12px;
    max-height:450px;
    overflow:auto;
    font-size:12px;
}
</style>
</head>

<body class="bg-light">
<div class="container-fluid mt-4">

<h2>Status Admin Tools</h2>

<!-- INGEST -->
<div class="card mb-4">
<div class="card-header fw-bold">Data Ingestion</div>
<div class="card-body">

<a href="?run=current" class="btn btn-success me-2">Current (Live)</a>
<a href="?run=day" class="btn btn-outline-primary me-2">Last 24h</a>
<a href="?run=week" class="btn btn-outline-primary me-2">Last 7 Days</a>
<a href="?run=month" class="btn btn-outline-primary">Last 30 Days</a>

<hr>

<?php if ($logOutput): ?>
<pre><?= htmlspecialchars($logOutput) ?></pre>
<?php else: ?>
<div class="text-muted">Select an ingest range to run.</div>
<?php endif; ?>

</div>
</div>

<!-- LOGS -->
<div class="card mb-4">
<div class="card-header fw-bold">Logs</div>
<div class="card-body">
<a href="logs/" class="btn btn-outline-secondary">Open Logs Folder</a>
</div>
</div>

<!-- NAV -->
<div class="card">
<div class="card-header fw-bold">Navigation</div>
<div class="card-body">
<a href="dashboard.php" class="btn btn-secondary me-2">Dashboard</a>
<a href="settings.php" class="btn btn-secondary">Client Settings</a>
</div>
</div>

</div>
</body>
</html>
