<?php
// Update these settings for your local MySQL installation.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'copy_print_management');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10 MB

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lookup lists used by forms, validation and reports.
const USER_TYPES  = ['teacher' => 'Teacher', 'ta' => 'TA', 'student' => 'Student'];
const COLOR_MODES = ['color' => 'Color', 'bw' => 'B/W'];
const JOB_TYPES   = ['copy' => 'Copy', 'print' => 'Print', 'scan' => 'Scan'];

function user_type_label(?string $t): string { return USER_TYPES[$t ?? ''] ?? '-'; }
function color_label(?string $c): string { return COLOR_MODES[$c ?? ''] ?? '-'; }

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function require_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: index.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
            http_response_code(400);
            exit('Invalid form token. Refresh and try again.');
        }
    }
}
