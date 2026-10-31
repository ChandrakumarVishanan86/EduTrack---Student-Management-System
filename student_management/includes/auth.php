<?php
/**
 * Authentication & role-based access control (PHP sessions).
 *
 *   require_once '../../includes/auth.php';
 *   Auth::guard(['admin', 'lecturer']);          // page
 *   Auth::guard(['admin'], json: true);          // AJAX endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_name('SMSSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => APP_BASE . '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** Dashboard URL for a given role. */
    public static function homeFor(string $role): string
    {
        return match ($role) {
            'admin'    => 'dashboard/admin',
            'lecturer' => 'dashboard/lecturer',
            default    => 'dashboard/student',
        };
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::check() && in_array($_SESSION['user']['role'], $roles, true);
    }

    /**
     * Ensure a logged-in user with one of the roles.
     * Pages redirect; JSON endpoints answer 401 / 403.
     */
    public static function guard(array $roles = [], bool $json = false): array
    {
        if (!self::check()) {
            if ($json) {
                json_response(['success' => false, 'message' => 'Session expired. Please sign in again.'], 401);
            }
            redirect('login');
        }
        $user = self::user();
        if ($roles && !in_array($user['role'], $roles, true)) {
            if ($json) {
                json_response(['success' => false, 'message' => 'You do not have permission for this action.'], 403);
            }
            flash('danger', 'You do not have permission to open that page.');
            redirect(self::homeFor($user['role']));
        }
        return $user;
    }

    /** Seconds remaining on a login lock-out (0 = not locked). */
    public static function lockedFor(): int
    {
        $until = $_SESSION['login_lock_until'] ?? 0;
        return max(0, $until - time());
    }

    /** Verify credentials. Returns the user row or null. */
    public static function attempt(string $username, string $password): ?array
    {
        if (self::lockedFor() > 0) {
            return null;
        }

        $stmt = db()->prepare('SELECT * FROM users WHERE username = :u AND is_active = 1 LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password'])) {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= self::MAX_ATTEMPTS) {
                $_SESSION['login_lock_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['login_fails'] = 0;
            }
            return null;
        }

        // Upgrade hash transparently if PHP's default algorithm changed
        if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
            $upd = db()->prepare('UPDATE users SET password = :p WHERE id = :id');
            $upd->execute([':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $row['id']]);
        }

        session_regenerate_id(true);          // prevent session fixation
        unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
        $_SESSION['user'] = [
            'id'         => (int) $row['id'],
            'username'   => $row['username'],
            'full_name'  => $row['full_name'],
            'role'       => $row['role'],
            'student_id' => $row['student_id'] !== null ? (int) $row['student_id'] : null,
        ];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        db()->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')->execute([':id' => $row['id']]);
        return $_SESSION['user'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}

Auth::start();
