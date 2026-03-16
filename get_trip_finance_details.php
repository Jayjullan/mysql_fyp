<?php
header("Content-Type: application/json");
include 'db_config.php';

$tripID = isset($_GET['tripID']) ? intval($_GET['tripID']) : 0;

if ($tripID === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid Trip ID"]);
    exit();
}

// 1. Fetch Trip Info
$tripStmt = $conn->prepare("SELECT tripName, destination FROM trip WHERE tripID = ?");
$tripStmt->bind_param("i", $tripID);
$tripStmt->execute();
$tripInfo = $tripStmt->get_result()->fetch_assoc();

// 2. Fetch All Expenses for this trip
$expStmt = $conn->prepare("
    SELECT e.amount, e.description, u.username as payerName, e.expenseID 
    FROM expense e 
    LEFT JOIN users u ON e.payerID = u.id 
    WHERE e.tripID = ?
");
$expStmt->bind_param("i", $tripID);
$expStmt->execute();
$expenses = $expStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Calculate Summary
$totalAmount = 0;
foreach($expenses as $e) { $totalAmount += $e['amount']; }

echo json_encode([
    "status" => "success",
    "trip" => $tripInfo,
    "expenses" => $expenses,
    "totalAmount" => $totalAmount
]);

$conn->close();
?>