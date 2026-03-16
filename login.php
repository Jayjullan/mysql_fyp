<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'db_config.php'; 

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['email']) || !isset($data['password'])) {
        echo json_encode(["status" => "error", "message" => "Fields missing"]);
        exit;
    }

    $email = strtolower(trim($data['email']));
    $password = $data['password'];

    // Select the hashed password from the database
    $stmt = $conn->prepare("SELECT id, email, username, password FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        // Verify the provided password against the hash
        if (password_verify($password, $user_data['password'])) {
            echo json_encode([
                "status" => "success",
                "user" => [
                    "id" => $user_data['id'],
                    "email" => $user_data['email'],
                    "username" => $user_data['username'] ?? "User"
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid Email or Password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid Email or Password"]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server Error"]);
}
?>