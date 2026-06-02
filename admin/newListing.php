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
    $amenities = isset($_POST['amenities']) ? json_decode($_POST['amenities'], true) : [];

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

    $stmt = $conn->prepare($query); $stmt->bind_param( "issssddiii", $owner_id,
    $dorm_name, $description, $address, $latitude, $longitude, $monthly_rent, $available_rooms, $total_rooms, $room_capacity );

     if ($stmt->execute()) {
        $dorm_id = $conn->insert_id;

        if (!empty($amenities)) {
          $amenityQuery = "SELECT amenity_id FROM amenities WHERE amenity_name = ?";
          $amenityStmt = $conn->prepare($amenityQuery);
          
          foreach ($amenities as $amenityName) {
            $amenityStmt->bind_param("s", $amenityName);
            $amenityStmt->execute();
            $amenityResult = $amenityStmt->get_result();
            
            if ($amenityRow = $amenityResult->fetch_assoc()) {
              $amenityId = $amenityRow['amenity_id'];
              $insertAmenityQuery = "INSERT INTO dorm_amenities (dorm_id, amenity_id) VALUES (?, ?)";
              $insertAmenityStmt = $conn->prepare($insertAmenityQuery);
              $insertAmenityStmt->bind_param("ii", $dorm_id, $amenityId);
              $insertAmenityStmt->execute();
            }
          }
        }

        $uploadDir = "uploads/dorm_../images/";

        foreach ($_FILES['dorm_images']['tmp_name'] as $key => $tmp_name) {

            $fileName = time() . "_" . $_FILES['dorm_images']['name'][$key];
            $targetFile = $uploadDir . basename($fileName);

            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];

            $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            if (in_array($fileExtension, $allowedTypes)) {

                if (move_uploaded_file($tmp_name, $targetFile)) {
                    $imageQuery = "
                    INSERT INTO dorm_images (
                        dorm_id,
                        image_url
                    ) VALUES (?, ?)
                    ";

                    $imageStmt = $conn->prepare($imageQuery);
                    $imageStmt->bind_param("is", $dorm_id, $targetFile);
                    $imageStmt->execute();
                }
            }
        }

        echo "<script>alert('Dorm added successfully!')</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    header("Location: admin-dashboard.html");
    exit();
}
?>