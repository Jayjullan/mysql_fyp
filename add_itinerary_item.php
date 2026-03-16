<?php
header("Content-Type: application/json");
include 'db_config.php'; 

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['tripID'], $data['title'])) {
    $tripID = intval($data['tripID']);
    $title = $data['title'];
    $description = $data['description'] ?? "";
    $dateTime = $data['dateTime'] ?? "";
    $location = $data['location'] ?? "";
    $file_URL = $data['file_URL'] ?? null;

    $stmt = $conn->prepare("INSERT INTO itinerary_item (title, description, dateTime, location, file_URL, tripID) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $title, $description, $dateTime, $location, $file_URL, $tripID);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Missing tripID or Title"]);
}
$conn->close();
?>