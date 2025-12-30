<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection - UPDATE WITH YOUR CREDENTIALS
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ddms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$sql = "SELECT id, title, description, category, created_at FROM ai_suggestions ORDER BY created_at DESC";
$result = $conn->query($sql);

$suggestions = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
    echo json_encode($suggestions);
} else {
    echo json_encode(["error" => "No suggestions found"]);
}

$conn->close();
?>