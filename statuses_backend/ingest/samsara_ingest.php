<?php
/* ============================================================
   SAMSARA INGEST – FULL FREIGHT DUMP (FAILSAFE)
   ============================================================ */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "SAMSARA INGEST START\n";

$run = $_GET['run'] ?? 'current';

switch ($run) {
    case 'current':
        $sinceTs = date('Y-m-d H:i:s', strtotime('-4 hours'));
        $label = 'CURRENT / LIVE';
        break;
    case 'day':
        $sinceTs = date('Y-m-d H:i:s', strtotime('-1 day'));
        $label = 'LAST 24 HOURS';
        break;
    case 'week':
        $sinceTs = date('Y-m-d H:i:s', strtotime('-7 days'));
        $label = 'LAST 7 DAYS';
        break;
    case 'month':
    default:
        $sinceTs = date('Y-m-d H:i:s', strtotime('-30 days'));
        $label = 'LAST 30 DAYS';
        break;
}

echo "MODE: {$label}\n";
echo "SINCE: {$sinceTs}\n";

$db = st_db();
if (!$db) {
    echo "FATAL: DB CONNECTION FAILED\n";
    return;
}
echo "DB CONNECTED\n";

if (!defined('SAMSARA_API_KEY') || empty(SAMSARA_API_KEY)) {
    echo "FATAL: SAMSARA API KEY MISSING\n";
    return;
}

$sinceMs = strtotime($sinceTs) * 1000;
$url = "https://api.samsara.com/fleet/vehicles";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . SAMSARA_API_KEY,
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    echo "CURL ERROR: " . curl_error($ch) . "\n";
    curl_close($ch);
    return;
}

curl_close($ch);
echo "HTTP STATUS: {$httpCode}\n";

$data = json_decode($response, true);
if (!isset($data['data'])) {
    echo "INVALID API RESPONSE\n";
    return;
}

$vehicles = $data['data'];
echo "VEHICLES RETURNED: " . count($vehicles) . "\n";

$inserted = 0;

$sql = "
INSERT INTO samsara_route_status
(vehicle_id, vehicle_name, last_sync_ts)
VALUES (:vehicle_id, :vehicle_name, NOW())
";

$stmt = $db->prepare($sql);

foreach ($vehicles as $v) {
    if (empty($v['id'])) {
        continue;
    }

    $stmt->execute([
        ':vehicle_id'   => $v['id'],
        ':vehicle_name' => $v['name'] ?? null
    ]);

    $inserted++;
}

echo "ROWS INSERTED: {$inserted}\n";
echo "INGEST COMPLETE\n";
