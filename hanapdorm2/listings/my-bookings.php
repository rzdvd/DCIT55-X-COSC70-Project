<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (!isset($_SESSION['id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$renter_id = (int)$_SESSION['id'];

$query = '
    SELECT 
        b.booking_id,
        b.dorm_id,
        b.move_in_date,
        d.dorm_name,
        d.address,
        d.monthly_rent,
        d.room_capacity,
        d.available_rooms
    FROM bookings b
    JOIN dorms d ON b.dorm_id = d.dorm_id
    WHERE b.renter_id = ?
    ORDER BY b.move_in_date DESC
';

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $renter_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode(['bookings' => $bookings]);
?>
