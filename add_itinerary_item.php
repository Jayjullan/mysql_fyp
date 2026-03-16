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
    $budget = isset($data['budget']) ? floatval($data['budget']) : 0;
    $payerID = $data['payerID'] ?? null; // Logged in user ID

    // Start Transaction to ensure both inserts succeed
    $conn->begin_transaction();

    try {
        // 1. Insert into itinerary_item
        $stmt = $conn->prepare("INSERT INTO itinerary_item (title, description, dateTime, location, file_URL, tripID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $description, $dateTime, $location, $file_URL, $tripID);
        $stmt->execute();
        $itineraryID = $conn->insert_id;

        // 2. If budget > 0, insert into expense table
        if ($budget > 0) {
            $expenseDesc = "Budget for activity: " . $title;
            $stmtEx = $conn->prepare("INSERT INTO expense (amount, description, payerID, tripID) VALUES (?, ?, ?, ?)");
            $stmtEx->bind_param("dsii", $budget, $expenseDesc, $payerID, $tripID);
            $stmtEx->execute();
        }

        $conn->commit();
        echo json_encode(["success" => true, "id" => $itineraryID]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Missing tripID or Title"]);
}
$conn->close();
?>