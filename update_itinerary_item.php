<?php
header("Content-Type: application/json");
include 'db_config.php'; 

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Verify itineraryID exists and is not empty
if (isset($data['itineraryID']) && !empty($data['itineraryID'])) {
    $itineraryID = intval($data['itineraryID']);
    $title = $data['title'] ?? "";
    $description = $data['description'] ?? "";
    $dateTime = $data['dateTime'] ?? "";
    $location = $data['location'] ?? "";
    $file_URL = $data['file_URL'] ?? null;

    $stmt = $conn->prepare("UPDATE itinerary_item SET title=?, description=?, dateTime=?, location=?, file_URL=? WHERE itineraryID=?");
    $stmt->bind_param("sssssi", $title, $description, $dateTime, $location, $file_URL, $itineraryID);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
} else {
    // Descriptive error message to help you debug what the server actually received
    echo json_encode([
        "success" => false, 
        "error" => "Missing itineraryID in request body.",
        "received_data" => $data
    ]);
}
$conn->close();
?>