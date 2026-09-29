<?php
/**
 * AJAX — Delete presentation
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

if (!SchemaMigrator::tableExists('presentations')) {
    Helper::jsonResponse(['success' => false, 'message' => 'Database migration required.'], 503);
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    Helper::jsonResponse(['success' => false, 'message' => 'Invalid presentation id.'], 400);
}

$ownerId = Auth::isAdmin() ? null : Auth::id();
$ok = Presentation::delete($id, $ownerId !== null ? (int) $ownerId : null);

if (!$ok) {
    Helper::jsonResponse(['success' => false, 'message' => 'Unable to delete presentation.'], 400);
}

Helper::jsonResponse(['success' => true, 'message' => 'Presentation deleted.']);
