<?php

session_start();

require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = sanitize_email($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? '';

if ($firstName === '' || $lastName === '') {
    redirect_with('register.html', 'First name and last name are required.');
}

if (!is_valid_email($email)) {
    redirect_with('register.html', 'Please enter a valid email address.');
}

if (!in_array($role, ['student', 'dorm_owner'], true)) {
    redirect_with('register.html', 'Please select whether you are a student or dorm owner.');
}

if (strlen($password) < 6) {
    redirect_with('register.html', 'Password must be at least 6 characters.');
}

if ($password !== $confirmPassword) {
    redirect_with('register.html', 'Passwords do not match.');
}

$check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    redirect_with('register.html', 'An account with this email already exists.');
}

$passwordHash = hash_password($password);

$insert = $conn->prepare(
    'INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
);
$insert->bind_param('sssss', $firstName, $lastName, $email, $passwordHash, $role);

if (!$insert->execute()) {
    $insert->close();
    redirect_with('register.html', 'Registration failed. Please try again.');
}

$userId = (int) $insert->insert_id;
$insert->close();

set_user_session([
    'id' => $userId,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'role' => $role,
]);

header('Location: ' . login_redirect_for_role($role));
exit;
