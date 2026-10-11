<?php
/**
 * admin/setup.php
 * First-run admin creation. Self-locks after one admin exists.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ------------------------------------------------------------
//  Self-lock: if any admin has a real password hash, redirect.
// ------------------------------------------------------------
$hasRealAdmin = false;
try {
    $row = $pdo->query("SELECT password_hash FROM admins LIMIT 1")->fetchColumn();
    if ($row && !str_contains($row, 'REPLACE_THIS')) {
        $hasRealAdmin = true;
    }
} catch (PDOException $e) {
    error_log('[SETUP CHECK] ' . $e->getMessage());
}

if ($hasRealAdmin) {
    redirect('/admin/index.php');
}

// ------------------------------------------------------------
//  Handle form submission
// ------------------------------------------------------------
$errors = [];
$done   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Rate limit: 5 attempts per 15 min per session
    if (!rate_limit('setup', 5, 900)) {
        $errors[] = 'Too many attempts. Try again later.';
    } else {
        $email    = trim((string)($_POST['email'] ?? ''));
        $name     = trim((string)($_POST['name'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($name === '' || strlen($name) > 100) {
            $errors[] = 'Enter your name (max 100 chars).';
        }
        if (strlen($password) < 10) {
            $errors[] = 'Password must be at least 10 characters.';
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain upper, lower, and a digit.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            try {
                // Replace the placeholder row (or insert a new one)
                $existing = $pdo->query("SELECT id FROM admins LIMIT 1")->fetchColumn();
                if ($existing) {
                    $stmt = $pdo->prepare(
                        "UPDATE admins SET email = ?, password_hash = ?, name = ? WHERE id = ?"
                    );
                    $stmt->execute([$email, $hash, $name, $existing]);
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO admins (email, password_hash, name) VALUES (?, ?, ?)"
                    );
                    $stmt->execute([$email, $hash, $name]);
                }
                $done = true;
            } catch (PDOException $e) {
                error_log('[SETUP SAVE] ' . $e->getMessage());
                $errors[] = 'Could not save admin. Check the logs.';
            }
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup — Darkest Custom Admin</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: grid; place-items: center;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    background: #EAF4FB; color: #374151; padding: 24px;
  }
  .card {
    width: 100%; max-width: 460px; background: #fff;
    border-radius: 16px; box-shadow: 0 12px 32px rgba(11,30,63,.12);
    padding: 40px 32px;
  }
  h1 {
    font-family: 'Playfair Display', Georgia, serif;
    color: #0B1E3F; margin: 0 0 6px; font-size: 28px;
    letter-spacing: -0.02em;
  }
  p.sub { color: #6B7280; margin: 0 0 28px; font-size: 14px; }
  label { display: block; font-size: 13px; font-weight: 600; color: #0B1E3F; margin: 16px 0 6px; }
  input[type=text], input[type=email], input[type=password] {
    width: 100%; min-height: 48px; padding: 0 14px;
    border: 1.5px solid #E5E7EB; border-radius: 10px;
    font-size: 15px; font-family: inherit; color: #0A0A0A;
    transition: border-color 150ms;
  }
  input:focus { outline: none; border-color: #4A9FE0; }
  button {
    width: 100%; min-height: 52px; margin-top: 24px;
    background: #4A9FE0; color: #fff; border: none; border-radius: 10px;
    font-size: 16px; font-weight: 600; cursor: pointer;
    transition: background 150ms;
  }
  button:hover { background: #12294F; }
  .errors {
    background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C;
    padding: 12px 16px; border-radius: 10px; font-size: 14px;
    margin-bottom: 16px;
  }
  .errors ul { margin: 0; padding-left: 18px; }
  .success {
    background: #F0FDF4; border: 1px solid #BBF7D0; color: #166534;
    padding: 16px; border-radius: 10px; font-size: 15px; text-align: center;
  }
  .success a { color: #166534; font-weight: 600; }
  .hint { font-size: 12px; color: #9CA3AF; margin-top: 6px; }
</style>
</head>
<body>
  <div class="card">
    <?php if ($done): ?>
      <h1>Setup complete</h1>
      <div class="success">
        Admin account created.<br><br>
        <a href="/admin/index.php">Log in →</a>
      </div>
    <?php else: ?>
      <h1>First-run setup</h1>
      <p class="sub">Create your admin account. This page locks itself after you finish.</p>

      <?php if ($errors): ?>
        <div class="errors">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <label for="name">Your name</label>
        <input type="text" id="name" name="name" required maxlength="100"
               value="<?= e($_POST['name'] ?? '') ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="150"
               value="<?= e($_POST['email'] ?? '') ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="10">
        <div class="hint">At least 10 characters, with upper, lower, and a digit.</div>

        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="10">

        <button type="submit">Create admin account</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>