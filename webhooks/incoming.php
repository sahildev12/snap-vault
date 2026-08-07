<?php
/**
 * Inbound webhook endpoint
 * POST JSON with header X-Jammu-Signature: sha256=<hmac>
 *
 * Example body:
 * {
 *   "event": "notification.push",
 *   "user_id": 2,
 *   "title": "External alert",
 *   "body": "Something happened",
 *   "link": "/member/dashboard.php"
 * }
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$sig = $_SERVER['HTTP_X_JAMMU_SIGNATURE'] ?? null;

if (!Webhook::verifySignature($raw, $sig)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$event = (string) ($data['event'] ?? 'notification.push');
$userId = (int) ($data['user_id'] ?? 0);
$title = trim((string) ($data['title'] ?? 'Webhook event'));
$body = isset($data['body']) ? trim((string) $data['body']) : null;
$link = isset($data['link']) ? trim((string) $data['link']) : null;

if ($userId < 1 || $title === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'user_id and title required']);
    exit;
}

$user = User::findById($userId);
if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$id = Notification::create($userId, $event, $title, $body, $link);

echo json_encode([
    'success' => true,
    'notification_id' => $id,
    'event' => $event,
]);
