<?php
/**
 * Upload model & file handling
 */

declare(strict_types=1);

class Upload
{
    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT up.*, u.name AS uploader_name, u.username AS uploader_username
             FROM uploads up
             JOIN users u ON u.id = up.user_id
             WHERE up.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function countAll(): int
    {
        return (int) Database::getConnection()->query('SELECT COUNT(*) FROM uploads')->fetchColumn();
    }

    public static function countToday(?int $userId = null): int
    {
        if ($userId !== null) {
            $stmt = Database::getConnection()->prepare(
                'SELECT COUNT(*) FROM uploads WHERE user_id = ? AND DATE(created_at) = CURDATE()'
            );
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        }
        return (int) Database::getConnection()
            ->query('SELECT COUNT(*) FROM uploads WHERE DATE(created_at) = CURDATE()')
            ->fetchColumn();
    }

    public static function countByFlag(string $flag): int
    {
        $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM uploads WHERE flag = ?');
        $stmt->execute([$flag]);
        return (int) $stmt->fetchColumn();
    }

    public static function countByUser(int $userId): int
    {
        $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM uploads WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function recent(int $limit = 10, ?int $userId = null): array
    {
        $limit = max(1, min(100, $limit));
        if ($userId !== null) {
            $stmt = Database::getConnection()->prepare(
                "SELECT up.*, u.name AS uploader_name
                 FROM uploads up
                 JOIN users u ON u.id = up.user_id
                 WHERE up.user_id = ?
                 ORDER BY up.created_at DESC
                 LIMIT {$limit}"
            );
            $stmt->execute([$userId]);
        } else {
            $stmt = Database::getConnection()->query(
                "SELECT up.*, u.name AS uploader_name
                 FROM uploads up
                 JOIN users u ON u.id = up.user_id
                 ORDER BY up.created_at DESC
                 LIMIT {$limit}"
            );
        }
        return $stmt->fetchAll();
    }

    /** Last 7 days upload counts for charts */
    public static function last7DaysCounts(?int $userId = null): array
    {
        $labels = [];
        $counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('D', strtotime($date));

            if ($userId !== null) {
                $stmt = Database::getConnection()->prepare(
                    'SELECT COUNT(*) FROM uploads WHERE user_id = ? AND DATE(created_at) = ?'
                );
                $stmt->execute([$userId, $date]);
            } else {
                $stmt = Database::getConnection()->prepare(
                    'SELECT COUNT(*) FROM uploads WHERE DATE(created_at) = ?'
                );
                $stmt->execute([$date]);
            }
            $counts[] = (int) $stmt->fetchColumn();
        }
        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * Build filter WHERE clause from filter array.
     * Keys: period (today|week|month), from, to, flag, member_id, search, user_id
     */
    public static function buildFilters(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'up.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['member_id'])) {
            $where[] = 'up.user_id = ?';
            $params[] = (int) $filters['member_id'];
        }

        if (!empty($filters['flag']) && in_array($filters['flag'], Helper::uploadFlags(), true)) {
            $where[] = 'up.flag = ?';
            $params[] = $filters['flag'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(up.description LIKE ? OR u.name LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $period = $filters['period'] ?? '';
        switch ($period) {
            case 'today':
                $where[] = 'DATE(up.created_at) = CURDATE()';
                break;
            case 'week':
                $where[] = 'YEARWEEK(up.created_at, 1) = YEARWEEK(CURDATE(), 1)';
                break;
            case 'month':
                $where[] = 'YEAR(up.created_at) = YEAR(CURDATE()) AND MONTH(up.created_at) = MONTH(CURDATE())';
                break;
            case 'range':
                if (!empty($filters['from'])) {
                    $where[] = 'DATE(up.created_at) >= ?';
                    $params[] = $filters['from'];
                }
                if (!empty($filters['to'])) {
                    $where[] = 'DATE(up.created_at) <= ?';
                    $params[] = $filters['to'];
                }
                break;
            default:
                // Custom from/to without period
                if (!empty($filters['from'])) {
                    $where[] = 'DATE(up.created_at) >= ?';
                    $params[] = $filters['from'];
                }
                if (!empty($filters['to'])) {
                    $where[] = 'DATE(up.created_at) <= ?';
                    $params[] = $filters['to'];
                }
                break;
        }

        return [
            'where'  => implode(' AND ', $where),
            'params' => $params,
        ];
    }

    public static function listFiltered(array $filters, int $page = 1, int $perPage = PER_PAGE): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $built = self::buildFilters($filters);
        $pdo = Database::getConnection();

        $countSql = "SELECT COUNT(*) FROM uploads up JOIN users u ON u.id = up.user_id WHERE {$built['where']}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($built['params']);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT up.*, u.name AS uploader_name, u.profile AS uploader_profile
                FROM uploads up
                JOIN users u ON u.id = up.user_id
                WHERE {$built['where']}
                ORDER BY up.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($built['params']);

        return [
            'rows'  => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    public static function listByUser(int $userId, array $filters = [], int $page = 1, int $perPage = PER_PAGE): array
    {
        $filters['user_id'] = $userId;
        return self::listFiltered($filters, $page, $perPage);
    }

    /**
     * Validate and store uploaded image file.
     * @return array{success:bool, filename?:string, message?:string}
     */
    public static function storeFile(array $file, string $destinationDir): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE))];
        }

        if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
            return ['success' => false, 'message' => 'File exceeds the 10MB size limit.'];
        }

        $original = $file['name'] ?? '';
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            return ['success' => false, 'message' => 'Only JPG, JPEG, PNG, and WEBP files are allowed.'];
        }

        $tmp = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            return ['success' => false, 'message' => 'Invalid upload.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        if (!in_array($mime, ALLOWED_MIME_TYPES, true)) {
            return ['success' => false, 'message' => 'Invalid image MIME type.'];
        }

        // Extra: verify it's an image
        if (@getimagesize($tmp) === false) {
            return ['success' => false, 'message' => 'File is not a valid image.'];
        }

        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $filename = time() . '_' . random_int(100000, 999999) . '.' . $ext;
        $target = rtrim($destinationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmp, $target)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file.'];
        }

        return ['success' => true, 'filename' => $filename];
    }

    public static function create(int $userId, string $image, string $flag, ?string $description): int
    {
        if (!in_array($flag, Helper::uploadFlags(), true)) {
            $flag = 'normal';
        }
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO uploads (user_id, image, flag, description, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $image, $flag, $description !== '' ? $description : null]);
        $id = (int) Database::getConnection()->lastInsertId();
        Webhook::dispatch('upload.created', [
            'upload_id' => $id,
            'user_id'   => $userId,
            'flag'      => $flag,
            'image'     => $image,
        ]);
        return $id;
    }

    public static function delete(int $id, ?int $ownerId = null): bool
    {
        $row = self::findById($id);
        if (!$row) {
            return false;
        }
        if ($ownerId !== null && (int) $row['user_id'] !== $ownerId) {
            return false;
        }

        $path = UPLOAD_DIR . $row['image'];
        if (is_file($path)) {
            @unlink($path);
        }

        $stmt = Database::getConnection()->prepare('DELETE FROM uploads WHERE id = ?');
        return $stmt->execute([$id]);
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension.',
            default => 'Unknown upload error.',
        };
    }
}
