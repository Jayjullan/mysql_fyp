<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_config.php'; 

ob_start();

$tripId = isset($_GET['tripId']) ? intval($_GET['tripId']) : 0;

try {
    if ($tripId <= 0) {
        throw new Exception("Invalid Trip ID");
    }

    // Fetch Trip Metadata
    $stmt = $conn->prepare("SELECT tripId, tripName, invite_code, destination, startDate, endDate, duration, image, adminID FROM trip WHERE tripId = ?");
    $stmt->bind_param("i", $tripId);
    $stmt->execute();
    $tripMeta = $stmt->get_result()->fetch_assoc();

    if (!$tripMeta) {
        throw new Exception("Trip not found");
    }

    $current_admin_id = $tripMeta['adminID'];

    // Fetch Admin Details from admin table joined with users for the latest name
    $stmtCheckAdmin = $conn->prepare("
        SELECT a.userID, u.username 
        FROM admin a 
        JOIN users u ON a.userID = u.id 
        WHERE a.adminID = ?
    ");
    $stmtCheckAdmin->bind_param("i", $current_admin_id);
    $stmtCheckAdmin->execute();
    $adminRow = $stmtCheckAdmin->get_result()->fetch_assoc();

    if (!$adminRow) {
        // Integrity check: If admin missing from admin table, try to find in users
        $stmtUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmtUser->bind_param("i", $current_admin_id);
        $stmtUser->execute();
        $userData = $stmtUser->get_result()->fetch_assoc();
        
        $admin_uid = $current_admin_id;
        $admin_name = $userData ? $userData['username'] : "Admin";
    } else {
        $admin_uid = $adminRow['userID'];
        $admin_name = $adminRow['username'];
    }

    // Fetch Group Members - JOINED with users table to get the NEW name
    $stmtMembers = $conn->prepare("
        SELECT u.username AS name, u.email, gm.userID 
        FROM group_member gm
        JOIN users u ON gm.userID = u.id
        WHERE gm.tripID = ? 
        ORDER BY (gm.userID = ?) DESC, u.username ASC
    ");
    $stmtMembers->bind_param("ii", $tripId, $admin_uid);
    $stmtMembers->execute();
    $members = $stmtMembers->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch Activities
    $stmt2 = $conn->prepare("SELECT title, description, dateTime, location, file_URL FROM itinerary_item WHERE tripID = ? ORDER BY dateTime ASC");
    $stmt2->bind_param("i", $tripId);
    $stmt2->execute();
    $activities = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    ob_end_clean();
    echo json_encode([
        "tripId" => $tripMeta['tripId'],
        "name" => $tripMeta['tripName'],
        "invite_code" => $tripMeta['invite_code'],
        "description" => $tripMeta['destination'],
        "startDate" => $tripMeta['startDate'],
        "endDate" => $tripMeta['endDate'],
        "duration" => $tripMeta['duration'],
        "image" => $tripMeta['image'],
        "admin_userID" => $admin_uid,
        "admin_name" => $admin_name, // Added for clarity
        "members" => $members, 
        "activities" => $activities
    ]);

} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(["error" => $e->getMessage(), "success" => false]);
}
?>