<?php
/**
 * Presentation / meeting file uploads
 */

declare(strict_types=1);

class Presentation
{
    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT p.*, u.name AS uploader_name, u.username AS uploader_username
             FROM presentations p
             JOIN users u ON u.id = p.user_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function countAll(): int
    {
        return (int) Database::getConnection()->query('SELECT COUNT(*) FROM presentations')->fetchColumn();
    }

    public static function countByUser(int $userId): int
    {
        $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM presentations WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function countDemo(): int
    {
        return (int) Database::getConnection()
            ->query('SELECT COUNT(*) FROM presentations WHERE is_demo = 1')
            ->fetchColumn();
    }

    /**
     * @return array{where:string, params:list<mixed>}
     */
    public static function buildFilters(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'p.user_id = ?';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['member_id'])) {
            $where[] = 'p.user_id = ?';
            $params[] = (int) $filters['member_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE ? OR p.description LIKE ? OR p.original_name LIKE ? OR u.name LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $period = $filters['period'] ?? '';
        switch ($period) {
            case 'today':
                $where[] = 'DATE(p.created_at) = CURDATE()';
                break;
            case 'week':
                $where[] = 'YEARWEEK(p.created_at, 1) = YEARWEEK(CURDATE(), 1)';
                break;
            case 'month':
                $where[] = 'YEAR(p.created_at) = YEAR(CURDATE()) AND MONTH(p.created_at) = MONTH(CURDATE())';
                break;
            case 'range':
                if (!empty($filters['from'])) {
                    $where[] = 'DATE(p.created_at) >= ?';
                    $params[] = $filters['from'];
                }
                if (!empty($filters['to'])) {
                    $where[] = 'DATE(p.created_at) <= ?';
                    $params[] = $filters['to'];
                }
                break;
            default:
                if (!empty($filters['from'])) {
                    $where[] = 'DATE(p.created_at) >= ?';
                    $params[] = $filters['from'];
                }
                if (!empty($filters['to'])) {
                    $where[] = 'DATE(p.created_at) <= ?';
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

        $countSql = "SELECT COUNT(*) FROM presentations p JOIN users u ON u.id = p.user_id WHERE {$built['where']}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($built['params']);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.*, u.name AS uploader_name, u.profile AS uploader_profile
                FROM presentations p
                JOIN users u ON u.id = p.user_id
                WHERE {$built['where']}
                ORDER BY COALESCE(p.meeting_date, p.created_at) DESC, p.created_at DESC
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
     * @return array{success:bool, filename?:string, original_name?:string, file_size?:int, message?:string}
     */
    public static function storeFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE))];
        }

        if (($file['size'] ?? 0) > PRESENTATION_MAX_BYTES) {
            return ['success' => false, 'message' => 'File exceeds the 50MB size limit.'];
        }

