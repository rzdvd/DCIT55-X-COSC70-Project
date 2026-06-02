<?php

function redirect_with(string $page, ?string $error = null, ?string $success = null, ?string $resetLink = null): void
{
    $params = [];
    if ($error !== null && $error !== '') {
        $params['error'] = $error;
    }
    if ($success !== null && $success !== '') {
        $params['success'] = $success;
    }
    if ($resetLink !== null && $resetLink !== '') {
        $params['reset_link'] = urlencode($resetLink);
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
    return $role === 'dorm_owner' ? '../admin/admin-dashboard.html' : '../listings/listings.html';
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
    $resetLink = $scheme . '://' . $host . $basePath . '/auth/reset-password.php?token=' . urlencode($token);

    // Check if running on localhost - store link for development testing
    if (is_localhost()) {
        $resetFile = sys_get_temp_dir() . '/hanapdorm_reset_' . md5($email) . '.txt';
        file_put_contents($resetFile, $resetLink);
        // Store in session for quick access
        $_SESSION['dev_reset_link_' . md5($email)] = $resetLink;
        return true;
    }

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

function is_localhost(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, 'localhost') !== false || 
           strpos($host, '127.0.0.1') !== false ||
           strpos($host, '::1') !== false;
}

function get_dev_reset_link(string $email): ?string
{
    if (!is_localhost()) {
        return null;
    }
    
    $resetFile = sys_get_temp_dir() . '/hanapdorm_reset_' . md5($email) . '.txt';
    if (file_exists($resetFile)) {
        return file_get_contents($resetFile);
    }
    
    return $_SESSION['dev_reset_link_' . md5($email)] ?? null;
}
