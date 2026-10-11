<?php
/**
 * includes/db.php
 * PDO connection to the `dark` database.
 * Include this once at the top of any page that needs the DB.
 * Requires config.php to be loaded first.
 */

declare(strict_types=1);

// Config must already be loaded by the entry script.
// If someone includes db.php directly, bail out.
if (!defined('DB_HOST')) {
    http_response_code(500);
    die('Configuration not loaded.');
}

// ------------------------------------------------------------
//  Build DSN
// ------------------------------------------------------------
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    DB_HOST,
    DB_PORT,
    DB_NAME
);

// ------------------------------------------------------------
//  Driver options — locked down
// ------------------------------------------------------------
$options = [
    // Throw exceptions on error — never silent failures
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    // Associative arrays by default
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // Real prepared statements — not emulated. Critical for security.
    PDO::ATTR_EMULATE_PREPARES   => false,

    // Disable multi-statement execution (blocks stacked-query attacks)
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,

    // No persistent connections — shared hosting hates them
    PDO::ATTR_PERSISTENT         => false,

    // Force UTF-8 on the connection (belt + braces with charset in DSN)
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];

// ------------------------------------------------------------
//  Connect
// ------------------------------------------------------------
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Force strict SQL mode — reject malformed data instead of silently truncating
    $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");

    // WAT (UTC+1) — matches Nigerian business hours
    $pdo->exec("SET SESSION time_zone = '+01:00'");

} catch (PDOException $e) {
    // Log the real error, show a generic message
    error_log('[DB CONNECT ERROR] ' . $e->getMessage());
    http_response_code(500);

    if (defined('APP_DEBUG') && APP_DEBUG === true) {
        die('DB connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    die('We are having a temporary issue. Please try again in a moment.');
} 
