<?php
// /statuses/backend/mail/templates.php
// Manning Transfer – Arrive/Depart shipper email templates (HTML)

/**
 * Safe string
 */
function st_h($v): string {
    if ($v === null) return '';
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format timestamp like: 01/07/2026 21:05 pm
 */
function st_fmt_dt($dt): string {
    if (!$dt) return 'N/A';
    $ts = strtotime((string)$dt);
    if ($ts === false) return 'N/A';
    return date('m/d/Y H:i a', $ts);
}

/**
 * Build common header block exactly like your examples
 */
function st_manning_header(array $r, array $client): string {
    $po   = $r['po_number'] ?? $r['reference_number'] ?? $r['load_id'] ?? '';
    $po   = st_h($po);

    $contact_name  = st_h($client['client_name'] ?? 'Manning Transfer, Inc.');
    $contact_phone = st_h($client['contact_phone'] ?? '905-876-1416');
    $contact_email = st_h($client['contact_email'] ?? 'selliott@strongtco.com');

    $pickup_appt   = st_h($r['pickup_appt_ts'] ?? 'N/A');
    $delivery_appt = st_h($r['delivery_appt_ts'] ?? 'N/A');

    $customer_num  = st_h($r['customer_number'] ?? '');
    $reference_num = st_h($r['reference_number'] ?? ($r['load_id'] ?? ''));
    $bol_num       = st_h($r['bol_number'] ?? '');
    $pickup_num    = st_h($r['pickup_number'] ?? '');

    $top = "
      <div style=\"font-family:Arial, Helvetica, sans-serif; font-size:14px; color:#111;\">
        <h2 style=\"margin:0 0 10px 0; font-size:18px; font-weight:700;\">PO# {$po}</h2>

        <table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse; width:100%; max-width:700px;\">
          <tr>
            <td style=\"padding:2px 6px 2px 0; width:80px;\"><b>Contact:</b></td>
            <td style=\"padding:2px 0;\">{$contact_name}</td>
          </tr>
          <tr>
            <td style=\"padding:2px 6px 2px 0;\"><b>Phone:</b></td>
            <td style=\"padding:2px 0;\">{$contact_phone}</td>
          </tr>
          <tr>
            <td style=\"padding:2px 6px 2px 0;\"><b>Email:</b></td>
            <td style=\"padding:2px 0;\">{$contact_email}</td>
          </tr>
        </table>

        <div style=\"margin:10px 0 8px 0;\">
          <b>Pickup Appt:</b> {$pickup_appt}
          &nbsp;&nbsp;&nbsp;
          <b>Delivery Appt:</b> {$delivery_appt}
        </div>

        <div style=\"margin:0 0 12px 0;\">
          <b>Customer#:</b> {$customer_num}
          &nbsp;&nbsp;&nbsp;
          <b>Reference#:</b> {$reference_num}<br>
          <b>BOL#:</b> {$bol_num}
          &nbsp;&nbsp;&nbsp;
          <b>Pickup#:</b> {$pickup_num}
        </div>
    ";

    return $top;
}

/**
 * Build the pickup location table like your examples
 */
function st_pickup_table(array $r, string $status_label): string {
    $dt   = st_fmt_dt($r['last_event_ts'] ?? null);

    $name = $r['pickup_stop_name'] ?? $r['pickup_location'] ?? $r['origin_text'] ?? '';
    $city = $r['pickup_city'] ?? '';
    $state= $r['pickup_state'] ?? '';

    $name = st_h($name);
    $city = st_h($city);
    $state= st_h($state);

    $status_label = st_h($status_label);

    return "
      <h3 style=\"margin:0 0 8px 0; font-size:14px; font-weight:700;\">Pick-up Location</h3>
      <table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse; width:100%; max-width:700px; border:1px solid #bbb;\">
        <thead>
          <tr style=\"background:#f2f2f2;\">
            <th align=\"left\" style=\"padding:6px; border-bottom:1px solid #bbb;\">Date</th>
            <th align=\"left\" style=\"padding:6px; border-bottom:1px solid #bbb;\">Name</th>
            <th align=\"left\" style=\"padding:6px; border-bottom:1px solid #bbb;\">City</th>
            <th align=\"left\" style=\"padding:6px; border-bottom:1px solid #bbb;\">State</th>
            <th align=\"left\" style=\"padding:6px; border-bottom:1px solid #bbb;\">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style=\"padding:6px; border-top:1px solid #ddd;\">{$dt}</td>
            <td style=\"padding:6px; border-top:1px solid #ddd;\">{$name}</td>
            <td style=\"padding:6px; border-top:1px solid #ddd;\">{$city}</td>
            <td style=\"padding:6px; border-top:1px solid #ddd;\">{$state}</td>
            <td style=\"padding:6px; border-top:1px solid #ddd;\">{$status_label}</td>
          </tr>
        </tbody>
      </table>
      </div>
    ";
}

/**
 * Arrive Shipper Notification (HTML)
 */
function st_email_arrive_shipper(array $row, array $client): array {
    $subject = "Arrive Shipper Notification | PO# " . (($row['po_number'] ?? $row['reference_number'] ?? $row['load_id'] ?? ''));
    $html = st_manning_header($row, $client);
    $html .= st_pickup_table($row, 'Arrive Shipper');
    return ['subject' => $subject, 'html' => $html];
}

/**
 * Depart Shipper Notification (HTML)
 */
function st_email_depart_shipper(array $row, array $client): array {
    $subject = "Depart Shipper Notification | PO# " . (($row['po_number'] ?? $row['reference_number'] ?? $row['load_id'] ?? ''));
    $html = st_manning_header($row, $client);
    $html .= st_pickup_table($row, 'Picked');
    return ['subject' => $subject, 'html' => $html];
}
