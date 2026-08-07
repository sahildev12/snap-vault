<?php
/**
 * AJAX — Filter images (JSON)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    Helper::jsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$page = max(1, (int) Helper::input('page', 1));
$filters = [
    'period'    => (string) Helper::input('period', ''),
    'flag'      => (string) Helper::input('flag', ''),
    'search'    => trim((string) Helper::input('search', '')),
    'from'      => (string) Helper::input('from', ''),
    'to'        => (string) Helper::input('to', ''),
    'member_id' => (string) Helper::input('member_id', ''),
];

if (Auth::isMember()) {
    $filters['user_id'] = (int) Auth::id();
    unset($filters['member_id']);
} elseif (!empty($filters['member_id'])) {
    // admin filtering by member
} elseif (!empty(Helper::input('user_id'))) {
    $filters['user_id'] = (int) Helper::input('user_id');
}

$result = Upload::listFiltered($filters, $page, PER_PAGE);

$rows = array_map(static function (array $row): array {
    return [
        'id'            => (int) $row['id'],
        'image'         => Helper::uploadUrl($row['image']),
        'flag'          => $row['flag'],
        'description'   => $row['description'],
        'created_at'    => $row['created_at'],
        'created_label' => Helper::formatDate($row['created_at']),
        'uploader_name' => $row['uploader_name'] ?? '',
    ];
}, $result['rows']);

Helper::jsonResponse([
    'success' => true,
    'total'   => $result['total'],
    'page'    => $page,
    'rows'    => $rows,
]);