        $original = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, PRESENTATION_EXTENSIONS, true)) {
            return ['success' => false, 'message' => 'Only PPT, PPTX, and PDF files are allowed.'];
        }

        $tmp = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmp)) {
            return ['success' => false, 'message' => 'Invalid upload.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        if (!in_array($mime, PRESENTATION_MIME_TYPES, true)) {
            return ['success' => false, 'message' => 'Invalid file type.'];
        }

        if (!is_dir(PRESENTATION_UPLOAD_DIR)) {
            mkdir(PRESENTATION_UPLOAD_DIR, 0755, true);
        }

        $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = PRESENTATION_UPLOAD_DIR . $filename;

        if (!move_uploaded_file($tmp, $target)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file.'];
        }

        return [
            'success'       => true,
            'filename'      => $filename,
            'original_name' => $original,
            'file_size'     => (int) ($file['size'] ?? 0),
        ];
    }

    public static function create(
        int $userId,
        string $title,
        string $filename,
        string $originalName,
        ?string $description,
        ?string $meetingDate,
        ?int $fileSize,
        bool $isDemo = false
    ): int {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO presentations (user_id, title, filename, original_name, description, meeting_date, file_size, is_demo, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $userId,
            $title,
            $filename,
            $originalName,
            $description !== '' ? $description : null,
            $meetingDate !== '' ? $meetingDate : null,
            $fileSize,
            $isDemo ? 1 : 0,
        ]);
        $id = (int) Database::getConnection()->lastInsertId();
        Webhook::dispatch('presentation.created', [
            'presentation_id' => $id,
            'user_id'         => $userId,
            'title'           => $title,
            'filename'        => $filename,
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

        $path = PRESENTATION_UPLOAD_DIR . $row['filename'];
        if (is_file($path)) {
            @unlink($path);
        }

        $stmt = Database::getConnection()->prepare('DELETE FROM presentations WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function filePath(array $row): string
    {
        return PRESENTATION_UPLOAD_DIR . $row['filename'];
    }

    public static function fileExtension(array $row): string
    {
        return strtolower(pathinfo((string) $row['filename'], PATHINFO_EXTENSION));
    }

    public static function isPdf(array $row): bool
    {
        return self::fileExtension($row) === 'pdf';
    }

    public static function isPowerPoint(array $row): bool
    {
        return in_array(self::fileExtension($row), ['ppt', 'pptx'], true);
    }

    public static function createViewToken(int $id, int $ttl = 3600): string
    {
        $expires = time() + max(300, $ttl);
        $payload = $id . ':' . $expires;
        $signature = hash_hmac('sha256', $payload, VIEW_TOKEN_SECRET);
        $token = $payload . ':' . $signature;

        return rtrim(strtr(base64_encode($token), '+/', '-_'), '=');
    }

    public static function validateViewToken(string $token): ?int
    {
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }

        $parts = explode(':', $decoded);
        if (count($parts) !== 3) {
            return null;
        }

        [$id, $expires, $signature] = $parts;
        $payload = $id . ':' . $expires;
        $expected = hash_hmac('sha256', $payload, VIEW_TOKEN_SECRET);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        if ((int) $expires < time()) {
            return null;
        }

        return (int) $id;
    }

    public static function publicFileUrl(int $id): string
    {
        $token = self::createViewToken($id);
        return Helper::absoluteUrl(BASE_URL . 'presentation-file.php?token=' . rawurlencode($token));
    }

    public static function inlineViewUrl(int $id): string
    {
        return BASE_URL . 'view-presentation.php?id=' . $id;
    }

    public static function officeEmbedUrl(int $id): string
    {
        return 'https://view.officeapps.live.com/op/embed.aspx?src='
            . rawurlencode(self::publicFileUrl($id));
    }

    public static function userCanAccess(array $row): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }

        return Auth::isMember() && (int) $row['user_id'] === (int) Auth::id();
    }

    public static function sendFile(array $row, bool $inline = false, bool $publicEmbed = false): void
    {
        $path = self::filePath($row);
        if (!is_file($path)) {
            http_response_code(404);
            exit('File missing.');
        }

        $mime = self::mimeForRow($row);
        $filename = (string) ($row['original_name'] ?: $row['filename']);
        $disposition = $inline ? 'inline' : 'attachment';
        $size = (int) filesize($path);

        if ($publicEmbed) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
            header('Access-Control-Expose-Headers: Content-Length, Content-Type, Content-Disposition');
            header('Cache-Control: public, max-age=300');
        } else {
            header('Cache-Control: private, max-age=3600');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) $size);
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $filename) . '"');
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            exit;
        }

        readfile($path);
        exit;
    }

    public static function mimeForRow(array $row): string
    {
        return match (self::fileExtension($row)) {
            'pdf'  => 'application/pdf',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => mime_content_type(self::filePath($row)) ?: 'application/octet-stream',
        };
    }

    public static function fileIcon(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf'  => 'bi-file-earmark-pdf',
            'ppt'  => 'bi-file-earmark-ppt',
            'pptx' => 'bi-file-earmark-ppt',
            default => 'bi-file-earmark',
        };
    }

    public static function formatSize(?int $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '—';
        }
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
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
