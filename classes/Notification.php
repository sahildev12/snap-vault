<?php
/**
 * In-app notifications
 */

declare(strict_types=1);

class Notification
{
    public static function create(int $userId, string $type, string $title, ?string $body = null, ?string $link = null): int
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO notifications (user_id, type, title, body, link, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, 0, NOW())'
        );
        $stmt->execute([$userId, $type, $title, $body, $link]);
        $id = (int) Database::getConnection()->lastInsertId();
        Webhook::dispatch('notification.created', [
            'notification_id' => $id,
            'user_id'         => $userId,
            'type'            => $type,
            'title'           => $title,
            'body'            => $body,
            'link'            => $link,
        ]);
        return $id;
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function recent(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::getConnection()->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function markRead(int $userId, ?int $id = null): void
    {
        if ($id !== null) {
            $stmt = Database::getConnection()->prepare(
                'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$id, $userId]);
            return;
        }
        $stmt = Database::getConnection()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
    }
}
