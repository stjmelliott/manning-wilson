<?php
require_once __DIR__ . '/../credentials/flags.php';
require_once __DIR__ . '/../credentials/db.php';

require_once __DIR__ . '/../statuses_backend/lib/db.php';
require_once __DIR__ . '/../statuses_backend/lib/logger.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$range = isset($_GET['range']) ? trim($_GET['range']) : 'day'; // day|week|month
$days  = ($range === 'week') ? 7 : (($range === 'month') ? 30 : 1);

$pdo = st_db();

$bucket = ($range === 'week')
    ? "DATE_FORMAT(last_event_ts, '%x-W%v')"
    : (($range === 'month')
        ? "DATE_FORMAT(last_event_ts, '%Y-%m')"
        : "DATE(last_event_ts)");

$sql = "
SELECT
  client_code,
  {$bucket} AS bucket,
  COUNT(*) AS row_count
FROM samsara_ops_status
WHERE last_event_ts >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
GROUP BY client_code, bucket
ORDER BY bucket DESC, client_code ASC
";

$rows = $pdo->query($sql)->fetchAll();
st_log('reports', 'reports view', ['range'=>$range,'rows'=>count($rows)]);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Status Reports</title>
  <?php require_once __DIR__ . '/_assets.php'; ?>
</head>
<body class="container-fluid py-3">

<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="m-0">Reports</h2>
  <form class="d-flex gap-2" method="get" action="reports.php">
    <select class="form-select form-select-sm" name="range" style="width:160px">
      <option value="day"   <?= $range==='day'?'selected':'' ?>>Daily</option>
      <option value="week"  <?= $range==='week'?'selected':'' ?>>Weekly</option>
      <option value="month" <?= $range==='month'?'selected':'' ?>>Monthly</option>
    </select>
    <button class="btn btn-sm btn-primary">Run</button>
  </form>
</div>

<table class="table table-sm table-bordered">
  <thead class="table-light">
    <tr><th>Bucket</th><th>Client</th><th>Rows</th></tr>
  </thead>
  <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="3" class="text-center text-muted">No rows</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars((string)$r['bucket']) ?></td>
        <td><?= htmlspecialchars((string)$r['client_code']) ?></td>
        <td><?= htmlspecialchars((string)$r['row_count']) ?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>

</body>
</html>
