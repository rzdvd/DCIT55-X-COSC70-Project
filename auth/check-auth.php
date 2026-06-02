<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'isLoggedIn' => isset($_SESSION['id']),
    'userName' => $_SESSION['first_name'] ?? 'User',
    'userProfilePic' => $_SESSION['profile_picture'] ?? '../uploads/pfps/default-profile.jpg',
    'userRole' => $_SESSION['role'] ?? 'student'
]);
?>
