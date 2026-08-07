<?php
/**
 * Idempotent schema updater for one-click server DB upgrades
 */
declare(strict_types=1);

class SchemaMigrator
{
    /** @return list<array{id:string,label:string,applied:bool}> */
    public static function status(): array
    {
        self::ensureMigrationsTable();
        $applied = self::appliedIds();
        $out = [];
        foreach (self::definitions() as $id => $def) {
            $out[] = [
                'id'      => $id,
                'label'   => (string) $def['label'],
                'applied' => in_array($id, $applied, true),
            ];
        }
        return $out;
    }

    /** @return array{ok:bool,ran:int,skipped:int,messages:list<string>} */
    public static function run(): array
    {
        self::ensureMigrationsTable();
        $applied = self::appliedIds();
        $messages = [];
        $ran = 0;
        $skipped = 0;

        foreach (self::definitions() as $id => $def) {
            if (in_array($id, $applied, true)) {
                $skipped++;
                $messages[] = 'Skipped (already applied): ' . $def['label'];
                continue;
            }

            try {
                ($def['up'])(Database::getConnection());
                self::markApplied($id);
                $ran++;
                $messages[] = 'Applied: ' . $def['label'];
            } catch (Throwable $e) {
                $messages[] = 'Failed: ' . $def['label'] . ' — ' . $e->getMessage();
                return [
                    'ok'       => false,
                    'ran'      => $ran,
                    'skipped'  => $skipped,
                    'messages' => $messages,
                ];
            }
        }

        if ($ran === 0 && $skipped > 0) {
            $messages[] = 'Database is already up to date.';
        }

        return [
            'ok'       => true,
            'ran'      => $ran,
            'skipped'  => $skipped,
            'messages' => $messages,
        ];
    }

    /** @return array<string,bool> */
    public static function tablePresence(): array
    {
        $needed = ['users', 'uploads', 'tasks', 'task_files', 'notifications', 'messages', 'schema_migrations'];
        $pdo = Database::getConnection();
        $out = [];
        foreach ($needed as $table) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$table]);
            $out[$table] = ((int) $stmt->fetchColumn()) > 0;
        }
        return $out;
    }

    private static function ensureMigrationsTable(): void
    {
        Database::getConnection()->exec(
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                id VARCHAR(120) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /** @return list<string> */
    private static function appliedIds(): array
    {
        $rows = Database::getConnection()
            ->query('SELECT id FROM schema_migrations ORDER BY applied_at ASC')
            ->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strval', $rows ?: []);
    }

    private static function markApplied(string $id): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO schema_migrations (id, applied_at) VALUES (?, NOW())'
        );
        $stmt->execute([$id]);
    }

    /**
     * Ordered migration steps. Safe to re-run overall; each id runs once.
     *
     * @return array<string, array{label:string, up:callable}>
     */
    private static function definitions(): array
    {
        return [
            '2026_08_jammu_uploads_flags' => [
                'label' => 'Expand upload flags (urgent, confidential)',
                'up'    => static function (PDO $pdo): void {
                    $pdo->exec(
                        "ALTER TABLE uploads
                         MODIFY COLUMN flag ENUM('normal', 'important', 'urgent', 'confidential')
                         NOT NULL DEFAULT 'normal'"
                    );
                },
            ],
            '2026_08_jammu_tasks' => [
                'label' => 'Create tasks table',
                'up'    => static function (PDO $pdo): void {
                    $pdo->exec(
                        "CREATE TABLE IF NOT EXISTS tasks (
                            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(200) NOT NULL,
                            description TEXT DEFAULT NULL,
                            assigned_to INT UNSIGNED NOT NULL,
                            assigned_by INT UNSIGNED NOT NULL,
                            deadline DATETIME NOT NULL,
                            status ENUM('pending', 'in_progress', 'completed', 'overdue') NOT NULL DEFAULT 'pending',
                            started_at DATETIME DEFAULT NULL,
                            completed_at DATETIME DEFAULT NULL,
                            completion_note TEXT DEFAULT NULL,
                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX idx_tasks_assigned_to (assigned_to),
                            INDEX idx_tasks_assigned_by (assigned_by),
                            INDEX idx_tasks_status (status),
                            INDEX idx_tasks_deadline (deadline),
                            CONSTRAINT fk_tasks_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
                            CONSTRAINT fk_tasks_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ],
            '2026_08_jammu_task_files' => [
                'label' => 'Create task_files table',
                'up'    => static function (PDO $pdo): void {
                    $pdo->exec(
                        "CREATE TABLE IF NOT EXISTS task_files (
                            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            task_id INT UNSIGNED NOT NULL,
                            filename VARCHAR(255) NOT NULL,
                            original_name VARCHAR(255) DEFAULT NULL,
                            uploaded_by INT UNSIGNED NOT NULL,
                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_task_files_task (task_id),
                            CONSTRAINT fk_task_files_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE ON UPDATE CASCADE,
                            CONSTRAINT fk_task_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ],
            '2026_08_jammu_notifications' => [
                'label' => 'Create notifications table',
                'up'    => static function (PDO $pdo): void {
                    $pdo->exec(
                        "CREATE TABLE IF NOT EXISTS notifications (
                            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            user_id INT UNSIGNED NOT NULL,
                            type VARCHAR(60) NOT NULL,
                            title VARCHAR(200) NOT NULL,
                            body TEXT DEFAULT NULL,
                            link VARCHAR(255) DEFAULT NULL,
                            is_read TINYINT(1) NOT NULL DEFAULT 0,
                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_notifications_user (user_id),
                            INDEX idx_notifications_read (is_read),
                            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ],
            '2026_08_jammu_messages' => [
                'label' => 'Create messages table',
                'up'    => static function (PDO $pdo): void {
                    $pdo->exec(
                        "CREATE TABLE IF NOT EXISTS messages (
                            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            from_user_id INT UNSIGNED NOT NULL,
                            to_user_id INT UNSIGNED NOT NULL,
                            body TEXT NOT NULL,
                            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            read_at DATETIME DEFAULT NULL,
                            INDEX idx_messages_from (from_user_id),
                            INDEX idx_messages_to (to_user_id),
                            INDEX idx_messages_pair (from_user_id, to_user_id),
                            INDEX idx_messages_created (created_at),
                            CONSTRAINT fk_messages_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
                            CONSTRAINT fk_messages_to FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                },
            ],
        ];
    }
}
