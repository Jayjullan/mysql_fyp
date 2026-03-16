<?php
header("Content-Type: application/json");
include 'db_config.php';

// Get the userID from the URL
$userID = isset($_GET['userID']) ? intval($_GET['userID']) : 0;

if ($userID === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid User ID"]);
    exit();
}

// 1. FETCH ONLY ACTIVE TRIPS THE USER IS A MEMBER OF
// Added filter: t.status = 'active'
$tripStmt = $conn->prepare("
    SELECT t.tripID, t.tripName as title, t.destination, t.status
    FROM trip t
    INNER JOIN group_member gm ON t.tripID = gm.tripID
    WHERE gm.userID = ? AND t.status = 'active'
    ORDER BY t.tripID DESC
");
$tripStmt->bind_param("i", $userID);
$tripStmt->execute();
$tripsResult = $tripStmt->get_result();

$trips_data = [];

while ($trip = $tripsResult->fetch_assoc()) {
    $currentTripID = $trip['tripID'];
    
    // 2. FETCH EXPENSES FOR THIS TRIP
    // Linking expense.tripID to the current active trip
    $expStmt = $conn->prepare("
        SELECT e.expenseID, e.amount, e.description, u.username as payerName
        FROM expense e
        LEFT JOIN users u ON e.payerID = u.id
        WHERE e.tripID = ?
    ");
    $expStmt->bind_param("i", $currentTripID);
    $expStmt->execute();
    $expensesResult = $expStmt->get_result();
    
    $expenses = [];
    $totalAmount = 0;
    
    while ($exp = $expensesResult->fetch_assoc()) {
        $expenses[] = $exp;
        $totalAmount += floatval($exp['amount']);
    }
    
    $trip['expenses'] = $expenses;
    $trip['totalAmount'] = $totalAmount;
    $trips_data[] = $trip;
}

// 3. RETURN JSON
echo json_encode([
    "status" => "success",
    "trips" => $trips_data
]);

$conn->close();
?>