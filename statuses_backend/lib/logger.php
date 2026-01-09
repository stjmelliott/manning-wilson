<?php
// /statuses/backend/lib/logger.php

function st_log(string $channel, string $message, array $context = []): void
{
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        return;
    }

    $file = $logDir . '/' . $channel . '.log';

    $ts = date('Y-m-d H:i:s');
    $line = '[' . $ts . '] ' . $message;

    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }

    $line .= PHP_EOL;

    @file_put_contents($file, $line, FILE_APPEND);
}
