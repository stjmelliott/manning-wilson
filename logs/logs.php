<?php
// /statuses/logs/logs.php
// Dynamic Log Viewer - Minnesota Vikings Theme (Light)

declare(strict_types=1);

const LOG_DIR = __DIR__;

// Discover all .log files
$logFiles = glob(LOG_DIR . '/*.log');
$logs = [];

foreach ($logFiles as $file) {
    $filename = basename($file);
    $key = strtolower(str_replace('.log', '', $filename));
    $displayName = ucwords(str_replace('_', ' ', $key));
    
    $logs[$key] = [
        'file'    => $filename,
        'display' => $displayName,
        'path'    => $file
    ];
}

// Add Show All
$logs['all'] = [
    'file'    => 'All Logs',
    'display' => 'Show All',
    'path'    => null
];

// Handle clear
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['clear'])) {
    $key = trim($_POST['clear']);
    if (isset($logs[$key]) && $key !== 'all') {
        $path = $logs[$key]['path'];
        if (is_file($path) && is_writable($path)) {
            if (file_put_contents($path, '') !== false) {
                $message = '<div class="alert alert-success">Log cleared successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to clear log (write error).</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Log not writable or missing.</div>';
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

function readLog(string $path): string {
    if (!is_file($path) || !is_readable($path)) return "File not found";
    $content = @file_get_contents($path);
    return $content ?: "Log is empty";
}

function highlight(string $text): string {
    return str_replace(
        ['ERROR', 'EXCEPTION', 'WARN', 'WARNING', 'INFO', 'SUCCESS'],
        [
            '<span class="text-danger fw-bold">ERROR</span>',
            '<span class="text-danger fw-bold">EXCEPTION</span>',
            '<span class="text-warning fw-bold">WARN</span>',
            '<span class="text-warning fw-bold">WARNING</span>',
            '<span class="text-info">INFO</span>',
            '<span class="vikings-gold fw-bold">SUCCESS</span>'
        ],
        htmlspecialchars($text)
    );
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vikings Dev Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --vikings-purple: #4f2683;
            --vikings-gold: #ffc62f;
            --vikings-light: #f5f0ff;
        }
        body {
            background: linear-gradient(to bottom right, #f8f5ff, #f0e9ff);
            color: #2d1b4e;
            min-height: 100vh;
        }
        .container-fluid { max-width: 1400px; }
        .card {
            border: 3px solid var(--vikings-purple);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(79,38,131,0.12);
            overflow: hidden;
        }
        .card-header {
            background: var(--vikings-purple);
            color: white;
            border-bottom: 4px solid var(--vikings-gold);
        }
        .log-container {
            background: #ffffff;
            font-family: 'Consolas', monospace;
            font-size: 0.96rem;
            line-height: 1.6;
            padding: 1.75rem 2rem;
            max-height: 75vh;
            overflow-y: auto;
        }
        .nav-tabs {
            border-bottom: 3px solid var(--vikings-gold);
        }
        .nav-tabs .nav-link {
            background: #f0e6ff;
            border: 1px solid var(--vikings-purple);
            border-bottom: none;
            border-radius: 12px 12px 0 0;
            color: var(--vikings-purple);
            margin-right: 6px;
        }
        .nav-tabs .nav-link.active {
            background: white;
            color: var(--vikings-purple);
            border-bottom: 3px solid var(--vikings-gold);
            font-weight: 700;
        }
        .btn-clear {
            background: var(--vikings-gold);
            color: var(--vikings-purple);
            border: 2px solid var(--vikings-purple);
            border-radius: 30px;
            padding: 0.45rem 1.2rem;
            font-weight: 600;
        }
        .btn-clear:hover {
            background: #ffdb5c;
        }
        .vikings-gold { color: var(--vikings-gold); }
        .log-source {
            background: var(--vikings-purple);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        h2 { color: var(--vikings-purple); }
    </style>
</head>
<body>

<div class="container-fluid py-5">
    <div class="text-center mb-5">
        <h2 class="display-5 fw-bold">Minnesota Vikings Dev Logs</h2>
        <small class="text-muted">Refreshed <?= date('H:i:s') ?></small>
    </div>

    <?= $message ?? '' ?>

    <ul class="nav nav-tabs mb-4 justify-content-center" id="logTabs" role="tablist">
        <?php $first = true; foreach ($logs as $key => $item): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $first ? 'active' : '' ?>" 
                        id="<?= $key ?>-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-<?= $key ?>"
                        type="button">
                    <?= $item['display'] ?>
                </button>
            </li>
            <?php $first = false; ?>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content">
        <?php $first = true; foreach ($logs as $key => $item): ?>
            <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= $key ?>">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center py-3 px-4">
                        <div>
                            <i class="fas fa-file-lines me-2"></i>
                            <?= htmlspecialchars($item['file']) ?>
                        </div>
                        <form method="post" class="m-0">
                            <input type="hidden" name="clear" value="<?= $key ?>">
                            <button type="submit" class="btn btn-clear"
                                    onclick="return confirm('Clear this log?')">
                                Clear
                            </button>
                        </form>
                    </div>

                    <?php if ($key === 'all'): ?>
                        <div class="log-container">
                            <?php foreach ($logs as $logKey => $logItem): 
                                if ($logKey === 'all') continue;
                                $content = readLog($logItem['path']);
                                if (trim($content) === '') continue;
                            ?>
                                <div class="log-source"><?= htmlspecialchars($logItem['display']) ?></div>
                                <div class="mb-4"><?= nl2br(highlight($content)) ?></div>
                                <hr class="my-4 opacity-25">
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="log-container">
                            <?= nl2br(highlight(readLog($item['path']))) 
                                ?: '<div class="text-center text-muted py-5">— empty log —</div>' ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php $first = false; ?>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>