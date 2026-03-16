<?php
header("Content-Type: application/json");
include 'db_config.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Use null coalescing to avoid "Undefined index" errors
$code = $data['invite_code'] ?? null;
$user_id = $data['user_id'] ?? null;

if (!$code || !$user_id) {
    echo json_encode(["status" => "error", "message" => "Missing invite code or user ID"]);
    exit;
}

// 1. Locate the trip by invite code
$t = $conn->prepare("SELECT tripId FROM trip WHERE invite_code = ?");
$t->bind_param("s", $code);
$t->execute();
$tripRes = $t->get_result()->fetch_assoc();

if ($tripRes) {
    $tripId = $tripRes['tripId'];

    // 2. Prevent the user from joining the same trip twice
    $check = $conn->prepare("SELECT memberID FROM group_member WHERE userID = ? AND tripID = ?");
    $check->bind_param("ii", $user_id, $tripId);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "You are already a member of this trip"]);
        exit;
    }

    // 3. Fetch user details to populate group_member table
    $u = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $u->bind_param("i", $user_id);
    $u->execute();
    $userData = $u->get_result()->fetch_assoc();

    if (!$userData) {
        echo json_encode(["status" => "error", "message" => "User record not found"]);
        exit;
    }

    // 4. Add the user to the group_member table
    $join = $conn->prepare("INSERT INTO group_member (name, email, userID, tripID) VALUES (?, ?, ?, ?)");
    $join->bind_param("ssii", $userData['username'], $userData['email'], $user_id, $tripId);
    
    if ($join->execute()) {
        echo json_encode(["status" => "success", "message" => "Joined successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to join: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Trip not found. Check the code and try again."]);
}

$conn->close();
?>