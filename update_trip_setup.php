<?php
header("Content-Type: application/json");
include 'db_config.php'; 

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['tripId'], $data['startDate'], $data['endDate'], $data['duration'], $data['image'])) {
    // Note: ensure your table column names match exactly: tripId, startDate, endDate, duration, image
    $sql = "UPDATE trip SET startDate = ?, endDate = ?, duration = ?, image = ? WHERE tripId = ?";
    $stmt = $conn->prepare($sql);
    
    // Using "ssssi" assuming tripId is an Integer and the rest are Strings
    $stmt->bind_param("ssssi", $data['startDate'], $data['endDate'], $data['duration'], $data['image'], $data['tripId']);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Incomplete data provided to server."]);
}
?>