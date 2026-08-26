<?php

declare(strict_types=1);

$apiBaseUrl = getenv('API_BASE_URL');
$apiBaseUrl = is_string($apiBaseUrl) ? rtrim($apiBaseUrl, '/') : '';

$error = null;
$lists = [];

if ($apiBaseUrl === '') {
    $error = 'API_BASE_URL is not set. Add it as an App Service application setting.';
} else {
    $url = $apiBaseUrl . '/api/todolists';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => !is_localhost($apiBaseUrl),
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false || $curlError !== '') {
        $error = 'Could not reach the TodoList API: ' . ($curlError !== '' ? $curlError : 'unknown error');
    } elseif ($status < 200 || $status >= 300) {
        $error = 'TodoList API returned HTTP ' . $status . '.';
    } else {
        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            $error = 'TodoList API returned invalid JSON.';
        } else {
            $lists = $decoded;
        }
    }
}

function is_localhost(string $baseUrl): bool
{
    $host = parse_url($baseUrl, PHP_URL_HOST);
    return $host === 'localhost' || $host === '127.0.0.1';
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Todo lists</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, sans-serif; margin: 2rem auto; max-width: 40rem; line-height: 1.4; }
        h1 { font-size: 1.5rem; }
        .error { background: #fee2e2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 0.5rem; }
        .list { border: 1px solid #d4d4d4; border-radius: 0.5rem; padding: 1rem 1.25rem; margin: 1rem 0; }
        .list h2 { margin: 0 0 0.5rem; font-size: 1.1rem; }
        ul { margin: 0; padding-left: 1.25rem; }
        .done { text-decoration: line-through; opacity: 0.7; }
        .empty { color: #737373; }
    </style>
</head>
<body>
    <h1>Todo lists</h1>
    <p>PHP 8 front end calling the ASP.NET Core API.</p>

    <?php if ($error !== null): ?>
        <p class="error"><?= h($error) ?></p>
    <?php elseif (count($lists) === 0): ?>
        <p class="empty">No todo lists yet. Create one with POST /api/todolists.</p>
    <?php else: ?>
        <?php foreach ($lists as $list): ?>
            <?php
            $title = is_array($list) ? (string) ($list['title'] ?? 'Untitled') : 'Untitled';
            $todos = is_array($list) && isset($list['todos']) && is_array($list['todos']) ? $list['todos'] : [];
            ?>
            <section class="list">
                <h2><?= h($title) ?></h2>
                <?php if (count($todos) === 0): ?>
                    <p class="empty">No items</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($todos as $todo): ?>
                            <?php
                            $itemTitle = is_array($todo) ? (string) ($todo['title'] ?? '') : '';
                            $done = is_array($todo) && !empty($todo['isCompleted']);
                            ?>
                            <li class="<?= $done ? 'done' : '' ?>"><?= h($itemTitle) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
