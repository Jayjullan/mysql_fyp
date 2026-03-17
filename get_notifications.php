<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include 'db_config.php';

$userID = $_GET['userID'] ?? null;

if (!$userID) {
    echo json_encode(["status" => "error", "message" => "UserID is required"]);
    exit;
}

try {
    // Select notifications for the user, newest first
    $sql = "SELECT id, title, description, type, isRead, created_at 
            FROM notifications 
            WHERE userID = ? 
            ORDER BY created_at DESC, id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        // Formatting the date/time for the frontend
        $row['time_ago'] = date("M d, H:i", strtotime($row['created_at']));
        $notifications[] = $row;
    }

    echo json_encode($notifications);

} catch (Exception $e) {
    // Ensuring even on failure we return JSON, not an HTML error
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>