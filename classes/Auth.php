<?php
/**
 * Authentication & session helpers
 */

declare(strict_types=1);

class Auth
{
    public static function login(string $username, string $password): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }

        $user = User::findByUsername($username);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        if ((int) $user['status'] !== 1) {
            return ['success' => false, 'message' => 'Your account has been disabled. Contact admin.'];
        }

        session_regenerate_id(true);

        $_SESSION['user_id']  = (int) $user['id'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['name']     = $user['name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile']  = $user['profile'];

        // Refresh the session cookie so the long lifetime applies after login
        if (defined('SESSION_LIFETIME') && SESSION_LIFETIME > 0) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                [
                    'expires' => time() + SESSION_LIFETIME,
                    'path' => $params['path'] ?: '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => (bool) ($params['secure'] ?? false),
                    'httponly' => true,
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        return ['success' => true, 'user' => $user];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isMember(): bool
    {
        return self::role() === 'member';
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'       => (int) $_SESSION['user_id'],
            'role'     => $_SESSION['role'],
            'name'     => $_SESSION['name'],
            'username' => $_SESSION['username'],
            'profile'  => $_SESSION['profile'] ?? null,
        ];
    }

    public static function refreshSession(): void
    {
        $id = self::id();
        if ($id === null) {
            return;
        }
        $user = User::findById($id);
        if (!$user) {
            self::logout();
            return;
        }
        $_SESSION['name']     = $user['name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile']  = $user['profile'];
        $_SESSION['role']     = $user['role'];
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Helper::setFlash('warning', 'Please log in to continue.');
            Helper::redirect(BASE_URL . 'auth/login.php');
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        if (self::role() !== $role) {
            Helper::setFlash('danger', 'You do not have permission to access that page.');
            if (self::isAdmin()) {
                Helper::redirect(BASE_URL . 'admin/dashboard.php');
            }
            Helper::redirect(BASE_URL . 'member/dashboard.php');
        }
    }
}
