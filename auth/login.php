<?php
/**
 * Login page
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

if (Auth::check()) {
    Helper::redirect(BASE_URL . 'index.php');
}

$error = '';

if (Helper::isPost()) {
    Csrf::requireValid();
    $username = trim((string) Helper::input('username', ''));
    $password = (string) Helper::input('password', '');
    $result = Auth::login($username, $password);

    if ($result['success']) {
        $role = $result['user']['role'];
        Helper::setFlash('success', 'Welcome back, ' . $result['user']['name'] . '!');
        if ($role === 'admin') {
            Helper::redirect(BASE_URL . 'admin/dashboard.php');
        }
        Helper::redirect(BASE_URL . 'member/dashboard.php');
    }
    $error = $result['message'];
}

$pageTitle = 'Sign In';
$hideSidebar = true;
$bodyClass = 'login-page';
require BASE_PATH . 'includes/header.php';
?>

<div class="login-wrap">
    <div class="login-panel">
        <div class="login-brand">
            <div class="brand-mark brand-mark-lg"><i class="bi bi-hospital"></i></div>
            <h1 class="login-title"><?= Helper::e(APP_NAME) ?></h1>
            <p class="login-org mb-1"><?= Helper::e(APP_TAGLINE) ?></p>
            <p class="login-subtitle mb-0">Sign in to manage images, tasks, and maps.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= Helper::e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="" class="login-form" autocomplete="on" novalidate>
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= Helper::e((string) Helper::input('username', '')) ?>"
                       required autofocus placeholder="Username">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group password-toggle-group">
                    <input type="password" class="form-control" id="password" name="password"
                           required placeholder="Password" autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary password-toggle-btn"
                            title="Show password" aria-label="Show password" aria-pressed="false"
                            data-target="password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg">
                Sign In
            </button>
        </form>
        <p class="login-hint">Admin demo: admin / Admin@123</p>
    </div>
</div>

<?php require BASE_PATH . 'includes/footer.php'; ?>
