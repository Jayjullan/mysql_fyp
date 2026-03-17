<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include 'db_config.php'; // Using include to match login.php

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['id']) || !isset($data['current_password']) || !isset($data['new_password'])) {
        throw new Exception("Missing required fields");
    }
    
    $user_id = $data['id'];
    $current_password = $data['current_password'];
    $new_password = $data['new_password'];
    
    if (empty($user_id) || empty($current_password) || empty($new_password)) {
        throw new Exception("All fields are required");
    }
    
    if (strlen($new_password) < 6) {
        throw new Exception("Password must be at least 6 characters long");
    }
    
    // Get current user password using mysqli (to match your db_config)
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        throw new Exception("Current password is incorrect");
    }
    
    // Hash new password
    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password in database
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_new_password, $user_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Password changed successfully"
        ]);
    } else {
        throw new Exception("Failed to update password");
    }

    $stmt->close();
    $update_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>