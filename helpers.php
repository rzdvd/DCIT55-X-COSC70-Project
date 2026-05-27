<?php

function redirect_with(string $page, ?string $error = null, ?string $success = null): void
{
    $params = [];
    if ($error !== null && $error !== '') {
        $params['error'] = $error;
    }
    if ($success !== null && $success !== '') {
        $params['success'] = $success;
    }

    $separator = strpos($page, '?') !== false ? '&' : '?';
    $query = $params ? $separator . http_build_query($params) : '';
    header('Location: ' . $page . $query);
    exit;
}

function set_user_session(array $user): void
{
    $_SESSION['id'] = (int) $user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}

function login_redirect_for_role(string $role): string
{
    return $role === 'dorm_owner' ? 'admin-dashboard.html' : 'listings.html';
}

function sanitize_email(string $email): string
{
    return strtolower(trim($email));
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $storedHash, mysqli $conn, int $userId): bool
{
    if (password_verify($password, $storedHash)) {
        return true;
    }

    // Support legacy plaintext passwords from early seed data.
    if ($storedHash === $password) {
        $newHash = hash_password($password);
        $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $newHash, $userId);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    return false;
}

function send_reset_email(string $email, string $token): bool
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    $resetLink = $scheme . '://' . $host . $basePath . '/reset-password.php?token=' . urlencode($token);

    $subject = 'HanapDormIndang Password Reset';
    $message = "Hello,\n\n";
    $message .= "We received a request to reset your password.\n";
    $message .= "Click the link below to set a new password (expires in 1 hour):\n\n";
    $message .= $resetLink . "\n\n";
    $message .= "If you did not request this, you can ignore this email.\n";

    $headers = "From: noreply@hanapdormindang.local\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($email, $subject, $message, $headers);
}
