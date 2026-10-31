<?php
/**
 * Global application settings.
 * Edit DB_* values if your WAMP MySQL user/password differ from the defaults.
 */
declare(strict_types=1);

// ---- Database (WAMP default: user "root", empty password) -------------
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');          // change to 3307 if MariaDB/MySQL runs on that port in your WAMP
define('DB_NAME', 'student_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- Application ------------------------------------------------------
define('APP_NAME', 'EduTrack SMS');
define('APP_BASE', '/student_management');   // URL path of the project (no trailing slash)
define('APP_ROOT', dirname(__DIR__));         // filesystem path of the project
define('APP_TIMEZONE', 'Asia/Colombo');
define('DEFAULT_STUDENT_PASSWORD', 'student123');

date_default_timezone_set(APP_TIMEZONE);

// Set to false on a production server
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '0');   // errors never leak into JSON responses
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}
