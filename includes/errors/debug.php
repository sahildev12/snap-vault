<?php
/**
 * Detailed debug error page (APP_DEBUG)
 * Expects: $exception Throwable, $statusCode int, $frames array, $snippet string
 */
declare(strict_types=1);

$appName = defined('APP_NAME') ? APP_NAME : 'Application';
$appEnv = defined('APP_ENV') ? APP_ENV : 'unknown';
$method = htmlspecialchars((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), ENT_QUOTES, 'UTF-8');
$uri = htmlspecialchars((string) ($_SERVER['REQUEST_URI'] ?? '/'), ENT_QUOTES, 'UTF-8');
$exceptionClass = htmlspecialchars($exception::class, ENT_QUOTES, 'UTF-8');
$exceptionMessage = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
$exceptionFile = htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8');
$exceptionLine = (int) $exception->getLine();
$previous = $exception->getPrevious();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (int) $statusCode ?> · <?= htmlspecialchars($exceptionClass, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg: #111827;
            --panel: #1f2937;
            --panel-2: #374151;
            --text: #f9fafb;
            --muted: #9ca3af;
            --accent: #f87171;
            --code-bg: #0b1220;
            --line: rgba(255,255,255,.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px 16px 48px; }
        .badge {
            display: inline-block;
            background: var(--accent);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            padding: 4px 10px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: clamp(1.4rem, 2.5vw, 2rem);
            word-break: break-word;
        }
        .meta {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 20px;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .card-head {
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .card-body { padding: 16px; }
        .exception-type {
            color: #fca5a5;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .file-link {
            color: #93c5fd;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 13px;
            word-break: break-all;
        }
        .snippet {
            background: var(--code-bg);
            border-radius: 8px;
            overflow: auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
        }
        .err-line {
            display: flex;
            gap: 12px;
            padding: 2px 12px;
            white-space: pre;
        }
        .err-line.is-target {
            background: rgba(248, 113, 113, .18);
            border-left: 3px solid var(--accent);
        }
        .err-line-no {
            width: 36px;
            text-align: right;
            color: var(--muted);
            user-select: none;
            flex-shrink: 0;
        }
        .err-line-code { color: #e5e7eb; }
        .frames { list-style: none; margin: 0; padding: 0; }
        .frames li {
            border-bottom: 1px solid var(--line);
            padding: 12px 16px;
            font-size: 13px;
        }
        .frames li:last-child { border-bottom: 0; }
        .frame-no { color: var(--muted); margin-right: 8px; }
        .frame-call {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            color: #e5e7eb;
            word-break: break-word;
        }
        .frame-file {
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
            word-break: break-all;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }
        .kv {
            background: rgba(255,255,255,.03);
            border-radius: 8px;
            padding: 10px 12px;
        }
        .kv dt {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .kv dd {
            margin: 0;
            font-size: 13px;
            word-break: break-word;
        }
    </style>
</head>
<body>
<div class="wrap">
    <span class="badge"><?= (int) $statusCode ?> <?= htmlspecialchars($httpTitle ?? 'Error', ENT_QUOTES, 'UTF-8') ?></span>
    <h1><?= $exceptionMessage !== '' ? $exceptionMessage : $exceptionClass ?></h1>
    <div class="meta"><?= $exceptionClass ?> · <?= $appName ?> (<?= htmlspecialchars($appEnv, ENT_QUOTES, 'UTF-8') ?>)</div>

    <div class="card">
        <div class="card-head">Exception</div>
        <div class="card-body">
            <div class="exception-type"><?= $exceptionClass ?></div>
            <div class="file-link"><?= $exceptionFile ?>:<?= $exceptionLine ?></div>
        </div>
    </div>

    <?php if ($snippet !== ''): ?>
    <div class="card">
        <div class="card-head">Source</div>
        <div class="card-body">
            <div class="snippet"><?= $snippet ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head">Request</div>
        <div class="card-body grid">
            <div class="kv"><dt>Method</dt><dd><?= $method ?></dd></div>
            <div class="kv"><dt>URL</dt><dd><?= $uri ?></dd></div>
            <div class="kv"><dt>PHP</dt><dd><?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="kv"><dt>Debug</dt><dd>APP_DEBUG is enabled</dd></div>
        </div>
    </div>

    <?php if ($frames !== []): ?>
    <div class="card">
        <div class="card-head">Stack trace</div>
        <ol class="frames">
            <?php foreach ($frames as $index => $frame): ?>
                <li>
                    <span class="frame-no">#<?= (int) $index ?></span>
                    <div class="frame-call"><?= htmlspecialchars($frame['call'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($frame['file'] !== '[internal]'): ?>
                        <div class="frame-file">
                            <?= htmlspecialchars($frame['file'], ENT_QUOTES, 'UTF-8') ?><?= $frame['line'] > 0 ? ':' . (int) $frame['line'] : '' ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endif; ?>

    <?php if ($previous instanceof Throwable): ?>
    <div class="card">
        <div class="card-head">Previous exception</div>
        <div class="card-body">
            <div class="exception-type"><?= htmlspecialchars($previous::class, ENT_QUOTES, 'UTF-8') ?></div>
            <div><?= htmlspecialchars($previous->getMessage(), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="file-link"><?= htmlspecialchars($previous->getFile(), ENT_QUOTES, 'UTF-8') ?>:<?= (int) $previous->getLine() ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
