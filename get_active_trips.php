<?php
ob_start();
header("Content-Type: application/json");
include 'db_config.php'; 

try {
    // Validate that userID is provided in the GET request
    if (!isset($_GET['userID']) || empty($_GET['userID'])) {
        echo json_encode(["error" => "User ID is required"]);
        exit;
    }

    $currentUserID = intval($_GET['userID']);

    /**
     * The query selects trips where the logged-in user is a member.
     * It performs an INNER JOIN with group_member to ensure the user belongs to the trip.
     * It filters by status = 'Active' to show only ongoing trips.
     */
    $sql = "SELECT 
                t.tripId AS id, 
                t.tripName AS title, 
                t.invite_code AS code, 
                t.destination, 
                t.startDate, 
                t.duration, 
                t.image 
            FROM trip t
            INNER JOIN group_member gm ON t.tripId = gm.tripID
            WHERE gm.userID = ? 
            AND t.status = 'Active'";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    // Bind the current user's ID to the query
    $stmt->bind_param("i", $currentUserID);
    $stmt->execute();
    $result = $stmt->get_result();

    $trips = [];
    while($row = $result->fetch_assoc()) {
        $trips[] = $row; 
    }

    // Clear buffer and return JSON encoded trip list
    ob_end_clean();
    echo json_encode($trips);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>