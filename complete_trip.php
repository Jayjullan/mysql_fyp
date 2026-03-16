<?php
header("Content-Type: application/json");
include 'db_config.php'; 

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['tripID'])) {
    $tripID = intval($data['tripID']);
    
    // Assuming 'status' is a column in your 'trip' table. 
    // If it doesn't exist, you'll need to add it via: ALTER TABLE trip ADD COLUMN status VARCHAR(20) DEFAULT 'active';
    $stmt = $conn->prepare("UPDATE trip SET status = 'inactive' WHERE tripId = ?");
    $stmt->bind_param("i", $tripID);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Trip marked as inactive"]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Missing tripID"]);
}
$conn->close();
?>