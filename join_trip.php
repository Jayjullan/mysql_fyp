<?php
header("Content-Type: application/json");
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
$code = $data['invite_code'];
$user_id = $data['user_id'];

// 1. Locate trip
$t = $conn->prepare("SELECT tripId FROM trip WHERE invite_code = ?");
$t->bind_param("s", $code);
$t->execute();
$tripRes = $t->get_result()->fetch_assoc();

if ($tripRes) {
    $tripId = $tripRes['tripId'];

    // 2. Prevent joining twice
    $check = $conn->prepare("SELECT memberID FROM group_member WHERE userID = ? AND tripID = ?");
    $check->bind_param("ii", $user_id, $tripId);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Already a member"]);
        exit;
    }

    // 3. Get Joiner details
    $u = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $u->bind_param("i", $user_id);
    $u->execute();
    $userData = $u->get_result()->fetch_assoc();

    // 4. Add to group
    $join = $conn->prepare("INSERT INTO group_member (name, email, userID, tripID) VALUES (?, ?, ?, ?)");
    $join->bind_param("ssii", $userData['username'], $userData['email'], $user_id, $tripId);
    
    if ($join->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Join failed"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Trip not found"]);
}
?>