<?php
/**
 * User model / repository
 */

declare(strict_types=1);

class User
{
    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function countMembers(): int
    {
        $stmt = Database::getConnection()->query("SELECT COUNT(*) FROM users WHERE role = 'member'");
        return (int) $stmt->fetchColumn();
    }

    public static function listMembers(string $search = '', int $page = 1, int $perPage = MEMBERS_PER_PAGE): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $params = [];
        $where = "WHERE role = 'member'";

        if ($search !== '') {
            $where .= ' AND (name LIKE ? OR username LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $pdo = Database::getConnection();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT id, name, username, profile, status, created_at
                FROM users {$where}
                ORDER BY name ASC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'rows'  => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    public static function searchMembers(string $q, int $limit = 20): array
    {
        $like = '%' . $q . '%';
        $stmt = Database::getConnection()->prepare(
            "SELECT id, name, username, profile, status
             FROM users
             WHERE role = 'member' AND (name LIKE ? OR username LIKE ?)
             ORDER BY name ASC
             LIMIT {$limit}"
        );
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    public static function allActiveMembers(): array
    {
        $stmt = Database::getConnection()->query(
            "SELECT id, name, username, profile, status
             FROM users
             WHERE role = 'member' AND status = 1
             ORDER BY name ASC"
        );
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO users (name, username, password, profile, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['profile'] ?? null,
            $data['role'] ?? 'member',
            (int) ($data['status'] ?? 1),
        ]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach (['name', 'username', 'profile', 'role', 'status'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = "{$key} = ?";
                $params[] = $data[$key];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if ($fields === []) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = Database::getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function resetPassword(int $id, string $password): bool
    {
        $stmt = Database::getConnection()->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
    }

    public static function toggleStatus(int $id): bool
    {
        $user = self::findById($id);
        if (!$user || $user['role'] === 'admin') {
            return false;
        }
        $newStatus = (int) $user['status'] === 1 ? 0 : 1;
        $stmt = Database::getConnection()->prepare('UPDATE users SET status = ? WHERE id = ?');
        return $stmt->execute([$newStatus, $id]);
    }

    public static function delete(int $id): bool
    {
        $user = self::findById($id);
        if (!$user || $user['role'] === 'admin') {
            return false;
        }

        // Unlink profile image
        if (!empty($user['profile'])) {
            $path = PROFILE_DIR . $user['profile'];
            if (is_file($path)) {
                @unlink($path);
            }
        }

        // Unlink upload files before cascade
        $uploads = Upload::listByUser($id, [], 1, 100000);
        foreach ($uploads['rows'] as $row) {
            $file = UPLOAD_DIR . $row['image'];
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $stmt = Database::getConnection()->prepare('DELETE FROM users WHERE id = ? AND role = ?');
        return $stmt->execute([$id, 'member']);
    }

    public static function usernameExists(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = Database::getConnection()->prepare(
                'SELECT COUNT(*) FROM users WHERE username = ? AND id != ?'
            );
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = Database::getConnection()->prepare(
                'SELECT COUNT(*) FROM users WHERE username = ?'
            );
            $stmt->execute([$username]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    /** Members with upload stats for folder cards */
    public static function membersWithStats(string $search = ''): array
    {
        $params = [];
        $where = "WHERE u.role = 'member'";
        if ($search !== '') {
            $where .= ' AND (u.name LIKE ? OR u.username LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT u.id, u.name, u.username, u.profile, u.status,
                       COUNT(up.id) AS total_images,
                       MAX(up.created_at) AS last_upload
                FROM users u
                LEFT JOIN uploads up ON up.user_id = u.id
                {$where}
                GROUP BY u.id, u.name, u.username, u.profile, u.status
                ORDER BY u.name ASC";

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
