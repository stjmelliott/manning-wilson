<?php
function st_samsara_get(string $url, string $api_key): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $api_key,
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT => 45
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'error' => $err];
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($code < 200 || $code >= 300) return ['ok' => false, 'http' => $code, 'resp' => $resp, 'data' => $data];
    return ['ok' => true, 'http' => $code, 'data' => $data];
}
