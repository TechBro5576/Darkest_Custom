 <?php
/**
 * includes/auth.php
 *
 * Admin authentication & authorization.
 * Include AFTER config.php, db.php, and functions.php.
 *
 * Provides:
 *   - admin_login($email, $password)  : attempt login
 *   - admin_logout()                  : destroy session safely
 *   - current_admin()                 : fetch the logged-in admin row (cached)
 *   - require_admin()                 : guard (also lives in functions.php — this one wins)
 */

declare(strict_types=1);

// Ensure dependencies are loaded
if (!defined('DB_HOST') || !function_exists('csrf_check')) {
    http_response_code(500);
    die('auth.php requires config.php, db.php, and functions.php to be loaded first.');
}

// ============================================================
//  LOGIN
// ============================================================

/**
 * Attempt to log in an admin.
 * Returns true on success, false on failure.
 * Never reveals whether the email or password was wrong.
 */
function admin_login(string $email, string $password): bool
{
    global $pdo;

    $email = strtolower(trim($email));

    // --------------------------------------------------------
    //  Fetch admin by email
    // --------------------------------------------------------
    try {
        $stmt = $pdo->prepare(
            'SELECT id, email, password_hash, name FROM admins WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('[LOGIN QUERY] ' . $e->getMessage());
        return false;
    }

    // --------------------------------------------------------
    //  Constant-time defense against user enumeration.
    //  If admin not found, still run password_verify against a
    //  dummy hash so response time is identical.
    // --------------------------------------------------------
    if (!$admin) {
        password_verify($password, '$2y$12$' . str_repeat('x', 53));
        return false;
    }

    // --------------------------------------------------------
    //  Verify password
    // --------------------------------------------------------
    if (!password_verify($password, $admin['password_hash'])) {
        return false;
    }

    // --------------------------------------------------------
    //  Rehash if cost has changed (future-proofing)
    // --------------------------------------------------------
    if (password_needs_rehash($admin['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
        try {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([$newHash, $admin['id']]);
        } catch (PDOException $e) {
            // Non-fatal — log and continue
            error_log('[REHASH] ' . $e->getMessage());
        }
    }

    // --------------------------------------------------------
    //  Success — regenerate session, set identity
    // --------------------------------------------------------
    session_regenerate_id(true);

    $_SESSION['admin_id']    = (int) $admin['id'];
    $_SESSION['admin_name']  = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['_created']    = time();

    // Update last_login_at
    try {
        $pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')
            ->execute([$admin['id']]);
    } catch (PDOException $e) {
        error_log('[LOGIN UPDATE] ' . $e->getMessage());
    }

    return true;
}

// ============================================================
//  LOGOUT
// ============================================================

/**
 * Fully destroy the session — cookies and all.
 */
function admin_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

// ============================================================
//  CURRENT ADMIN
// ============================================================

/**
 * Fetch the currently logged-in admin from the DB. Cached per request.
 * Returns null if not logged in or the account vanished.
 */
function current_admin(): ?array
{
    static $cached = false;
    static $admin  = null;

    if ($cached) {
        return $admin;
    }
    $cached = true;

    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare(
            'SELECT id, email, name, created_at, last_login_at
             FROM admins WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$_SESSION['admin_id']]);
        $row = $stmt->fetch();

        if (!$row) {
            // Admin was deleted mid-session — kick them out
            admin_logout();
            return null;
        }

        $admin = $row;
        return $admin;

    } catch (PDOException $e) {
        error_log('[CURRENT ADMIN] ' . $e->getMessage());
        return null;
    }
}

// ============================================================
//  GUARD
// ============================================================

/**
 * Require an authenticated admin. Redirects to login if missing.
 * Overrides the simpler guard in functions.php — this version is stricter.
 */
function require_admin(): void
{
    $admin = current_admin();
    if ($admin === null) {
        flash('error', 'Please log in to continue.');
        redirect('/admin/index.php');
    }
}

/**
 * Require admin AND a valid CSRF on POST requests.
 * Call at the top of every admin POST handler.
 */
function require_admin_post(): void
{
    require_admin();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
    }
}
