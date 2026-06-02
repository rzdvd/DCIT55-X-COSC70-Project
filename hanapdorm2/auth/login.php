<?php

if (!empty($_POST['remember_me'])) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
    ]);
}

session_start();

require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = sanitize_email($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!is_valid_email($email) || $password === '') {
    redirect_with('login.html', 'Please enter a valid email and password.');
}

$stmt = $conn->prepare(
    'SELECT id, first_name, last_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !verify_password($password, $user['password_hash'], $conn, (int) $user['id'])) {
    redirect_with('login.html', 'Invalid email or password.');
}

set_user_session($user);

header('Location: ' . login_redirect_for_role($user['role']));
exit;
