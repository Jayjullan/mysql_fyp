<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "plan2gether_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed"]));
}

$userID = isset($_GET['userID']) ? intval($_GET['userID']) : 0;

if ($userID === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid User ID"]);
    exit;
}

// Added 'image' to the SELECT statement
$sql = "SELECT tripId as id, tripName as title, invite_code as code, 
               destination as description, image 
        FROM trip 
        WHERE status = 'Inactive' 
        AND adminID = ? 
        ORDER BY tripId DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$trips = [];
while($row = $result->fetch_assoc()) {
    $trips[] = $row;
}

echo json_encode($trips);
$conn->close();
?>