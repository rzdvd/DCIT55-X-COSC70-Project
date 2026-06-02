<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "hanapdorm";

$conn = mysqli_connect($db_server,
                        $db_user,
                        $db_pass,
                        $db_name);

// Set timezone to UTC for consistency
date_default_timezone_set('UTC');

// Set MySQL timezone to UTC
if ($conn) {
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+00:00'");
}
?>