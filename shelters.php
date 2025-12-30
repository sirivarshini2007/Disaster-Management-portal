<?php
include 'config.php';

$sql = "SELECT * FROM shelters";
$result = $conn->query($sql);

$shelters = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $shelters[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($shelters);

$conn->close();
?>