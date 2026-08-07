<?php
/**
 * Task assignment & completion
 */

declare(strict_types=1);

class Task
{
    public static function refreshOverdue(): void
    {
        try {
            Database::getConnection()->exec(
                "UPDATE tasks
                 SET status = 'overdue'
                 WHERE status IN ('pending', 'in_progress')
                   AND deadline < NOW()"
            );
        } catch (Throwable $e) {
            AppLog::warning('Task::refreshOverdue failed (schema may be outdated)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT t.*,
                    a.name AS assignee_name, a.username AS assignee_username, a.profile AS assignee_profile,
                    b.name AS assigner_name
             FROM tasks t
             INNER JOIN users a ON a.id = t.assigned_to
             INNER JOIN users b ON b.id = t.assigned_by
             WHERE t.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function listAll(string $status = '', string $search = '', int $page = 1, int $perPage = TASKS_PER_PAGE): array
    {
        self::refreshOverdue();
        $offset = max(0, ($page - 1) * $perPage);
        $params = [];
        $where = 'WHERE 1=1';
        if ($status !== '' && in_array($status, ['pending', 'in_progress', 'completed', 'overdue'], true)) {
            $where .= ' AND t.status = ?';
            $params[] = $status;
        }
        $search = trim($search);
        if ($search !== '') {
            $where .= ' AND (t.title LIKE ? OR t.description LIKE ? OR a.name LIKE ? OR a.username LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $pdo = Database::getConnection();
        $countSql = "SELECT COUNT(*) FROM tasks t
                     INNER JOIN users a ON a.id = t.assigned_to
                     {$where}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT t.*, a.name AS assignee_name, b.name AS assigner_name
                FROM tasks t
                INNER JOIN users a ON a.id = t.assigned_to
                INNER JOIN users b ON b.id = t.assigned_by
                {$where}
                ORDER BY
                    CASE t.status
                        WHEN 'overdue' THEN 0
                        WHEN 'pending' THEN 1
                        WHEN 'in_progress' THEN 2
                        ELSE 3
                    END,
                    t.deadline ASC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function listForMember(int $memberId, string $status = '', string $search = '', int $page = 1, int $perPage = TASKS_PER_PAGE): array
    {
        self::refreshOverdue();
        $offset = max(0, ($page - 1) * $perPage);
        $params = [$memberId];
        $where = 'WHERE t.assigned_to = ?';
        if ($status !== '' && in_array($status, ['pending', 'in_progress', 'completed', 'overdue'], true)) {
            $where .= ' AND t.status = ?';
            $params[] = $status;
        }
        $search = trim($search);
        if ($search !== '') {
            $where .= ' AND (t.title LIKE ? OR t.description LIKE ? OR b.name LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }

        $pdo = Database::getConnection();
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM tasks t
             INNER JOIN users b ON b.id = t.assigned_by
             {$where}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT t.*, b.name AS assigner_name
                FROM tasks t
                INNER JOIN users b ON b.id = t.assigned_by
                {$where}
                ORDER BY
                    CASE t.status
                        WHEN 'overdue' THEN 0
                        WHEN 'pending' THEN 1
                        WHEN 'in_progress' THEN 2
                        ELSE 3
                    END,
                    t.deadline ASC
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function create(array $data): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO tasks (title, description, assigned_to, assigned_by, deadline, status, created_at)
             VALUES (?, ?, ?, ?, ?, \'pending\', NOW())'
        );
        $stmt->execute([
            $data['title'],
            $data['description'] !== '' ? $data['description'] : null,
            (int) $data['assigned_to'],
            (int) $data['assigned_by'],
            $data['deadline'],
        ]);
        $id = (int) Database::getConnection()->lastInsertId();

        $link = BASE_URL . 'member/task-view.php?id=' . $id;
        Notification::create(
            (int) $data['assigned_to'],
            'task.assigned',
            'New task assigned',
            $data['title'],
            $link
        );
        Webhook::dispatch('task.assigned', [
            'task_id'     => $id,
            'title'       => $data['title'],
            'assigned_to' => (int) $data['assigned_to'],
            'deadline'    => $data['deadline'],
        ]);

        return $id;
    }

    public static function start(int $id, int $memberId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE tasks
             SET status = 'in_progress', started_at = COALESCE(started_at, NOW())
             WHERE id = ? AND assigned_to = ? AND status IN ('pending', 'overdue')"
        );
        return $stmt->execute([$id, $memberId]);
    }

    public static function complete(int $id, int $memberId, string $note = ''): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE tasks
             SET status = 'completed',
                 completed_at = NOW(),
                 started_at = COALESCE(started_at, created_at),
                 completion_note = ?
             WHERE id = ? AND assigned_to = ? AND status <> 'completed'"
        );
        $ok = $stmt->execute([$note !== '' ? $note : null, $id, $memberId]);
        if (!$ok || $stmt->rowCount() === 0) {
            return false;
        }

        $task = self::find($id);
        if ($task) {
            $link = BASE_URL . 'admin/tasks.php?status=completed';
            Notification::create(
                (int) $task['assigned_by'],
                'task.completed',
                'Task completed',
                $task['title'] . ' by ' . ($task['assignee_name'] ?? 'member'),
                $link
            );
            Webhook::dispatch('task.completed', [
                'task_id'      => $id,
                'title'        => $task['title'],
                'assigned_to'  => (int) $task['assigned_to'],
                'completed_at' => $task['completed_at'],
                'duration'     => Helper::formatDuration($task['created_at'], $task['completed_at']),
            ]);
        }
        return true;
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE tasks
             SET title = ?, description = ?, assigned_to = ?, deadline = ?
             WHERE id = ?'
        );
        $ok = $stmt->execute([
            $data['title'],
            $data['description'] !== '' ? $data['description'] : null,
            (int) $data['assigned_to'],
            $data['deadline'],
            $id,
        ]);
        if ($ok) {
            Webhook::dispatch('task.updated', [
                'task_id'     => $id,
                'title'       => $data['title'],
                'assigned_to' => (int) $data['assigned_to'],
                'deadline'    => $data['deadline'],
            ]);
        }
        return $ok;
    }

    public static function delete(int $id): bool
    {
        $files = self::files($id);
        foreach ($files as $f) {
            $path = TASK_UPLOAD_DIR . $f['filename'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $stmt = Database::getConnection()->prepare('DELETE FROM tasks WHERE id = ?');
        $ok = $stmt->execute([$id]);
        if ($ok) {
            Webhook::dispatch('task.deleted', ['task_id' => $id]);
        }
        return $ok;
    }

    public static function addFile(int $taskId, int $userId, string $stored, ?string $original): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO task_files (task_id, filename, original_name, uploaded_by, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$taskId, $stored, $original, $userId]);
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function files(int $taskId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT f.*, u.name AS uploader_name
             FROM task_files f
             INNER JOIN users u ON u.id = f.uploaded_by
             WHERE f.task_id = ?
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }

    public static function counts(): array
    {
        $out = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'overdue' => 0];
        try {
            self::refreshOverdue();
            $rows = Database::getConnection()->query(
                "SELECT status, COUNT(*) AS c FROM tasks GROUP BY status"
            )->fetchAll();
            foreach ($rows as $row) {
                $out[$row['status']] = (int) $row['c'];
            }
        } catch (Throwable $e) {
            AppLog::warning('Task::counts failed (schema may be outdated)', [
                'error' => $e->getMessage(),
            ]);
        }
        return $out;
    }

    public static function storeCompletionFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload failed.'];
        }
        if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
            return ['success' => false, 'message' => 'File too large (max 10MB).'];
        }

        $original = (string) ($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = array_merge(ALLOWED_EXTENSIONS, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']);
        if (!in_array($ext, $allowed, true)) {
            return ['success' => false, 'message' => 'File type not allowed.'];
        }

        if (!is_dir(TASK_UPLOAD_DIR)) {
            mkdir(TASK_UPLOAD_DIR, 0755, true);
        }

        $stored = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = TASK_UPLOAD_DIR . $stored;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Could not save file.'];
        }

        return ['success' => true, 'filename' => $stored, 'original' => $original];
    }

    public static function statusBadge(string $status): string
    {
        $map = [
            'pending'     => ['Pending', 'badge-urgent'],
            'in_progress' => ['In progress', 'badge-confidential'],
            'completed'   => ['Completed', 'badge-normal'],
            'overdue'     => ['Overdue', 'badge-important'],
        ];
        $meta = $map[$status] ?? ['Unknown', 'badge-normal'];
        return '<span class="badge ' . $meta[1] . '">' . htmlspecialchars($meta[0], ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
