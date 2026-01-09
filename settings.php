<?php
require_once __DIR__ . '/../credentials/flags.php';
require_once __DIR__ . '/../credentials/db.php';

require_once __DIR__ . '/../statuses_backend/lib/logger.php';
require_once __DIR__ . '/../statuses_backend/lib/clients.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if ($token !== ADMIN_TOKEN) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$clients = st_load_clients();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client = [
        'client_code' => strtoupper(trim($_POST['client_code'] ?? '')),
        'client_name' => trim($_POST['client_name'] ?? ''),
        'samsara_api_key' => trim($_POST['samsara_api_key'] ?? ''),
        'default_shipment_status' => trim($_POST['default_shipment_status'] ?? 'in_transit'),
        'email_subject_prefix' => trim($_POST['email_subject_prefix'] ?? 'Status Update'),
        'email_triggers' => array_values(array_filter(array_map('trim', explode(',', (string)($_POST['email_triggers'] ?? ''))))),
        'email_recipients' => []
    ];

    $emails = trim($_POST['email_recipients'] ?? '');
    if ($emails !== '') {
        foreach (explode(',', $emails) as $e) {
            $e = trim($e);
            if ($e === '') continue;
            $client['email_recipients'][] = ['email' => $e, 'name' => $e];
        }
    }

    if ($client['client_code'] === '') {
        $msg = 'client_code required';
    } else {
        $clients = st_upsert_client($clients, $client);
        st_save_clients($clients);
        $msg = 'saved';
        st_log('settings', 'client saved', ['client_code'=>$client['client_code']]);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Client Setup</title>
  <?php require_once __DIR__ . '/_assets.php'; ?>
</head>
<body class="container py-3">

<h2>Client Setup</h2>

<?php if ($msg): ?>
  <div class="alert alert-info py-2"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="post" class="card p-3 mb-4">
  <div class="row g-2">
    <div class="col-md-3">
      <label class="form-label">Client Code</label>
      <input name="client_code" class="form-control form-control-sm" required>
    </div>
    <div class="col-md-5">
      <label class="form-label">Client Name</label>
      <input name="client_name" class="form-control form-control-sm">
    </div>
    <div class="col-md-4">
      <label class="form-label">Default Shipment Status</label>
      <input name="default_shipment_status" class="form-control form-control-sm" value="in_transit">
    </div>
  </div>

  <div class="mt-3">
    <label class="form-label">Samsara API Key</label>
    <input name="samsara_api_key" class="form-control form-control-sm">
  </div>

  <div class="row g-2 mt-3">
    <div class="col-md-6">
      <label class="form-label">Email Recipients (comma-separated)</label>
      <input name="email_recipients" class="form-control form-control-sm">
    </div>
    <div class="col-md-6">
      <label class="form-label">Email Triggers (comma-separated statuses)</label>
      <input name="email_triggers" class="form-control form-control-sm" placeholder="arrived_pickup,departed_pickup,arrived_delivery">
    </div>
  </div>

  <div class="mt-3">
    <label class="form-label">Email Subject Prefix</label>
    <input name="email_subject_prefix" class="form-control form-control-sm" value="Status Update">
  </div>

  <div class="mt-3">
    <button class="btn btn-primary btn-sm">Save Client</button>
  </div>
</form>

<h5>Configured Clients</h5>
<table class="table table-sm table-bordered">
  <thead class="table-light">
    <tr>
      <th>Client</th>
      <th>Name</th>
      <th>Recipients</th>
      <th>Triggers</th>
      <th>Has API Key</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td><?= htmlspecialchars((string)($c['client_code'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($c['client_name'] ?? '')) ?></td>
        <td>
          <?php
            $r = $c['email_recipients'] ?? [];
            $emails = [];
            if (is_array($r)) { foreach ($r as $x) { if (!empty($x['email'])) $emails[] = $x['email']; } }
            echo htmlspecialchars(implode(', ', $emails));
          ?>
        </td>
        <td><?= htmlspecialchars(implode(', ', (array)($c['email_triggers'] ?? []))) ?></td>
        <td><?= (!empty($c['samsara_api_key'])) ? 'YES' : 'NO' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
