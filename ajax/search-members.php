<?php
/**
 * AJAX — Search team members
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check() || !Auth::isAdmin()) {
    Helper::jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$q = trim((string) Helper::input('q', ''));
$rows = $q === '' ? [] : User::searchMembers($q);

$data = array_map(static function (array $row): array {
    return [
        'id'       => (int) $row['id'],
        'name'     => $row['name'],
        'username' => $row['username'],
        'profile'  => Helper::profileUrl($row['profile']),
        'status'   => (int) $row['status'],
    ];
}, $rows);

Helper::jsonResponse(['success' => true, 'rows' => $data]);
