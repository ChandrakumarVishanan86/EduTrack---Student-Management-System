<?php
/**
 * PDO connection (singleton).
 *
 * Usage:   $pdo = Database::getConnection();
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Database
{
    private static ?PDO $instance = null;

    // Prevent instantiation / cloning - this is a static factory.
    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
                ]);
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                $msg = APP_DEBUG
                    ? 'Database connection failed: ' . htmlspecialchars($e->getMessage())
                    : 'Database connection failed.';
                // JSON for AJAX callers, plain text for pages
                if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                    || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                } else {
                    echo '<h3 style="font-family:sans-serif">' . $msg . '</h3>'
                       . '<p style="font-family:sans-serif">Did you import <code>sql/database.sql</code> in phpMyAdmin?</p>';
                }
                exit;
            }
        }
        return self::$instance;
    }
}

/** Convenience helper */
function db(): PDO
{
    return Database::getConnection();
}
