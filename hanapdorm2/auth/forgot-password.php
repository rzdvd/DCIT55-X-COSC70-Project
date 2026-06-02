<?php

session_start();

require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot-password.html');
    exit;
}

$email = sanitize_email($_POST['email'] ?? '');
$genericSuccess = 'If an account exists for that email, a reset link has been sent.';

if (!is_valid_email($email)) {
    redirect_with('forgot-password.html', 'Please enter a valid email address.');
}

$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    $token = bin2hex(random_bytes(32));

    $deleteOld = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
    $deleteOld->bind_param('s', $email);
    $deleteOld->execute();
    $deleteOld->close();

    $insert = $conn->prepare(
        'INSERT INTO password_resets (email, reset_token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
    );
    $insert->bind_param('ss', $email, $token);
    $insert->execute();
    $insert->close();

    send_reset_email($email, $token);
    
    // For localhost, also return the reset link in the redirect
    if (is_localhost()) {
        $resetLink = get_dev_reset_link($email);
        if ($resetLink) {
            redirect_with('forgot-password.html', null, $genericSuccess, $resetLink);
        }
    }
}

redirect_with('forgot-password.html', null, $genericSuccess);
