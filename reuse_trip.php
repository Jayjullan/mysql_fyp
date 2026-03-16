<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "plan2gether_db";

$conn = new mysqli($servername, $username, $password, $dbname);

$data = json_decode(file_get_contents("php://input"), true);
$oldTripId = isset($data['tripId']) ? intval($data['tripId']) : 0;
$userID = isset($data['userID']) ? intval($data['userID']) : 0;

if ($oldTripId === 0 || $userID === 0) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

// 1. Get original trip details including the image blob/string
$stmt = $conn->prepare("SELECT tripName, destination, duration, image FROM trip WHERE tripId = ?");
$stmt->bind_param("i", $oldTripId);
$stmt->execute();
$oldTrip = $stmt->get_result()->fetch_assoc();

if (!$oldTrip) {
    echo json_encode(["status" => "error", "message" => "Trip not found"]);
    exit;
}

$newCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

// 2. Insert New Trip with the original image
$insertTrip = $conn->prepare("INSERT INTO trip (tripName, destination, duration, image, invite_code, status, adminID) VALUES (?, ?, ?, ?, ?, 'active', ?)");
$insertTrip->bind_param("sssssi", 
    $oldTrip['tripName'], 
    $oldTrip['destination'], 
    $oldTrip['duration'], 
    $oldTrip['image'], 
    $newCode,
    $userID
);

if ($insertTrip->execute()) {
    echo json_encode(["status" => "success", "message" => "Trip duplicated!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}

$conn->close();
?>