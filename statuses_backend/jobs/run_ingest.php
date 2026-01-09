<?php
echo "RUN_INGEST IDENTIFIER: FULL_DUMP_V1\n";

/*************************************************
 * SAMSARA INGEST – PURE FULL DATA DUMP
 *************************************************/

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../../credentials/flags.php';
require_once __DIR__ . '/../../credentials/db.php';
require_once __DIR__ . '/../../credentials/apis.php';
require_once __DIR__ . '/../lib/logger.php';
require_once __DIR__ . '/../lib/db_helper.php';

/* INPUT */
$range = $_GET['range'] ?? 'day';
if (!in_array($range, ['day','week','month'], true)) {
    die("INVALID RANGE\n");
}

/* WINDOW */
$since = match ($range) {
    'day'   => strtotime('-1 day'),
    'week'  => strtotime('-7 days'),
    'month' => strtotime('-30 days'),
};
$sinceMs = $since * 1000;

echo "INGEST MODE: FULL DATA DUMP\n";
echo "RANGE: {$range}\n";
echo "SINCE: " . date('Y-m-d H:i:s', $since) . "\n";

/* DB */
$pdo = st_db();

/* VEHICLES */
$ch = curl_init('https://api.samsara.com/fleet/vehicles');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . SAMSARA_API_KEY,
        'Content-Type: application/json'
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (!isset($data['data'])) {
    die("FAILED TO FETCH VEHICLES\n");
}

$vehicles = $data['data'];
echo "VEHICLES FOUND: " . count($vehicles) . "\n";

/* INSERT */
$sql = "
INSERT INTO samsara_ops_status (
    vehicle_id,
    vehicle_name,
    latitude,
    longitude,
    speed_mph,
    last_event_ts,
    last_sync_ts
) VALUES (
    :vehicle_id,
    :vehicle_name,
    :latitude,
    :longitude,
    :speed_mph,
    :last_event_ts,
    UTC_TIMESTAMP()
)";
$stmt = $pdo->prepare($sql);

$rows = 0;

foreach ($vehicles as $v) {
    $vid = $v['id'] ?? null;
    if (!$vid) continue;

    $ch = curl_init("https://api.samsara.com/fleet/vehicles/{$vid}/stats");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SAMSARA_API_KEY,
            'Content-Type: application/json'
        ]
    ]);
    $statsResp = curl_exec($ch);
    curl_close($ch);

    $stats = json_decode($statsResp, true);
    if (!isset($stats['data']['gps'])) continue;

    $gps = $stats['data']['gps'];
    if (($gps['timeMs'] ?? 0) < $sinceMs) continue;

    $stmt->execute([
        ':vehicle_id'   => $vid,
        ':vehicle_name' => $v['name'] ?? '',
        ':latitude'     => $gps['latitude'] ?? null,
        ':longitude'    => $gps['longitude'] ?? null,
        ':speed_mph'    => $stats['data']['speedMilesPerHour'] ?? null,
        ':last_event_ts'=> date('Y-m-d H:i:s', $gps['timeMs']/1000),
    ]);

    $rows++;
}

echo "ROWS INSERTED: {$rows}\n";
st_log('ingest', 'FULL DUMP COMPLETE', ['rows'=>$rows]);
