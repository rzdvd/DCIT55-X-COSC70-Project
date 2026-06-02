<?php
session_start();
include ("../database/database.php");

if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'dorm_owner') {
    header('Location: ../auth/login.html?error=You must be logged in as a dorm owner to add a listing');
    exit();
}

$owner_id = (int) $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $dorm_name = $_POST['dorm_name'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $monthly_rent = $_POST['monthly_rent'];
    $available_rooms = $_POST['available_rooms'];
    $total_rooms = $_POST['total_rooms'];
    $room_capacity = $_POST['room_capacity'];

    $query = "
    INSERT INTO dorms (
        owner_id,
        dorm_name,
        description,
        address,
        latitude,
        longitude,
        monthly_rent,
        available_rooms,
        total_rooms,
        room_capacity
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($query); 
    $stmt->bind_param("issssddiii", $owner_id, $dorm_name, $description, $address, $latitude, $longitude, $monthly_rent, $available_rooms, $total_rooms, $room_capacity);

    if ($stmt->execute()) {
        $dorm_id = $conn->insert_id;

        // 1. Set the correct physical folder path (relative to this admin file)
        $uploadDir = "../uploads/dorms/";

        // Create the directory if it doesn't exist yet to prevent errors
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['dorm_images']['tmp_name'] as $key => $tmp_name) {
            
            // Clean the filename to remove spaces and weird characters
            $cleanFileName = preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['dorm_images']['name'][$key]));
            $fileName = time() . "_" . $cleanFileName;
            
            // The physical path where PHP will move the file
            $targetFile = $uploadDir . $fileName;

            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            if (in_array($fileExtension, $allowedTypes)) {
                
                if (move_uploaded_file($tmp_name, $targetFile)) {
                    
                    // 2. Format the URL for the database so the homepage can read it!
                    // We save "uploads/dorms/filename.jpg" (No dots at the start)
                    $dbImageUrl = "uploads/dorms/" . $fileName;

                    $imageQuery = "
                    INSERT INTO dorm_images (
                        dorm_id,
                        image_url
                    ) VALUES (?, ?)
                    ";

                    $imageStmt = $conn->prepare($imageQuery);
                    $imageStmt->bind_param("is", $dorm_id, $dbImageUrl);
                    $imageStmt->execute();
                }
            }
        }

        echo "<script>alert('Dorm added successfully!'); window.location.href='admin-dashboard.html';</script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>