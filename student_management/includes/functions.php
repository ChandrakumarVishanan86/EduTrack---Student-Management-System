<?php
/**
 * Shared helpers: escaping, URLs, JSON responses, CSRF, grading, autoloader.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db_connect.php';

// Autoload model classes from /includes/models/ClassName.php
spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/models/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

/** HTML-escape */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Absolute URL path inside the app: url('students') => /student_management/students */
function url(string $path = ''): string
{
    return APP_BASE . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Send a JSON response and stop. */
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Decoded JSON request body (for fetch() calls with application/json). */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

// ---------------------------------------------------------------- CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verify the CSRF token from header, form field or JSON body. */
function csrf_verify(?array $json = null): bool
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['csrf_token']
        ?? ($json['csrf_token'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

/** Abort API requests that fail the CSRF check. */
function require_csrf(?array $json = null): void
{
    if (!csrf_verify($json)) {
        json_response(['success' => false, 'message' => 'Security token expired. Please reload the page.'], 419);
    }
}

// ---------------------------------------------------------------- Grading
/** Single source of truth for letter grades (JS mirrors this in main.js). */
function grade_from_marks(float $marks): string
{
    return match (true) {
        $marks >= 90 => 'A+',
        $marks >= 80 => 'A',
        $marks >= 75 => 'B+',
        $marks >= 70 => 'B',
        $marks >= 65 => 'C+',
        $marks >= 60 => 'C',
        $marks >= 50 => 'D',
        default      => 'F',
    };
}

/** Grade point on a 4.0 scale: marks / 25 */
function grade_point(float $marks): float
{
    return round($marks / 25, 2);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}
