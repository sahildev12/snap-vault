<?php
/**
 * Production-friendly error page
 * Expects: $httpCode int, $title string, $message string, $appName string, $homeUrl string
 */
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (int) $httpCode ?> · <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg: #f4f5f7;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --accent: #0066cc;
            --border: rgba(17, 24, 39, .08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 24px;
        }
        .card {
            width: min(520px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(17, 24, 39, .06);
        }
        .code {
            display: inline-block;
            font-size: 13px;
            font-weight: 700;
            color: var(--accent);
            background: rgba(0, 102, 204, .08);
            padding: 4px 10px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 1.5rem;
        }
        p {
            margin: 0 0 20px;
            color: var(--muted);
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            background: var(--accent);
            color: #fff;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 600;
        }
        .hint {
            margin-top: 16px;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="code"><?= (int) $httpCode ?></div>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn">Go to homepage</a>
        <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
            <div class="hint">APP_DEBUG is enabled — detailed errors are shown when exceptions occur.</div>
        <?php endif; ?>
    </div>
</body>
</html>
