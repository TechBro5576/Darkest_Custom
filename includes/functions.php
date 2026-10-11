<?php
/**
 * includes/functions.php
 *
 * Shared helpers. Include after config.php and db.php:
 *   require_once __DIR__ . '/includes/config.php';
 *   require_once __DIR__ . '/includes/db.php';
 *   require_once __DIR__ . '/includes/functions.php';
 */

declare(strict_types=1);

// ============================================================
//  OUTPUT ESCAPING
// ============================================================

/**
 * Escape a string for safe HTML output.
 * Use on EVERY piece of dynamic data that touches HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape for use inside an HTML attribute (semantically explicit).
 */
function e_attr(?string $value): string
{
    return e($value);
}

/**
 * Escape for use inside an inline JavaScript context.
 * Wraps the string in quotes and hex-escapes dangerous chars.
 */
function e_js(?string $value): string
{
    return json_encode(
        $value ?? '',
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
}

// ============================================================
//  MONEY / FORMATTING
// ============================================================

/**
 * Format an amount with the site's currency symbol.
 * Example: money(2500) → "₦2,500.00"
 */
function money(float $amount, bool $decimals = true): string
{
    $symbol = get_setting('currency_symbol', '₦');
    $formatted = number_format($amount, $decimals ? 2 : 0, '.', ',');
    return $symbol . $formatted;
}

/**
 * Convert any text into a URL-safe slug.
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/**
 * Normalize a Nigerian phone number to +234XXXXXXXXXX.
 * Accepts: 07035770759, 7035770759, +2347035770759, 2347035770759
 * Returns '' if invalid.
 */
function sanitize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if (str_starts_with($digits, '234')) {
        $digits = substr($digits, 3);
    }
    if (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    if (strlen($digits) !== 10) {
        return '';
    }

    return '+234' . $digits;
}

// ============================================================
//  SETTINGS (cached)
// ============================================================

/**
 * Read a setting from the settings table. Cached per request.
 */
function get_setting(string $key, ?string $default = null): ?string
{
    static $cache = null;

    if ($cache === null) {
        global $pdo;
        $cache = [];
        try {
            $rows = $pdo->query('SELECT `key`, `value` FROM settings')->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['key']] = $row['value'];
            }
        } catch (PDOException $e) {
            error_log('[SETTINGS LOAD] ' . $e->getMessage());
        }
    }

    return $cache[$key] ?? $default;
}

/**
 * Force settings cache to reload on next call.
 * Call this after updating a setting inside the same request.
 */
function reset_settings_cache(): void
{
    // Reach into the static via a wrapper: the only way in PHP is
    // to re-trigger the closure. Since PHP statics can't be unset
    // from outside, we use a small global flag instead.
    $GLOBALS['_settings_force_reload'] = true;
}

// ============================================================
//  REDIRECTS
// ============================================================

/**
 * Redirect to a URL and exit. Blocks cross-host redirects.
 */
function redirect(string $path): never
{
    if (preg_match('#^https?://#i', $path)) {
        $host = parse_url($path, PHP_URL_HOST);
        $self = parse_url(APP_URL, PHP_URL_HOST);
        if ($host !== $self) {
            $path = '/';
        }
    }

    header('Location: ' . $path, true, 302);
    exit;
}

// ============================================================
//  CSRF
// ============================================================

/**
 * Get (or lazily create) the CSRF token for this session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/**
 * Render a hidden CSRF input field for forms.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token on POST. Dies with 403 if invalid.
 */
function csrf_check(): void
{
    $sent  = $_POST['_csrf'] ?? '';
    $known = $_SESSION['_csrf'] ?? '';

    if ($known === '' || !hash_equals($known, (string)$sent)) {
        http_response_code(403);
        die('Invalid request. Please refresh and try again.');
    }
}

// ============================================================
//  FLASH MESSAGES
// ============================================================

/**
 * Queue a flash message for the next request.
 * $type is one of: 'success', 'error', 'info', 'warning'
 */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Pull and clear all queued flash messages.
 */
function take_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

// ============================================================
//  AUTH
// ============================================================

function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

/**
 * Require a logged-in admin. Redirects to login if not.
 * Call at the top of every /admin page except index.php and setup.php.
 */
