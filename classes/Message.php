<?php
/**
 * Direct messages (admin ↔ member)
 */

declare(strict_types=1);

class Message
{
    public static function send(int $fromId, int $toId, string $body): int
    {
        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Message cannot be empty.');
        }

        $stmt = Database::getConnection()->prepare(
            'INSERT INTO messages (from_user_id, to_user_id, body, created_at)
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$fromId, $toId, $body]);
        $id = (int) Database::getConnection()->lastInsertId();

        $from = User::findById($fromId);
        $to = User::findById($toId);
        $chatPath = (($to['role'] ?? '') === 'admin') ? 'admin/chat.php' : 'member/chat.php';
        Notification::create(
            $toId,
            'message.created',
            'New message',
            ($from['name'] ?? 'Someone') . ': ' . Helper::truncate($body, 80),
            BASE_URL . $chatPath . '?with=' . $fromId
        );
        Webhook::dispatch('message.created', [
            'message_id' => $id,
            'from'       => $fromId,
            'to'         => $toId,
            'preview'    => Helper::truncate($body, 120),
        ]);

        return $id;
    }

    public static function thread(int $userA, int $userB, int $afterId = 0, int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        $params = [$userA, $userB, $userB, $userA];
        $sql = 'SELECT m.*,
                       fu.name AS from_name, tu.name AS to_name
                FROM messages m
                INNER JOIN users fu ON fu.id = m.from_user_id
                INNER JOIN users tu ON tu.id = m.to_user_id
                WHERE ((m.from_user_id = ? AND m.to_user_id = ?)
                    OR (m.from_user_id = ? AND m.to_user_id = ?))';
        if ($afterId > 0) {
            $sql .= ' AND m.id > ?';
            $params[] = $afterId;
        }
        $sql .= " ORDER BY m.created_at ASC, m.id ASC LIMIT {$limit}";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function markRead(int $readerId, int $peerId): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE messages
             SET read_at = NOW()
             WHERE to_user_id = ? AND from_user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$readerId, $peerId]);
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function unreadCountWith(int $userId, int $peerId): int
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) FROM messages
             WHERE to_user_id = ? AND from_user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId, $peerId]);
        return (int) $stmt->fetchColumn();
    }

    /** Conversations for admin: all members with last message meta */
    public static function adminInbox(): array
    {
        $members = Database::getConnection()->query(
            "SELECT id, name, username, profile, status FROM users WHERE role = 'member' ORDER BY name ASC"
        )->fetchAll();

        $adminIds = Database::getConnection()->query(
            "SELECT id FROM users WHERE role = 'admin'"
        )->fetchAll(PDO::FETCH_COLUMN);
        $adminIds = array_map('intval', $adminIds ?: []);
        if ($adminIds === []) {
            return $members;
        }

        foreach ($members as &$m) {
            $mid = (int) $m['id'];
            $stmt = Database::getConnection()->prepare(
                "SELECT body, created_at, from_user_id, to_user_id
                 FROM messages
                 WHERE (from_user_id = ? AND to_user_id IN (" . implode(',', $adminIds) . "))
                    OR (to_user_id = ? AND from_user_id IN (" . implode(',', $adminIds) . "))
                 ORDER BY created_at DESC, id DESC
                 LIMIT 1"
            );
            $stmt->execute([$mid, $mid]);
            $last = $stmt->fetch() ?: null;
            $m['last_body'] = $last['body'] ?? null;
            $m['last_at'] = $last['created_at'] ?? null;

            $unreadStmt = Database::getConnection()->prepare(
                "SELECT COUNT(*) FROM messages
                 WHERE from_user_id = ?
                   AND to_user_id IN (" . implode(',', $adminIds) . ")
                   AND read_at IS NULL"
            );
            $unreadStmt->execute([$mid]);
            $m['unread'] = (int) $unreadStmt->fetchColumn();
        }
        unset($m);

        usort($members, static function ($a, $b) {
            $aAt = $a['last_at'] ?? '';
            $bAt = $b['last_at'] ?? '';
            if ($aAt === $bAt) {
                return strcasecmp((string) $a['name'], (string) $b['name']);
            }
            if ($aAt === '') {
                return 1;
            }
            if ($bAt === '') {
                return -1;
            }
            return strcmp($bAt, $aAt);
        });

        return $members;
    }

    public static function primaryAdminId(): ?int
    {
        $id = Database::getConnection()
            ->query("SELECT id FROM users WHERE role = 'admin' AND status = 1 ORDER BY id ASC LIMIT 1")
            ->fetchColumn();
        return $id !== false ? (int) $id : null;
    }
}
