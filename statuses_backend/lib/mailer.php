<?php
// /statuses/backend/lib/mailer.php
require_once __DIR__ . '/../../credentials/mail.php';

function st_send_email_sendgrid(string $to_email, string $to_name, string $subject, string $html): array {
    $key = defined('SENDGRID_API_KEY') ? trim((string)SENDGRID_API_KEY) : '';
    if ($key === '' || $key === 'PUT_SENDGRID_API_KEY_HERE') {
        return ['ok' => false, 'error' => 'Missing SENDGRID_API_KEY'];
    }

    $payload = [
        "personalizations" => [[ "to" => [[ "email" => $to_email, "name" => $to_name ]] ]],
        "from" => [ "email" => MAIL_FROM_EMAIL, "name" => MAIL_FROM_NAME ],
        "subject" => $subject,
        "content" => [[ "type" => "text/html", "value" => $html ]]
    ];

    $ch = curl_init("https://api.sendgrid.com/v3/mail/send");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $key,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => $err, 'http' => $http, 'resp' => $resp];
    if ($http >= 200 && $http < 300) return ['ok' => true, 'http' => $http];
    return ['ok' => false, 'http' => $http, 'resp' => $resp];
}
