<?php

declare(strict_types=1);

$apiBaseUrl = getenv('API_BASE_URL');
$apiBaseUrl = is_string($apiBaseUrl) ? rtrim($apiBaseUrl, '/') : '';

$error = null;
$lists = [];

function is_localhost(string $baseUrl): bool
{
    $host = parse_url($baseUrl, PHP_URL_HOST);
    return $host === 'localhost' || $host === '127.0.0.1';
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * @return array{0: string|false, 1: int, 2: string}
 */
function api_request(string $url, string $baseUrl, string $method = 'GET', ?string $jsonBody = null): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => !is_localhost($baseUrl),
    ]);

    if ($jsonBody !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    unset($ch);

    return [$body, $status, $curlError];
}

if ($apiBaseUrl === '') {
    $error = 'API_BASE_URL is not set. Add it as an App Service application setting.';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? 'create_list');

        if ($action === 'add_item') {
            $listId = trim((string) ($_POST['list_id'] ?? ''));
            $itemTitle = trim((string) ($_POST['item_title'] ?? ''));
            if ($listId === '' || !preg_match('/^[0-9a-fA-F-]{36}$/', $listId)) {
                $error = 'Invalid list.';
            } elseif ($itemTitle === '') {
                $error = 'Item title is required.';
            } else {
                [$body, $status, $curlError] = api_request(
                    $apiBaseUrl . '/api/todolists/' . rawurlencode($listId) . '/items',
                    $apiBaseUrl,
                    'POST',
                    json_encode(['title' => $itemTitle], JSON_THROW_ON_ERROR)
                );

                if ($body === false || $curlError !== '') {
                    $error = 'Could not create item: ' . ($curlError !== '' ? $curlError : 'unknown error');
                } elseif ($status < 200 || $status >= 300) {
                    $error = 'TodoList API returned HTTP ' . $status . ' when creating an item.';
                } else {
                    header('Location: ' . strtok((string) $_SERVER['REQUEST_URI'], '?'));
                    exit;
                }
            }
        } else {
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                $error = 'Title is required.';
            } else {
                [$body, $status, $curlError] = api_request(
                    $apiBaseUrl . '/api/todolists',
                    $apiBaseUrl,
                    'POST',
                    json_encode(['title' => $title], JSON_THROW_ON_ERROR)
                );

                if ($body === false || $curlError !== '') {
                    $error = 'Could not create list: ' . ($curlError !== '' ? $curlError : 'unknown error');
                } elseif ($status < 200 || $status >= 300) {
                    $error = 'TodoList API returned HTTP ' . $status . ' when creating a list.';
                } else {
                    header('Location: ' . strtok((string) $_SERVER['REQUEST_URI'], '?'));
                    exit;
                }
            }
        }
    }

    [$body, $status, $curlError] = api_request($apiBaseUrl . '/api/todolists', $apiBaseUrl);

    if ($body === false || $curlError !== '') {
        $error ??= 'Could not reach the TodoList API: ' . ($curlError !== '' ? $curlError : 'unknown error');
    } elseif ($status < 200 || $status >= 300) {
        $error ??= 'TodoList API returned HTTP ' . $status . '.';
    } else {
        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            $error ??= 'TodoList API returned invalid JSON.';
        } else {
            $lists = $decoded;
        }
    }
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
        form { display: flex; gap: 0.5rem; margin: 1rem 0 1.5rem; }
        .list form { margin: 0.75rem 0 0; }
        input[type="text"] { flex: 1; padding: 0.5rem 0.6rem; }
        button { padding: 0.5rem 0.85rem; }
        .error { background: #fee2e2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 0.5rem; }
        .list { border: 1px solid #d4d4d4; border-radius: 0.5rem; padding: 1rem 1.25rem; margin: 1rem 0; }
        .list h2 { margin: 0 0 0.5rem; font-size: 1.1rem; }
        ul { margin: 0; padding-left: 1.25rem; }
        .done { text-decoration: line-through; opacity: 0.7; }
        .empty { color: #737373; }
    </style>
    <?php
    $aiConnectionString = getenv('APPLICATIONINSIGHTS_CONNECTION_STRING');
    $aiConnectionString = is_string($aiConnectionString) ? $aiConnectionString : '';
    ?>
    <?php if ($aiConnectionString !== ''): ?>
    <script src="https://js.monitor.azure.com/scripts/b/ai.2.min.js" crossorigin="anonymous"></script>
    <script>
        const appInsights = new Microsoft.ApplicationInsights.ApplicationInsights({
            config: {
                connectionString: <?= json_encode($aiConnectionString, JSON_THROW_ON_ERROR) ?>
            }
        });
        appInsights.loadAppInsights();
        appInsights.trackPageView();
    </script>
    <?php endif; ?>
</head>
<body>
    <h1>Todo lists</h1>
    <p>PHP 8 front end calling the ASP.NET Core API.</p>

    <?php if ($apiBaseUrl !== ''): ?>
        <form method="post">
            <input type="hidden" name="action" value="create_list">
            <input type="text" name="title" placeholder="New list title" required maxlength="200">
            <button type="submit">Create list</button>
        </form>
    <?php endif; ?>

    <?php if ($error !== null): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($apiBaseUrl !== '' && count($lists) === 0 && $error === null): ?>
        <p class="empty">No todo lists yet.</p>
    <?php endif; ?>

    <?php foreach ($lists as $list): ?>
            <?php
            $listId = is_array($list) ? (string) ($list['id'] ?? '') : '';
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
                <?php if ($listId !== ''): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" name="list_id" value="<?= h($listId) ?>">
                        <input type="text" name="item_title" placeholder="New item" required maxlength="200">
                        <button type="submit">Add item</button>
                    </form>
                <?php endif; ?>
            </section>
    <?php endforeach; ?>
</body>
</html>
