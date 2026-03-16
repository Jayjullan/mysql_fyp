<?php
header("Content-Type: application/json");
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

$tripName = $data['tripName'];
$invite_code = $data['invite_code'];
$destination = $data['destination'];
$user_id = $data['user_id']; // This is the ID from the 'users' table
$default_image = "https://images.unsplash.com/photo-1488646953014-85cb44e25828?q=80&w=1000";

// --- 1. HANDLE THE ADMIN STATUS ---
// Check if this user is already an admin
$checkAdmin = $conn->prepare("SELECT adminID FROM admin WHERE userID = ?");
$checkAdmin->bind_param("i", $user_id);
$checkAdmin->execute();
$adminResult = $checkAdmin->get_result();

if ($adminResult->num_rows > 0) {
    // User is already an admin, get their existing adminID
    $adminData = $adminResult->fetch_assoc();
    $finalAdminID = $adminData['adminID'];
} else {
    // User is NOT an admin, we must add them first
    // We need their username for the adminName column
    $u = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $u->bind_param("i", $user_id);
    $u->execute();
    $userData = $u->get_result()->fetch_assoc();

    $addAdmin = $conn->prepare("INSERT INTO admin (adminName, userID) VALUES (?, ?)");
    $addAdmin->bind_param("si", $userData['username'], $user_id);
    $addAdmin->execute();
    
    // Get the newly generated adminID
    $finalAdminID = $conn->insert_id;
}

// --- 2. CREATE TRIP USING THE adminID ---
$stmt = $conn->prepare("INSERT INTO trip (tripName, invite_code, destination, status, adminID, image) VALUES (?, ?, ?, 'active', ?, ?)");
$stmt->bind_param("sssis", $tripName, $invite_code, $destination, $finalAdminID, $default_image);

if ($stmt->execute()) {
    $tripID = $conn->insert_id;

    // --- 3. ADD TO GROUP_MEMBER ---
    // Note: We already fetched $userData above if they were new, 
    // but if they were already admins, we need it now.
    if (!isset($userData)) {
        $u = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
        $u->bind_param("i", $user_id);
        $u->execute();
        $userData = $u->get_result()->fetch_assoc();
    }

    $m = $conn->prepare("INSERT INTO group_member (name, email, userID, tripID) VALUES (?, ?, ?, ?)");
    $m->bind_param("ssii", $userData['username'], $userData['email'], $user_id, $tripID);
    $m->execute();

    echo json_encode(["status" => "success", "tripID" => $tripID, "adminID_assigned" => $finalAdminID]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>