<?php
function st_clients_file(): string {
    $dir = __DIR__ . '/../config';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    return $dir . '/clients.json';
}
function st_load_clients(): array {
    $file = st_clients_file();
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}
function st_save_clients(array $clients): void {
    $file = st_clients_file();
    file_put_contents($file, json_encode($clients, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL);
}
function st_upsert_client(array $clients, array $client): array {
    $code = $client['client_code'] ?? '';
    $out = [];
    $found = false;
    foreach ($clients as $c) {
        if (($c['client_code'] ?? '') === $code) { $out[] = $client; $found = true; }
        else { $out[] = $c; }
    }
    if (!$found) $out[] = $client;
    return $out;
}
