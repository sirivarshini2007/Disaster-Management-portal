<?php
include 'config.php';

$sql = "SELECT * FROM disasters WHERE status = 'active'";
$result = $conn->query($sql);

$disasters = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $disasters[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($disasters);

$conn->close();
?>