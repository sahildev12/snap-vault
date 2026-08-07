<?php
/**
 * Chat poll / send API
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

$me = (int) Auth::id();
$action = (string) Helper::input('action', 'poll');

try {
    if ($action === 'unread') {
        Helper::jsonResponse([
            'success' => true,
            'unread'  => Message::unreadCount($me),
        ]);
    }

    if ($action === 'send' && Helper::isPost()) {
        Csrf::requireValid();
        $to = (int) Helper::input('to_user_id', 0);
        $body = (string) Helper::input('body', '');
        $peer = User::findById($to);
        if (!$peer) {
            Helper::jsonResponse(['success' => false, 'message' => 'Invalid recipient.'], 400);
        }
        // Admin can message members; members can message admins
        if (Auth::isAdmin() && ($peer['role'] ?? '') !== 'member') {
            Helper::jsonResponse(['success' => false, 'message' => 'Admin can only chat with members.'], 403);
        }
        if (Auth::isMember() && ($peer['role'] ?? '') !== 'admin') {
            Helper::jsonResponse(['success' => false, 'message' => 'Members can only chat with admin.'], 403);
        }

        $id = Message::send($me, $to, $body);
        Message::markRead($me, $to);
        Helper::jsonResponse([
            'success' => true,
            'message' => Message::thread($me, $to, $id - 1, 1)[0] ?? null,
        ]);
    }

    // poll thread
    $peerId = (int) Helper::input('with', 0);
    $after = (int) Helper::input('after', 0);
    if ($peerId < 1) {
        Helper::jsonResponse(['success' => false, 'message' => 'Missing peer.'], 400);
    }
    $peer = User::findById($peerId);
    if (!$peer) {
        Helper::jsonResponse(['success' => false, 'message' => 'Invalid peer.'], 400);
    }
    if (Auth::isAdmin() && ($peer['role'] ?? '') !== 'member') {
        Helper::jsonResponse(['success' => false, 'message' => 'Forbidden.'], 403);
    }
    if (Auth::isMember() && ($peer['role'] ?? '') !== 'admin') {
        Helper::jsonResponse(['success' => false, 'message' => 'Forbidden.'], 403);
    }

    Message::markRead($me, $peerId);
    $rows = Message::thread($me, $peerId, $after);
    Helper::jsonResponse([
        'success' => true,
        'messages'=> $rows,
        'unread'  => Message::unreadCount($me),
    ]);
} catch (Throwable $e) {
    Helper::jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
