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
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GET - Fetch all favorites
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $query = '
        SELECT 
            d.dorm_id,
            d.dorm_name,
            d.address,
            d.monthly_rent,
            d.room_capacity,
            d.available_rooms,
            d.description
        FROM favorites f
        JOIN dorms d ON f.dorm_id = d.dorm_id
        WHERE f.renter_id = ?
        ORDER BY f.created_at DESC
    ';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $renter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode(['favorites' => $favorites]);
    exit;
}

// GET - Check if a specific dorm is favorited
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'check') {
    $dorm_id = (int)($_GET['dorm_id'] ?? 0);
    
    if ($dorm_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid dorm_id']);
        exit;
    }
    
    $query = 'SELECT 1 FROM favorites WHERE renter_id = ? AND dorm_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $renter_id, $dorm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_favorite = $result->num_rows > 0;
    $stmt->close();
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode(['is_favorite' => $is_favorite]);
    exit;
}

// POST - Add or remove favorite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dorm_id = (int)($_POST['dorm_id'] ?? 0);
    
    if ($dorm_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid dorm_id']);
        exit;
    }
    
    // Check if already favorited
    $checkQuery = 'SELECT 1 FROM favorites WHERE renter_id = ? AND dorm_id = ? LIMIT 1';
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('ii', $renter_id, $dorm_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $is_favorite = $checkResult->num_rows > 0;
    $checkStmt->close();
    
    if ($is_favorite) {
        // Remove from favorites
        $deleteQuery = 'DELETE FROM favorites WHERE renter_id = ? AND dorm_id = ?';
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param('ii', $renter_id, $dorm_id);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        $conn->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add to favorites
        $insertQuery = 'INSERT INTO favorites (renter_id, dorm_id) VALUES (?, ?)';
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param('ii', $renter_id, $dorm_id);
        
        if ($insertStmt->execute()) {
            $insertStmt->close();
            $conn->close();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'action' => 'added']);
        } else {
            $insertStmt->close();
            $conn->close();
            
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add favorite']);
        }
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request']);
?>
