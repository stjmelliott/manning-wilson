<?php
function st_send_email(string $to_email, string $to_name, string $subject, string $html): array {
    if (!defined('SENDGRID_API_KEY') || trim(SENDGRID_API_KEY) === '' || SENDGRID_API_KEY === 'PUT_SENDGRID_API_KEY_HERE') {
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
            "Authorization: Bearer " . SENDGRID_API_KEY,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => $err, 'http' => $code, 'resp' => $resp];
    if ($code >= 200 && $code < 300) return ['ok' => true, 'http' => $code];
    return ['ok' => false, 'http' => $code, 'resp' => $resp];
}
