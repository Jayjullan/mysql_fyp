<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "plan2gether_db");

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['id']) && isset($data['username'])) {
    $id = mysqli_real_escape_string($conn, $data['id']);
    $username = mysqli_real_escape_string($conn, $data['username']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Update the main users table
        $sqlUser = "UPDATE users SET username = '$username' WHERE id = '$id'";
        $conn->query($sqlUser);

        // 2. Update the admin table (where userID matches the user being edited)
        $sqlAdmin = "UPDATE admin SET adminName = '$username' WHERE userID = '$id'";
        $conn->query($sqlAdmin);

        // Commit changes
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Username and Admin profiles updated"]);
        
    } catch (Exception $e) {
        // Rollback if anything goes wrong
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
}

$conn->close();
?>