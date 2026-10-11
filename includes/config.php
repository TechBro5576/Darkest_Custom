<?php
declare(strict_types=1);

/**
 * includes/config.php
 *
 * Single source of truth for credentials and app-wide constants.
 * NEVER commit this file to Git. NEVER expose it in /public.
 */

// ============================================================
//  ENVIRONMENT
// ============================================================

define('APP_DEBUG', true);
define('APP_URL',   'http://localhost/darkest-custom');
define('APP_ENV',   'local');

// ============================================================
//  DATABASE
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'dark');
define('DB_USER', 'root');
define('DB_PASS', '');

// ============================================================
//  PAYSTACK
// ============================================================

define('PAYSTACK_PUBLIC_KEY', '');
define('PAYSTACK_SECRET_KEY', '');

// ============================================================
//  SECURITY
// ============================================================

define('APP_SECRET', 'CHANGE-ME-9f2c7a1e4b8d3f6a0e5c2b7d4a8f1e3c6b9d2a5f8e1c4b7d0a3f6e9c2b5d8a1f4');

define('MAX_UPLOAD_BYTES', 8 * 1024 * 1024);

define('ALLOWED_MIMES', [
    'image/png'     => 'png',
    'image/jpeg'    => 'jpg',
    'image/webp'    => 'webp',
    'image/svg+xml' => 'svg',
]);

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_WINDOW_SECONDS', 900);

// ============================================================
//  PATHS
// ============================================================

define('ROOT_PATH',    dirname(__DIR__));
define('UPLOAD_PATH',  ROOT_PATH . '/uploads');
define('UPLOAD_URL',   APP_URL . '/uploads');
define('DESIGN_PATH',  UPLOAD_PATH . '/designs');
define('PREVIEW_PATH', UPLOAD_PATH . '/previews');
define('DESIGN_URL',   UPLOAD_URL . '/designs');
define('PREVIEW_URL',  UPLOAD_URL . '/previews');

// ============================================================
//  SESSIONS
// ============================================================

define('SESSION_NAME',     'darkest_sess');
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);

// ============================================================
//  ERROR HANDLING
// ============================================================

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/../darkest-error.log');
}

// ============================================================
//  SECURITY HEADERS
// ============================================================

if (APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header_remove('X-Powered-By');

// ============================================================
//  SESSION BOOTSTRAP
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    $secure = (APP_ENV === 'production');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}