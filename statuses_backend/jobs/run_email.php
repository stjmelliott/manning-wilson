<?php
// /statuses/backend/jobs/run_email.php
// Sends Arrive/Depart Shipper emails for Manning based on shipment_status transitions

require_once __DIR__ . '/../../credentials/flags.php';
require_once __DIR__ . '/../../credentials/db.php';
require_once __DIR__ . '/../../credentials/mail.php';

require_once __DIR__ . '/../lib/logger.php';
require_once __DIR__ . '/../lib/db_helper.php';
require_once __DIR__ . '/../lib/clients.php';
require_once __DIR__ . '/../lib/mailer.php';

require_once __DIR__ . '/../mail/templates.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = st_db();

$clients = st_load_clients();
if (!is_array($clients) || empty($clients)) {
    echo "NO CLIENTS CONFIGURED\n";
    exit;
}

foreach ($clients as $client) {
    $client_code = strtoupper(trim((string)($client['client_code'] ?? '')));
    if ($client_code === '') continue;

    $recips = $client['email_recipients'] ?? [];
    if (!is_array($recips) || empty($recips)) {
        st_log('email', 'skip: no recipients', ['client_code' => $client_code]);
        echo "EMAIL {$client_code}: SKIP (no recipients)\n";
        continue;
    }

    // Candidates: status changed since last notification (dedupe)
    $q = $pdo->prepare("
        SELECT *
        FROM samsara_ops_status
        WHERE client_code = :client_code
          AND shipment_status IS NOT NULL
          AND (last_notified_status IS NULL OR last_notified_status <> shipment_status)
        ORDER BY last_event_ts DESC
        LIMIT 500
    ");
    $q->execute([':client_code' => $client_code]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    echo "EMAIL {$client_code}: candidates=" . count($rows) . "\n";
    st_log('email', 'candidates', ['client_code' => $client_code, 'count' => count($rows)]);

    foreach ($rows as $r) {
        $status = strtolower(trim((string)($r['shipment_status'] ?? '')));
        if ($status === '') continue;

        // Map statuses to the two email types you provided
        $payload = null;

        // Your system can set shipment_status to these exact values.
        // "arrive_shipper" -> Arrive Shipper Notification
        // "depart_shipper" -> Depart Shipper Notification
        if ($status === 'arrive_shipper' || $status === 'arrive shipper' || $status === 'arrived_pickup' || $status === 'arrived pickup') {
            $payload = st_email_arrive_shipper($r, $client);
        } elseif ($status === 'depart_shipper' || $status === 'depart shipper' || $status === 'departed_pickup' || $status === 'departed pickup' || $status === 'picked') {
            $payload = st_email_depart_shipper($r, $client);
        } else {
            // Not a trigger we send for
            continue;
        }

        $all_ok = true;

        foreach ($recips as $to) {
            $to_email = trim((string)($to['email'] ?? ''));
            $to_name  = trim((string)($to['name'] ?? $to_email));
            if ($to_email === '') continue;

            $send = st_send_email_sendgrid($to_email, $to_name, $payload['subject'], $payload['html']);
            st_log('email', 'send', ['client_code'=>$client_code,'load_id'=>$r['load_id'] ?? '', 'to'=>$to_email, 'ok'=>$send['ok'] ?? false, 'http'=>$send['http'] ?? null]);

            if (empty($send['ok'])) $all_ok = false;
        }

        if ($all_ok) {
            $u = $pdo->prepare("
                UPDATE samsara_ops_status
                SET last_notified_status = shipment_status,
                    last_notified_ts = NOW()
                WHERE client_code = :client_code
                  AND load_id = :load_id
            ");
            $u->execute([
                ':client_code' => $client_code,
                ':load_id' => (string)($r['load_id'] ?? '')
            ]);
        }
    }
}

echo "DONE\n";
