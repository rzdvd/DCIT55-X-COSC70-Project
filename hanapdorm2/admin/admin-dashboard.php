<?php
// Set JSON header first to prevent HTML error output
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
}

// Log errors to file instead of displaying them
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');
error_reporting(E_ALL);

// Suppress HTML error output for API responses
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['json'])) {
    ini_set('display_errors', 0);
}

// Global error handler to catch fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['json'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'PHP Error: ' . $errstr,
            'debug' => ['file' => $errfile, 'line' => $errline, 'errno' => $errno]
        ]);
        exit;
    }
}, E_ALL);

session_start();
include ("../database/database.php");

// Check database connection
if (!$conn) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'dorm_owner') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['json'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    } else {
        header('Location: ../auth/login.html?error=You must be logged in as a dorm owner');
        exit();
    }
}

$owner_id = (int) $_SESSION['id'];

// Fetch all dorms for this owner
$query = "
    SELECT 
        dorm_id,
        dorm_name,
        description,
        address,
        available_rooms,
        total_rooms,
        room_capacity,
        monthly_rent
    FROM dorms
    WHERE owner_id = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$dorms = [];
while ($row = $result->fetch_assoc()) {
    $dorms[] = $row;
}
$stmt->close();

// Fetch booking data
$bookingQuery = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.renter_id IS NOT NULL THEN 1 ELSE 0 END) as filled_bookings
    FROM dorms d
    LEFT JOIN bookings b ON d.dorm_id = b.dorm_id
    WHERE d.owner_id = ?
";

$bookingStmt = $conn->prepare($bookingQuery);
$bookingStmt->bind_param('i', $owner_id);
$bookingStmt->execute();
$bookingResult = $bookingStmt->get_result();
$bookingData = $bookingResult->fetch_assoc();
$bookingStmt->close();

// Calculate totals
$totalBeds = 0;
$totalRooms = 0;
$availableBeds = 0;
$availableRooms = 0;

foreach ($dorms as $dorm) {
    $totalRooms += (int)$dorm['total_rooms'];
    $availableRooms += (int)$dorm['available_rooms'];
    $totalBeds += (int)$dorm['room_capacity'] * (int)$dorm['total_rooms'];
    $availableBeds += (int)$dorm['room_capacity'] * (int)$dorm['available_rooms'];
}

$occupancyPercentage = $totalBeds > 0 ? round((($totalBeds - $availableBeds) / $totalBeds) * 100) : 0;


// Fetch applications/bookings by status
$appQuery = "
    SELECT 
        b.booking_id,
        b.dorm_id,
        b.renter_id,
        b.move_in_date,
        b.status,
        b.created_at,
        u.first_name,
        u.last_name,
        u.email,
        d.dorm_name
    FROM bookings b
    JOIN users u ON b.renter_id = u.id
    JOIN dorms d ON b.dorm_id = d.dorm_id
    WHERE d.owner_id = ?
    ORDER BY b.created_at DESC
";

$appStmt = $conn->prepare($appQuery);
$appStmt->bind_param('i', $owner_id);
$appStmt->execute();
$appResult = $appStmt->get_result();

$bookings = [];
while ($row = $appResult->fetch_assoc()) {
    $bookings[] = $row;
}
$appStmt->close();

// Count bookings by actual status
$totalApplications = count($bookings);
$approvedApplications = 0;
$pendingApplications = 0;
$rejectedApplications = 0;

foreach ($bookings as $booking) {
    if ($booking['status'] === 'approved') {
        $approvedApplications++;
    } elseif ($booking['status'] === 'pending') {
        $pendingApplications++;
    } elseif ($booking['status'] === 'rejected') {
        $rejectedApplications++;
    }
}

