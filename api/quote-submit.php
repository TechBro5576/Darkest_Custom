<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

csrf_check();

if (!rate_limit('quote', 5, 600)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many requests']);
    exit;
}

$name     = trim((string)($_POST['name']     ?? ''));
$phone    = sanitize_phone((string)($_POST['phone'] ?? ''));
$interest = trim((string)($_POST['interest'] ?? ''));
$quantity = trim((string)($_POST['quantity'] ?? ''));
$message  = trim((string)($_POST['message']  ?? ''));

if ($name === '' || $phone === '' || $interest === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $pdo->prepare(
        "INSERT INTO quote_requests (name, phone, interest, quantity, message, ip, user_agent, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    )->execute([
        $name,
        $phone,
        $interest,
        $quantity !== '' ? $quantity : null,
        $message  !== '' ? $message  : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
} catch (PDOException $e) {
    error_log('[QUOTE] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save']);
    exit;
}

echo json_encode(['ok' => true]);