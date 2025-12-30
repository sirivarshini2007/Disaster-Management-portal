<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ddms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM disasters";
$result = $conn->query($sql);

$disasters = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $disasters[] = $row;
    }
}

echo json_encode($disasters);
$conn->close();
?>