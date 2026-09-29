<?php
/**
 * System-wide demo / sample data
 */

declare(strict_types=1);

class DemoData
{
    private const DEMO_PASSWORD = 'Member@123';

    /** @return array<string, array{demo:int,total:int,real:int}> */
    public static function counts(): array
    {
        return [
            'members'        => self::entityCounts('users', "role = 'member'"),
            'uploads'        => self::entityCounts('uploads'),
            'tasks'          => self::entityCounts('tasks'),
            'presentations'  => self::entityCounts('presentations'),
            'messages'       => self::entityCounts('messages'),
            'notifications'  => self::entityCounts('notifications'),
        ];
    }

    public static function hasDemo(): bool
    {
        foreach (self::counts() as $bucket) {
            if ($bucket['demo'] > 0) {
                return true;
            }
        }

        return false;
    }

    /** @return array{demo:int,total:int,real:int} */
    public static function presentationCounts(): array
    {
        return self::entityCounts('presentations');
    }

    /** @return array{ok:bool, message:string, summary?:array<string,int>} */
    public static function seedAll(): array
    {
        if (!self::demoColumnsReady()) {
            return [
                'ok'      => false,
                'message' => 'Demo columns are missing. Run database migration first (Settings → Update Database or migrate.php).',
            ];
        }

        if (self::hasDemo()) {
            return [
                'ok'      => false,
                'message' => 'Demo data already exists. Remove demo data first, then load again.',
            ];
        }

        if (SchemaMigrator::tableExists('presentations')) {
            $missingDocs = self::missingDemoDocFiles();
            if ($missingDocs !== []) {
                return [
                    'ok'      => false,
                    'message' => 'Demo document files are missing from demo doc/: '
                        . implode(', ', $missingDocs)
                        . '. Add demo.ppt, demo.pptx, and demo.pdf, then try again.',
                ];
            }
        }

        $adminId = self::adminId();
        if ($adminId === null) {
            return ['ok' => false, 'message' => 'No admin account found to assign demo tasks and chat.'];
        }

        $pdo = Database::getConnection();
        $summary = [
            'members'       => 0,
            'uploads'       => 0,
            'tasks'         => 0,
            'presentations' => 0,
            'messages'      => 0,
            'notifications' => 0,
        ];

        $demoMembers = [
            ['Rahul Sharma', 'demo_rahul', [15, 118, 110], 'important'],
            ['Anita Desai', 'demo_anita', [0, 102, 204], 'normal'],
            ['Vikram Patel', 'demo_vikram', [194, 65, 12], 'important'],
            ['Sara Khan', 'demo_sara', [124, 58, 237], 'normal'],
        ];

        $password = password_hash(self::DEMO_PASSWORD, PASSWORD_BCRYPT);
        $memberIds = [];

        foreach ($demoMembers as $i => $demo) {
            [$name, $username, $color, $primaryFlag] = $demo;
            $parts = explode(' ', $name);
            $initials = substr($parts[0], 0, 1) . substr($parts[1] ?? $parts[0], 0, 1);
            $profileName = 'demo_' . $username . '.jpg';
            self::makeAvatar(PROFILE_DIR . $profileName, $initials, $color);

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, username, password, profile, role, status, is_demo, created_at)
                 VALUES (?, ?, ?, ?, \'member\', 1, 1, DATE_SUB(NOW(), INTERVAL ? DAY))'
            );
            $stmt->execute([$name, $username, $password, $profileName, 10 - $i]);
            $userId = (int) $pdo->lastInsertId();
            $memberIds[] = $userId;
            $summary['members']++;

            $uploadCount = 3 + ($i % 3);
            for ($n = 0; $n < $uploadCount; $n++) {
                $filename = 'demo_' . time() . '_' . random_int(100000, 999999) . '_' . $username . $n . '.jpg';
                $c = [
                    max(0, min(255, $color[0] + ($n * 18))),
                    max(0, min(255, $color[1] - ($n * 10))),
                    max(0, min(255, $color[2] + ($n * 12))),
                ];
                self::makePhoto(UPLOAD_DIR . $filename, $c);

                $flag = ($n === 0) ? $primaryFlag : (($n % 2 === 0) ? 'important' : 'normal');
                $desc = $flag === 'important'
                    ? '[Demo] Priority capture from field visit'
                    : '[Demo] Routine site photo';

                $ins = $pdo->prepare(
                    'INSERT INTO uploads (user_id, image, flag, description, is_demo, created_at)
                     VALUES (?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? HOUR))'
                );
                $ins->execute([$userId, $filename, $flag, $desc, ($i * 8) + ($n * 5) + 2]);
                $summary['uploads']++;
            }
        }

        $taskSamples = [
            ['Submit weekly field report', 'Compile visits and upload supporting photos.', 0, 'pending'],
            ['Upload PHC infrastructure photos', 'Capture building, equipment room, and signage.', 1, 'in_progress'],
            ['Complete equipment audit', 'Verify serial numbers against the block register.', 2, 'completed'],
            ['Review NHM outreach indicators', 'Update monthly outreach summary for CMO review.', 3, 'overdue'],
            ['Prepare meeting presentation', 'Upload slides before the CMO office visit.', 0, 'pending'],
        ];

        foreach ($taskSamples as $task) {
            [$title, $description, $memberIndex, $status] = $task;
            $assignedTo = $memberIds[$memberIndex % count($memberIds)];
            $deadline = date('Y-m-d H:i:s', strtotime($status === 'overdue' ? '-2 days' : '+5 days'));
            $startedAt = $status === 'in_progress' ? date('Y-m-d H:i:s', strtotime('-1 day')) : null;
            $completedAt = $status === 'completed' ? date('Y-m-d H:i:s', strtotime('-1 hour')) : null;
            $completionNote = $status === 'completed' ? 'Demo completion note for client preview.' : null;

            $stmt = $pdo->prepare(
                'INSERT INTO tasks (title, description, assigned_to, assigned_by, deadline, status, started_at, completed_at, completion_note, is_demo, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? HOUR))'
            );
            $stmt->execute([
                '[Demo] ' . $title,
                $description,
                $assignedTo,
                $adminId,
                $deadline,
                $status,
                $startedAt,
                $completedAt,
                $completionNote,
                random_int(4, 48),
            ]);
            $summary['tasks']++;
        }

        if (SchemaMigrator::tableExists('presentations')) {
            if (!is_dir(PRESENTATION_UPLOAD_DIR)) {
                mkdir(PRESENTATION_UPLOAD_DIR, 0755, true);
            }

            foreach (self::demoPresentationCatalog() as $index => $sample) {
                $memberId = $memberIds[$index % count($memberIds)];
                $stored = self::copyDemoDocToPresentations($sample['source']);
                if ($stored === null) {
                    continue;
                }

                $meetingDate = date('Y-m-d', strtotime('+' . (int) $sample['days_ahead'] . ' days'));
                $stmt = $pdo->prepare(
                    'INSERT INTO presentations (user_id, title, filename, original_name, description, meeting_date, file_size, is_demo, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())'
                );
                $stmt->execute([
                    $memberId,
                    $sample['title'],
                    $stored['filename'],
                    $stored['original_name'],
                    $sample['description'],
                    $meetingDate,
                    $stored['file_size'],
                ]);
                $summary['presentations']++;
                usleep(5000);
            }

            if ($summary['presentations'] === 0) {
                return [
                    'ok'      => false,
                    'message' => 'Could not copy demo presentation files from demo doc/.',
                ];
            }
        }

        if (SchemaMigrator::tableExists('messages')) {
            $chatSamples = [
                [$memberIds[0], $adminId, 'Hello CMO office, sharing our block update for this week.'],
                [$adminId, $memberIds[0], 'Received. Please upload the meeting presentation before Friday.'],
                [$memberIds[1], $adminId, 'Infrastructure photos have been uploaded to the gallery.'],
            ];

            foreach ($chatSamples as $chat) {
                [$fromId, $toId, $body] = $chat;
                $stmt = $pdo->prepare(
                    'INSERT INTO messages (from_user_id, to_user_id, body, is_demo, created_at)
                     VALUES (?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? MINUTE))'
                );
                $stmt->execute([$fromId, $toId, $body, random_int(10, 120)]);
                $summary['messages']++;
            }
        }

        if (SchemaMigrator::tableExists('notifications')) {
            $notificationSamples = [
                [$adminId, 'demo.summary', 'Demo data loaded', 'Sample members, images, tasks, and presentations are ready for preview.'],
                [$memberIds[0], 'demo.task', 'Demo task reminder', 'A sample task is waiting in your task list.'],
                [$memberIds[1], 'demo.upload', 'Demo gallery item', 'A sample image was added to your gallery.'],
            ];

            foreach ($notificationSamples as $note) {
                [$userId, $type, $title, $body] = $note;
                $stmt = $pdo->prepare(
                    'INSERT INTO notifications (user_id, type, title, body, link, is_read, is_demo, created_at)
                     VALUES (?, ?, ?, ?, NULL, 0, 1, DATE_SUB(NOW(), INTERVAL ? MINUTE))'
                );
                $stmt->execute([$userId, $type, $title, $body, random_int(5, 90)]);
                $summary['notifications']++;
            }
        }

        return [
            'ok'      => true,
            'message' => 'Loaded demo data: '
                . $summary['members'] . ' members, '
                . $summary['uploads'] . ' images, '
                . $summary['tasks'] . ' tasks, '
                . $summary['presentations'] . ' presentations, '
                . $summary['messages'] . ' messages, '
                . $summary['notifications'] . ' notifications. '
                . 'Login example: demo_rahul / ' . self::DEMO_PASSWORD,
            'summary' => $summary,
        ];
    }

    /** @return array{ok:bool, message:string, deleted?:array<string,int>} */
    public static function removeDemo(): array
    {
        if (!self::demoColumnsReady()) {
            return ['ok' => false, 'message' => 'Demo columns are missing. Run database migration first.'];
        }

        if (!self::hasDemo()) {
            return ['ok' => true, 'message' => 'No demo data to remove.', 'deleted' => self::emptyDeleted()];
        }

        $pdo = Database::getConnection();
        $deleted = self::emptyDeleted();

        if (SchemaMigrator::tableExists('presentations')) {
            $rows = $pdo->query('SELECT id, filename FROM presentations WHERE is_demo = 1')->fetchAll();
            foreach ($rows as $row) {
                $path = PRESENTATION_UPLOAD_DIR . $row['filename'];
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $deleted['presentations'] = $pdo->exec('DELETE FROM presentations WHERE is_demo = 1') ?: 0;
        }

        if (SchemaMigrator::tableExists('uploads')) {
            $rows = $pdo->query('SELECT image FROM uploads WHERE is_demo = 1')->fetchAll();
            foreach ($rows as $row) {
                $path = UPLOAD_DIR . $row['image'];
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $deleted['uploads'] = $pdo->exec('DELETE FROM uploads WHERE is_demo = 1') ?: 0;
        }

        if (SchemaMigrator::tableExists('tasks')) {
            $deleted['tasks'] = $pdo->exec('DELETE FROM tasks WHERE is_demo = 1') ?: 0;
        }

        if (SchemaMigrator::tableExists('messages')) {
            $deleted['messages'] = $pdo->exec('DELETE FROM messages WHERE is_demo = 1') ?: 0;
        }

        if (SchemaMigrator::tableExists('notifications')) {
            $deleted['notifications'] = $pdo->exec('DELETE FROM notifications WHERE is_demo = 1') ?: 0;
        }

        if (SchemaMigrator::tableExists('users')) {
            $rows = $pdo->query("SELECT id, profile FROM users WHERE is_demo = 1 AND role = 'member'")->fetchAll();
            foreach ($rows as $row) {
                if (!empty($row['profile'])) {
                    $path = PROFILE_DIR . $row['profile'];
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
            }
            $deleted['members'] = $pdo->exec("DELETE FROM users WHERE is_demo = 1 AND role = 'member'") ?: 0;
        }

        return [
            'ok'      => true,
            'message' => 'Removed demo data only. Real members, uploads, tasks, and presentations were not affected.',
            'deleted' => $deleted,
        ];
    }

    /** @return array{ok:bool, message:string, deleted?:array<string,int>} */
    public static function cleanAll(): array
    {
        $pdo = Database::getConnection();
        $deleted = self::emptyDeleted();

        if (SchemaMigrator::tableExists('presentations')) {
            $rows = $pdo->query('SELECT filename FROM presentations')->fetchAll();
            foreach ($rows as $row) {
                $path = PRESENTATION_UPLOAD_DIR . $row['filename'];
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $deleted['presentations'] = $pdo->exec('DELETE FROM presentations') ?: 0;
        }

        if (SchemaMigrator::tableExists('uploads')) {
            $rows = $pdo->query('SELECT image FROM uploads')->fetchAll();
            foreach ($rows as $row) {
                $path = UPLOAD_DIR . $row['image'];
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $deleted['uploads'] = $pdo->exec('DELETE FROM uploads') ?: 0;
        }

        if (SchemaMigrator::tableExists('tasks')) {
            $deleted['tasks'] = $pdo->exec('DELETE FROM tasks') ?: 0;
        }

        if (SchemaMigrator::tableExists('messages')) {
            $deleted['messages'] = $pdo->exec('DELETE FROM messages') ?: 0;
        }

        if (SchemaMigrator::tableExists('notifications')) {
            $deleted['notifications'] = $pdo->exec('DELETE FROM notifications') ?: 0;
        }

        if (SchemaMigrator::tableExists('users')) {
            $rows = $pdo->query("SELECT id, profile FROM users WHERE role = 'member'")->fetchAll();
            foreach ($rows as $row) {
                if (!empty($row['profile'])) {
                    $path = PROFILE_DIR . $row['profile'];
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
            }
            $deleted['members'] = $pdo->exec("DELETE FROM users WHERE role = 'member'") ?: 0;
        }

        $total = array_sum($deleted);
        if ($total === 0) {
            return ['ok' => true, 'message' => 'No data to delete.', 'deleted' => $deleted];
        }

        return [
            'ok'      => true,
            'message' => 'Deleted all system data (admin account kept): '
                . $deleted['members'] . ' members, '
                . $deleted['uploads'] . ' images, '
                . $deleted['tasks'] . ' tasks, '
                . $deleted['presentations'] . ' presentations, '
                . $deleted['messages'] . ' messages, '
                . $deleted['notifications'] . ' notifications.',
            'deleted' => $deleted,
        ];
    }

    /** @return array{ok:bool, message:string, created?:int} */
    public static function seedPresentations(): array
    {
        $result = self::seedAll();
        if (!$result['ok']) {
            return $result;
        }
        return [
            'ok'      => true,
            'message' => $result['message'],
            'created' => $result['summary']['presentations'] ?? 0,
        ];
    }

    /** @return array{ok:bool, message:string, deleted?:int} */
    public static function removeDemoPresentations(): array
    {
        return self::removeDemo();
    }

    /** @return array{ok:bool, message:string, deleted?:int} */
    public static function cleanAllPresentations(): array
    {
        return self::cleanAll();
    }

    /** @return array{demo:int,total:int,real:int} */
    private static function entityCounts(string $table, string $scopeWhere = '1=1'): array
    {
        if (!SchemaMigrator::tableExists($table)) {
            return ['demo' => 0, 'total' => 0, 'real' => 0];
        }

        try {
            $pdo = Database::getConnection();
            $total = (int) $pdo->query("SELECT COUNT(*) FROM {$table} WHERE {$scopeWhere}")->fetchColumn();
            $demo = self::hasDemoColumn($table)
                ? (int) $pdo->query("SELECT COUNT(*) FROM {$table} WHERE is_demo = 1 AND {$scopeWhere}")->fetchColumn()
                : 0;

            return [
                'demo'  => $demo,
                'total' => $total,
                'real'  => max(0, $total - $demo),
            ];
        } catch (Throwable $e) {
            AppLog::exception($e, 'DemoData::entityCounts(' . $table . ')');
            return ['demo' => 0, 'total' => 0, 'real' => 0];
        }
    }

    private static function demoColumnsReady(): bool
    {
        return self::hasDemoColumn('users')
            && self::hasDemoColumn('uploads')
            && self::hasDemoColumn('tasks');
    }

    private static function hasDemoColumn(string $table): bool
    {
        if (!SchemaMigrator::tableExists($table)) {
            return false;
        }

        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, 'is_demo']);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    private static function adminId(): ?int
    {
        $id = Database::getConnection()
            ->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")
            ->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /** @return array<string,int> */
    private static function emptyDeleted(): array
    {
        return [
            'members'       => 0,
            'uploads'       => 0,
            'tasks'         => 0,
            'presentations' => 0,
            'messages'      => 0,
            'notifications' => 0,
        ];
    }

    private static function makeAvatar(string $path, string $initials, array $rgb): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $size = 256;
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, true);
        $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);

        $text = strtoupper($initials);
        $fontCandidates = [
            'C:\\Windows\\Fonts\\segoeui.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ];
        $font = null;
        foreach ($fontCandidates as $candidate) {
            if (is_file($candidate)) {
                $font = $candidate;
                break;
            }
        }

        if ($font !== null) {
            $fontSize = 84;
            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $tw = abs($bbox[2] - $bbox[0]);
            $th = abs($bbox[7] - $bbox[1]);
            $x = (int) (($size - $tw) / 2) - (int) $bbox[0];
            $y = (int) (($size + $th) / 2);
            imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $text);
        } else {
            imagestring($img, 5, (int) (($size - 20) / 2), (int) (($size - 10) / 2), $text, $white);
        }

        imagejpeg($img, $path, 92);
        imagedestroy($img);
    }

    private static function makePhoto(string $path, array $rgb): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $w = 800;
        $h = 600;
        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        $accent = imagecolorallocatealpha($img, 255, 255, 255, 100);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);
        imagefilledellipse($img, (int) ($w * 0.72), (int) ($h * 0.28), 220, 220, $accent);
        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    /** @return list<array{source:string,title:string,description:string,days_ahead:int}> */
    private static function demoPresentationCatalog(): array
    {
        return [
            [
                'source'      => 'demo.pptx',
                'title'       => '[Demo] Monthly Block Review — Pallanwala',
                'description' => 'Sample meeting deck for CMO review.',
                'days_ahead'  => 3,
            ],
            [
                'source'      => 'demo.ppt',
                'title'       => '[Demo] NHM Progress Report — Akhnoor',
                'description' => 'Health indicators and outreach summary.',
                'days_ahead'  => 7,
            ],
            [
                'source'      => 'demo.pdf',
                'title'       => '[Demo] Infrastructure Update — Bishnah',
                'description' => 'PHC building status and equipment slides.',
                'days_ahead'  => 10,
            ],
        ];
    }

    /** @return list<string> */
    private static function missingDemoDocFiles(): array
    {
        $missing = [];
        foreach (self::demoPresentationCatalog() as $sample) {
            $path = DEMO_DOCS_DIR . $sample['source'];
            if (!is_file($path)) {
                $missing[] = $sample['source'];
            }
        }

        return $missing;
    }

    /** @return array{filename:string,original_name:string,file_size:int}|null */
    private static function copyDemoDocToPresentations(string $sourceFilename): ?array
    {
        $sourcePath = DEMO_DOCS_DIR . $sourceFilename;
        if (!is_file($sourcePath)) {
            return null;
        }

        $ext = strtolower(pathinfo($sourceFilename, PATHINFO_EXTENSION));
        $storedName = 'demo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = PRESENTATION_UPLOAD_DIR . $storedName;

        if (!@copy($sourcePath, $destPath)) {
            return null;
        }

        return [
            'filename'      => $storedName,
            'original_name' => $sourceFilename,
            'file_size'     => (int) filesize($destPath),
        ];
    }
}
