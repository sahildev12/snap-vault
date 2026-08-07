<?php
/**
 * Notifications AJAX API
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

$me = (int) Auth::id();
$action = (string) Helper::input('action', 'list');

try {
    if ($action === 'mark' && Helper::isPost()) {
        Csrf::requireValid();
        $id = (int) Helper::input('id', 0);
        if ($id > 0) {
            Notification::markRead($me, $id);
        } else {
            Notification::markRead($me, null);
        }
        Helper::jsonResponse([
            'success' => true,
            'unread'  => Notification::unreadCount($me),
        ]);
    }

    $rows = Notification::recent($me, 25);
    Helper::jsonResponse([
        'success'       => true,
        'unread'        => Notification::unreadCount($me),
        'notifications' => $rows,
    ]);
} catch (Throwable $e) {
    Helper::jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
