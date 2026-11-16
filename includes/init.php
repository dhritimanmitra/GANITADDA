<?php
declare(strict_types=1);
if (defined('GA_INIT')) { return; }
define('GA_INIT', true);
define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Kolkata');

if (!is_file(__DIR__ . '/config.php')) {
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $base = preg_replace('#/admin$#', '', $base);
    header('Location: ' . $base . '/install.php');
    exit;
}
require_once __DIR__ . '/config.php';

ini_set('display_errors', DEBUG_MODE ? '1' : '0');
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $e) {
    error_log('[GanitAdda] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo DEBUG_MODE ? '<pre>' . htmlspecialchars((string)$e) . '</pre>' : '<h1>Something went wrong</h1><p>Please try again later.</p>';
    exit;
});

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('GANITSESS');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
if (isset($_SESSION['last_seen']) && time() - (int)$_SESSION['last_seen'] > 7200) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_seen'] = time();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