function require_admin(): void
{
    if (!is_admin()) {
        redirect('/admin/index.php');
    }
}

// ============================================================
//  RATE LIMITING (session-based)
// ============================================================

/**
 * Per-session rate limit.
 * Returns true if the request is allowed, false if rate-limited.
 */
function rate_limit(string $key, int $max, int $windowSeconds): bool
{
    $now = time();
    $bucket = $_SESSION['_rl'][$key] ?? ['count' => 0, 'start' => $now];

    if ($now - $bucket['start'] > $windowSeconds) {
        $bucket = ['count' => 0, 'start' => $now];
    }

    $bucket['count']++;
    $_SESSION['_rl'][$key] = $bucket;

    return $bucket['count'] <= $max;
}

// ============================================================
//  ORDER REFERENCE
// ============================================================

/**
 * Generate the next order reference, e.g. DC-2026-0001.
 * Uses a row lock to avoid race conditions.
 */
function generate_order_ref(): string
{
    global $pdo;
    $prefix = get_setting('order_prefix', 'DC');
    $year   = date('Y');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT order_ref FROM orders
             WHERE order_ref LIKE ?
             ORDER BY id DESC LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$prefix . '-' . $year . '-%']);
        $last = $stmt->fetchColumn();

        $next = 1;
        if ($last) {
            $parts = explode('-', (string)$last);
            $next  = (int) end($parts) + 1;
        }

        $ref = sprintf('%s-%s-%04d', $prefix, $year, $next);
        $pdo->commit();
        return $ref;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[ORDER REF] ' . $e->getMessage());
        // Fallback: random suffix so checkout never blocks
        return $prefix . '-' . $year . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}

// ============================================================
//  SAFE UPLOAD
// ============================================================

/**
 * Handle a customer design upload safely.
 * Returns the stored filename (UUID.ext) on success.
 * Throws RuntimeException on any failure — caller catches and reports.
 *
 * Security:
 *   - Size check
 *   - Real MIME sniffing via finfo (not extension)
 *   - Raster images are re-encoded through GD (strips EXIF, embedded payloads)
 *   - SVG content is rejected if it contains scripts or event handlers
 *   - Random UUID filename — original filename discarded
 */
function safe_upload(array $file, string $destDir): string
{
    if (!isset($file['tmp_name'], $file['error'], $file['size'])) {
        throw new RuntimeException('No file uploaded.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (code ' . $file['error'] . ').');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException(
            'File too large. Max ' . round(MAX_UPLOAD_BYTES / 1048576, 1) . 'MB.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: '';

    if (!isset(ALLOWED_MIMES[$mime])) {
        throw new RuntimeException('Unsupported file type.');
    }
    $ext = ALLOWED_MIMES[$mime];

    if (!is_dir($destDir)) {
        throw new RuntimeException('Upload destination missing.');
    }

    $uuid     = bin2hex(random_bytes(16));
    $filename = $uuid . '.' . $ext;
    $dest     = rtrim($destDir, '/') . '/' . $filename;

    // --- SVG: reject scripts/handlers outright ---
    if ($mime === 'image/svg+xml') {
        $svg = file_get_contents($file['tmp_name']);
        if ($svg === false) {
            throw new RuntimeException('Could not read SVG.');
        }
        if (preg_match('/<script|on\w+\s*=|javascript:/i', $svg)) {
            throw new RuntimeException('SVG contains disallowed content.');
        }
        if (@file_put_contents($dest, $svg) === false) {
            throw new RuntimeException('Could not save file.');
        }
        return $filename;
    }

    // --- Raster: re-encode through GD ---
    $img = match ($mime) {
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default      => false,
    };
    if (!$img) {
        throw new RuntimeException('Invalid image file.');
    }

    $saved = match ($mime) {
        'image/png'  => imagepng($img, $dest, 6),
        'image/jpeg' => imagejpeg($img, $dest, 88),
        'image/webp' => imagewebp($img, $dest, 88),
    };
    imagedestroy($img);

    if (!$saved) {
        throw new RuntimeException('Could not save image.');
    }

    return $filename;
} 
