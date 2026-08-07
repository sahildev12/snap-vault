<?php
/**
 * Admin chat — pick a member and message them
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$with = (int) Helper::input('with', 0);
$inbox = Message::adminInbox();
$peer = $with > 0 ? User::findById($with) : null;
if ($peer && ($peer['role'] ?? '') !== 'member') {
    $peer = null;
    $with = 0;
}

if ($with > 0) {
    Message::markRead((int) Auth::id(), $with);
}

$pageTitle = 'Chat';
require BASE_PATH . 'includes/header.php';
?>

<div class="chat-layout">
    <aside class="chat-sidebar card panel-card">
        <div class="card-header bg-transparent border-0">
            <h2 class="h6 mb-0">Members</h2>
        </div>
        <div class="chat-people">
            <?php if ($inbox === []): ?>
                <div class="p-3 text-muted small">No members yet.</div>
            <?php else: ?>
                <?php foreach ($inbox as $row): ?>
                    <?php $hasUnread = (int) ($row['unread'] ?? 0) > 0; ?>
                    <a href="?with=<?= (int) $row['id'] ?>"
                       class="chat-person<?= $with === (int) $row['id'] ? ' is-active' : '' ?><?= $hasUnread ? ' has-unread' : '' ?>">
                        <span class="chat-avatar-wrap">
                            <img src="<?= Helper::e(Helper::profileUrl($row['profile'] ?? null)) ?>" alt="" class="avatar-sm">
                            <?php if ($hasUnread): ?>
                                <span class="chat-unread-dot" title="<?= (int) $row['unread'] ?> unread"></span>
                            <?php endif; ?>
                        </span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="chat-person-name">
                                <?= Helper::e($row['name']) ?>
                            </div>
                            <div class="chat-person-preview text-muted">
                                <?= Helper::e(Helper::truncate((string) ($row['last_body'] ?? 'No messages yet'), 42)) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <section class="chat-main card panel-card">
        <?php if (!$peer): ?>
            <div class="empty-state py-5">
                <i class="bi bi-chat-dots"></i>
                <p>Select a member to start chatting.</p>
            </div>
        <?php else: ?>
            <div class="chat-main-head">
                <img src="<?= Helper::e(Helper::profileUrl($peer['profile'] ?? null)) ?>" alt="" class="avatar-sm">
                <div>
                    <div class="fw-semibold"><?= Helper::e($peer['name']) ?></div>
                    <div class="small text-muted">@<?= Helper::e($peer['username']) ?></div>
                </div>
            </div>
            <div class="chat-messages" id="chatMessages" data-peer="<?= (int) $with ?>" data-self="<?= (int) Auth::id() ?>"></div>
            <form class="chat-compose" id="chatForm">
                <?= Csrf::field() ?>
                <input type="hidden" name="to_user_id" value="<?= (int) $with ?>">
                <textarea name="body" id="chatBody" class="form-control" rows="2" placeholder="Write a message…" required></textarea>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        <?php endif; ?>
    </section>
</div>

<?php
$extraScripts = '';
if ($peer) {
    $extraScripts = '<script src="' . Helper::e(Helper::asset('assets/js/chat.js')) . '"></script>';
}
require BASE_PATH . 'includes/footer.php';
?>
