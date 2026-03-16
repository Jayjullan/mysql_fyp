<?php
$host = "localhost";
$user = "root";
$password = ""; 
$dbname = "plan2gether_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed"]));
}
?>