if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'dorms' => $dorms,
        'stats' => [
            'total_beds' => $totalBeds,
            'total_rooms' => $totalRooms,
            'available_beds' => $availableBeds,
            'available_rooms' => $availableRooms,
            'occupancy_percentage' => $occupancyPercentage
        ],
        'applications' => [
            'total' => $totalApplications,
            'approved' => $approvedApplications,
            'pending' => $pendingApplications,
            'bookings' => $bookings
        ]
    ]);
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: log raw POST data
    error_log("POST request received. POST data: " . json_encode($_POST));
    
    $action = trim($_POST['action'] ?? '');
    error_log("Action extracted: '$action'");

    if (empty($action)) {
        echo json_encode(['success' => false, 'error' => 'No action provided']);
        exit;
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        // Fetch current user
        $userStmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ?');
        if (!$userStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $userStmt->bind_param('i', $owner_id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $user = $userResult->fetch_assoc();
        $userStmt->close();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
            exit;
        }

        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
            exit;
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        if (!$updateStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $updateStmt->bind_param('si', $newHash, $owner_id);

        if (!$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            error_log("Update password error: $error");
            echo json_encode(['success' => false, 'error' => 'Failed to update password: ' . $error]);
        } else {
            $updateStmt->close();
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        }
        exit;
    }

    if ($action === 'update_dorm') {
        $dorm_id = (int)($_POST['dorm_id'] ?? 0);
        $dorm_name = $_POST['dorm_name'] ?? '';
        $monthly_rent = (float)($_POST['monthly_rent'] ?? 0);
        $description = $_POST['description'] ?? '';

        if (!$dorm_id || !$dorm_name) {
            echo json_encode(['success' => false, 'error' => 'Invalid dorm data']);
            exit;
        }

        // Verify ownership
        $verifyStmt = $conn->prepare('SELECT owner_id FROM dorms WHERE dorm_id = ?');
        if (!$verifyStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $verifyStmt->bind_param('i', $dorm_id);
        if (!$verifyStmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $verifyStmt->error]);
            $verifyStmt->close();
            exit;
        }
        $verifyResult = $verifyStmt->get_result();
        $dormCheck = $verifyResult->fetch_assoc();
        $verifyStmt->close();

        if (!$dormCheck || $dormCheck['owner_id'] != $owner_id) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $updateStmt = $conn->prepare('UPDATE dorms SET dorm_name = ?, monthly_rent = ?, description = ? WHERE dorm_id = ?');
        if (!$updateStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $updateStmt->bind_param('sdsi', $dorm_name, $monthly_rent, $description, $dorm_id);

        if (!$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            error_log("Update dorm error: $error");
            echo json_encode(['success' => false, 'error' => 'Failed to update dorm: ' . $error]);
        } else {
            $updateStmt->close();
            echo json_encode(['success' => true, 'message' => 'Dorm updated successfully']);
        }
        exit;
    }

    if ($action === 'update_availability') {
        $dorm_id = (int)($_POST['dorm_id'] ?? 0);
        $available_rooms = (int)($_POST['available_rooms'] ?? 0);
        $total_rooms = (int)($_POST['total_rooms'] ?? 0);

        if (!$dorm_id || $available_rooms < 0 || $total_rooms < 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid room data']);
            exit;
        }

        if ($available_rooms > $total_rooms) {
            echo json_encode(['success' => false, 'error' => 'Available rooms cannot exceed total rooms']);
            exit;
        }

        // Verify ownership
        $verifyStmt = $conn->prepare('SELECT owner_id FROM dorms WHERE dorm_id = ?');
        if (!$verifyStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $verifyStmt->bind_param('i', $dorm_id);
        if (!$verifyStmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $verifyStmt->error]);
            $verifyStmt->close();
            exit;
        }
        $verifyResult = $verifyStmt->get_result();
        $dormCheck = $verifyResult->fetch_assoc();
        $verifyStmt->close();

        if (!$dormCheck || $dormCheck['owner_id'] != $owner_id) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $updateStmt = $conn->prepare('UPDATE dorms SET available_rooms = ?, total_rooms = ? WHERE dorm_id = ?');
        if (!$updateStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $updateStmt->bind_param('iii', $available_rooms, $total_rooms, $dorm_id);

        if (!$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            error_log("Update availability error: $error");
            echo json_encode(['success' => false, 'error' => 'Failed to update availability: ' . $error]);
        } else {
            $updateStmt->close();
            echo json_encode(['success' => true, 'message' => 'Availability updated successfully']);
        }
        exit;
    }

    if ($action === 'delete_dorm') {
        $dorm_id = (int)($_POST['dorm_id'] ?? 0);

        if (!$dorm_id) {
            echo json_encode(['success' => false, 'error' => 'Invalid dorm ID']);
            exit;
        }

        // Verify ownership first
        $verifyStmt = $conn->prepare('SELECT owner_id FROM dorms WHERE dorm_id = ?');
        $verifyStmt->bind_param('i', $dorm_id);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $dormCheck = $verifyResult->fetch_assoc();
        $verifyStmt->close();

        if (!$dormCheck || $dormCheck['owner_id'] != $owner_id) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized or dorm not found']);
            exit;
        }

        // Start a database transaction to safely remove all linked data first
        $conn->begin_transaction();
        try {
            // Delete child records to prevent foreign key constraint failures
            $conn->query("DELETE FROM dorm_amenities WHERE dorm_id = $dorm_id");
            $conn->query("DELETE FROM dorm_images WHERE dorm_id = $dorm_id");
            $conn->query("DELETE FROM favorites WHERE dorm_id = $dorm_id");
            $conn->query("DELETE FROM bookings WHERE dorm_id = $dorm_id");
            
            // Delete the parent dorm
            $conn->query("DELETE FROM dorms WHERE dorm_id = $dorm_id");
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Dorm deleted successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Delete dorm error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to delete dorm: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'approve_booking' || $action === 'reject_booking') {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $new_status = ($action === 'approve_booking') ? 'approved' : 'rejected';

        if (!$booking_id) {
            echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
            exit;
        }

        // Verify ownership - make sure this booking belongs to a dorm owned by this user
        $verifyStmt = $conn->prepare('
            SELECT b.booking_id 
            FROM bookings b
            JOIN dorms d ON b.dorm_id = d.dorm_id
            WHERE b.booking_id = ? AND d.owner_id = ?
        ');
        if (!$verifyStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $verifyStmt->bind_param('ii', $booking_id, $owner_id);
        if (!$verifyStmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $verifyStmt->error]);
            $verifyStmt->close();
            exit;
        }
        $verifyResult = $verifyStmt->get_result();
        $bookingCheck = $verifyResult->fetch_assoc();
        $verifyStmt->close();

        if (!$bookingCheck) {
            echo json_encode(['success' => false, 'error' => 'Booking not found or unauthorized']);
            exit;
        }

        // Update booking status
        $updateStmt = $conn->prepare('UPDATE bookings SET status = ? WHERE booking_id = ?');
        if (!$updateStmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $updateStmt->bind_param('si', $new_status, $booking_id);

        if (!$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            error_log("Update booking status error: $error");
            echo json_encode(['success' => false, 'error' => 'Failed to update booking: ' . $error]);
        } else {
            $updateStmt->close();
            $message = ($action === 'approve_booking') ? 'Booking approved successfully' : 'Booking rejected';
            echo json_encode(['success' => true, 'message' => $message, 'status' => $new_status]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}
?>
