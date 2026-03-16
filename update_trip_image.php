<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_config.php'; 

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['tripId']) && isset($data['image'])) {
    $tripId = intval($data['tripId']);
    $image = $data['image'];

    // Update the 'image' column in the 'trip' table
    $stmt = $conn->prepare("UPDATE trip SET image = ? WHERE tripId = ?");
    $stmt->bind_param("si", $image, $tripId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid parameters"]);
}
$conn->close();
?>