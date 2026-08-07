<?php
/**
 * Member chat — conversation with admin
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$adminId = Message::primaryAdminId();
if ($adminId === null) {
    Helper::setFlash('danger', 'No admin available for chat.');
    Helper::redirect(BASE_URL . 'member/dashboard.php');
}

$with = (int) Helper::input('with', $adminId);
if ($with < 1) {
    $with = $adminId;
}
$peer = User::findById($with);
if (!$peer || ($peer['role'] ?? '') !== 'admin') {
    $peer = User::findById($adminId);
    $with = $adminId;
}

Message::markRead((int) Auth::id(), $with);

$pageTitle = 'Chat';
require BASE_PATH . 'includes/header.php';
?>

<div class="chat-layout chat-layout-single">
    <section class="chat-main card panel-card">
        <div class="chat-main-head">
            <img src="<?= Helper::e(Helper::profileUrl($peer['profile'] ?? null)) ?>" alt="" class="avatar-sm">
            <div>
                <div class="fw-semibold"><?= Helper::e($peer['name']) ?></div>
                <div class="small text-muted">Administrator</div>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages" data-peer="<?= (int) $with ?>" data-self="<?= (int) Auth::id() ?>"></div>
        <form class="chat-compose" id="chatForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="to_user_id" value="<?= (int) $with ?>">
            <textarea name="body" id="chatBody" class="form-control" rows="2" placeholder="Write a message…" required></textarea>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </section>
</div>

<?php
$extraScripts = '<script src="' . Helper::e(Helper::asset('assets/js/chat.js')) . '"></script>';
require BASE_PATH . 'includes/footer.php';
?>
