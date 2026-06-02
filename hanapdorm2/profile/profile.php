<?php
session_start();
require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../helpers.php';

if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $query = 'SELECT id, first_name, last_name, email, role, profile_picture FROM users WHERE id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['user' => $user]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    
    if (empty($first_name) || empty($last_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'First and last name are required']);
        exit;
    }
    
    if (!is_valid_email($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }
    
    $checkEmail = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $checkEmail->bind_param('si', $email, $user_id);
    $checkEmail->execute();
    if ($checkEmail->get_result()->num_rows > 0) {
        $checkEmail->close();
        http_response_code(400);
        echo json_encode(['error' => 'Email already in use']);
        exit;
    }
    $checkEmail->close();
    
    $updateQuery = 'UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?';
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('sssi', $first_name, $last_name, $email, $user_id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['email'] = $email;
        
        $conn->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        $updateStmt->close();
        $conn->close();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload-pfp') {
    if (!isset($_FILES['profile_picture'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file provided']);
        exit;
    }
    
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, and GIF allowed']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Maximum 5MB allowed']);
        exit;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Upload failed']);
        exit;
    }
    
    $upload_dir = __DIR__ . '/uploads/pfps';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'pfp_' . $user_id . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . '/' . $new_filename;
    $relative_path = './uploads/pfps/' . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }
    
    $updateQuery = 'UPDATE users SET profile_picture = ? WHERE id = ?';
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('si', $relative_path, $user_id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        $_SESSION['profile_picture'] = $relative_path;
        
        $conn->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'profile_picture' => $relative_path]);
    } else {
        $updateStmt->close();
        $conn->close();
        @unlink($file_path);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile picture']);
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request']);
?>
