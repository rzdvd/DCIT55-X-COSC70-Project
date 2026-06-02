<?php
session_start();
include ("../database/database.php");

$sql = $conn->prepare(
    "SELECT d.*, u.first_name AS owner_first_name, u.last_name AS owner_last_name, u.email AS owner_email
     FROM dorms d
     JOIN users u ON d.owner_id = u.id
     WHERE d.available_rooms > 0"
);
$sql->execute();
$result = $sql->get_result();

$dorms = [];
while ($row = $result->fetch_assoc()) {
    $dorms[] = $row;
}

$sql = $conn->prepare("SELECT * FROM dorm_amenities INNER JOIN amenities ON dorm_amenities.amenity_id = amenities.amenity_id");
$sql->execute();
$result = $sql->get_result();

$dormAmenities = [];
while ($row = $result->fetch_assoc()) {
    $dormAmenities[] = $row;
}

if (isset($_GET['json'])) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['dorms' => $dorms, 'amenities' => $dormAmenities]);
  exit;
}
?>