<?php
header("Content-Type: application/json");
include 'db_config.php'; 
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['itineraryID'])) {
    $stmt = $conn->prepare("DELETE FROM itinerary_item WHERE itineraryID = ?");
    $stmt->bind_param("i", $data['itineraryID']);
    echo json_encode(["success" => $stmt->execute()]);
    $stmt->close();
}
?>