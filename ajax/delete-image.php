<?php
/**
 * AJAX — Delete image
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    Helper::jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
}

if (!Helper::isPost()) {
    Helper::jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!Csrf::validate($token)) {
    Helper::jsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    Helper::jsonResponse(['success' => false, 'message' => 'Invalid image id.'], 400);
}

$ownerId = Auth::isAdmin() ? null : Auth::id();
$ok = Upload::delete($id, $ownerId !== null ? (int) $ownerId : null);

if (!$ok) {
    Helper::jsonResponse(['success' => false, 'message' => 'Unable to delete image.'], 400);
}

Helper::jsonResponse(['success' => true, 'message' => 'Image deleted.']);
