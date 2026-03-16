<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_config.php'; 

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['username']) && isset($data['email']) && isset($data['password'])) {
    $username = $conn->real_escape_string($data['username']);
    $email = strtolower(trim($conn->real_escape_string($data['email'])));
    
    // Use PASSWORD_DEFAULT to create a secure hash
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered"]);
    } else {
        // Insert new user with the hashed password
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Account created successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }
        $stmt->close();
    }
    $checkEmail->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
}
$conn->close();
?>