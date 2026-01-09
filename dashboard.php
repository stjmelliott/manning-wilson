<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/credentials/flags.php';
require_once __DIR__ . '/credentials/db.php';
require_once __DIR__ . '/statuses_backend/lib/logger.php';
require_once __DIR__ . '/statuses_backend/lib/db_helper.php';

st_log('dashboard', 'load start');

$pdo = st_db();

$sql = "
SELECT
    client_code,
    client_name,
    load_id,
    shipment_status,
    pickup_city,
    pickup_state,
    delivery_city,
    delivery_state,
    last_event_ts
FROM samsara_ops_status
ORDER BY client_code, last_event_ts DESC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* group by client */
$clients = [];
foreach ($rows as $r) {
    $code = $r['client_code'] ?: 'UNKNOWN';
    $clients[$code][] = $r;
}

st_log('dashboard', 'rows fetched', ['clients' => count($clients)]);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Status Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid mt-3">

    <!-- Top Nav -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active">Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="reports.php">Reports</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="settings.php">Client Settings</a>
        </li>
    </ul>

    <!-- Client Tabs -->
    <ul class="nav nav-pills mb-3" role="tablist">
        <?php $i = 0; foreach ($clients as $clientCode => $_): ?>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link <?= $i === 0 ? 'active' : '' ?>"
                    data-bs-toggle="pill"
                    data-bs-target="#client-<?= htmlspecialchars($clientCode) ?>"
                    type="button"
                >
                    <?= htmlspecialchars($clientCode) ?>
                </button>
            </li>
        <?php $i++; endforeach; ?>
    </ul>

    <!-- Client Content -->
    <div class="tab-content">
        <?php $i = 0; foreach ($clients as $clientCode => $clientRows): ?>
            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                 id="client-<?= htmlspecialchars($clientCode) ?>">

                <div class="card mb-4">
                    <div class="card-header fw-bold">
                        Client: <?= htmlspecialchars($clientCode) ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Load</th>
                                    <th>Status</th>
                                    <th>Pickup</th>
                                    <th>Delivery</th>
                                    <th>Last Event</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientRows as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['load_id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($r['shipment_status'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(($r['pickup_city'] ?? '') . ' ' . ($r['pickup_state'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars(($r['delivery_city'] ?? '') . ' ' . ($r['delivery_state'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($r['last_event_ts'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        <?php $i++; endforeach; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php st_log('dashboard', 'render complete'); ?>
