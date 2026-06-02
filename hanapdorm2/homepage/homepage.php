<?php
session_start();
header('Content-Type: application/json');


require_once '../database/database.php'; 

try {
    // Check if connection from database.php is valid
    if (!$conn) {
        throw new Exception("Database connection not established.");
    }

    // Fetch dorms from the database
    // Fetch dorms and their first associated image
    $sql = "
        SELECT 
            d.dorm_id, 
            d.dorm_name, 
            d.address, 
            d.monthly_rent,
            (SELECT image_url FROM dorm_images di WHERE di.dorm_id = d.dorm_id LIMIT 1) AS image_url
        FROM dorms d 
        ORDER BY d.created_at DESC
    ";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception("Error executing query: " . mysqli_error($conn));
    }

    // Fetch all rows as an associative array
    $dorms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dorms[] = $row;
    }

    // Return the data as JSON
    echo json_encode(['success' => true, 'dorms' => $dorms]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>