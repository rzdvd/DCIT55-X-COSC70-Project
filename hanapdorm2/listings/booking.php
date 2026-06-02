<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../auth/login.html?error=Please log in to book a dorm');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.html');
    exit;
}

$renter_id = (int)$_SESSION['id'];
$dorm_id = (int)($_POST['dorm_id'] ?? 0);
$move_in_date = $_POST['move_in_date'] ?? '';

if ($dorm_id <= 0 || !$move_in_date) {
    header('Location: booking.html?error=Please select a dorm and move-in date');
    exit;
}

$checkDorm = $conn->prepare('SELECT dorm_id, available_rooms FROM dorms WHERE dorm_id = ?');
$checkDorm->bind_param('i', $dorm_id);
$checkDorm->execute();
$dorm = $checkDorm->get_result()->fetch_assoc();
$checkDorm->close();

if (!$dorm) {
    header('Location: booking.html?error=Selected dorm does not exist');
    exit;
}

if ($dorm['available_rooms'] <= 0) {
    header('Location: booking.html?error=This dorm has no available rooms');
    exit;
}

$insertBooking = $conn->prepare(
    'INSERT INTO bookings (
       dorm_id,
        renter_id,
        move_in_date
    ) VALUES (?, ?, ?)'
);

$insertBooking->bind_param(
    'iis',
    $dorm_id,
    $renter_id,
    $move_in_date
);

if ($insertBooking->execute()) {
    $booking_id = $conn->insert_id;
    $insertBooking->close();
    $conn->close();
    
    header('Location: booking.html?success=Booking submitted successfully! Your booking ID is ' . $booking_id);
    exit;
} else {
    $insertBooking->close();
    $conn->close();
    header('Location: booking.html?error=Failed to submit booking. Please try again.');
    exit;
}
?>
