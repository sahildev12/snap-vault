<?php
/**
 * AJAX — Get member details (admin)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check() || !Auth::isAdmin()) {
    Helper::jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$id = (int) Helper::input('id', 0);
$user = User::findById($id);

if (!$user || $user['role'] !== 'member') {
    Helper::jsonResponse(['success' => false, 'message' => 'Member not found.'], 404);
}

Helper::jsonResponse([
    'success' => true,
    'member'  => [
        'id'            => (int) $user['id'],
        'name'          => $user['name'],
        'username'      => $user['username'],
        'status'        => (int) $user['status'],
        'profile'       => $user['profile'],
        'profile_url'   => Helper::profileUrl($user['profile']),
        'created_at'    => $user['created_at'],
        'created_label' => Helper::formatDate($user['created_at'], 'd M Y'),
        'total_images'  => Upload::countByUser((int) $user['id']),
        'today_uploads' => Upload::countToday((int) $user['id']),
    ],
]);
