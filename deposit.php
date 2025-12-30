<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "ddms");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data and sanitize
$resource = $conn->real_escape_string($_POST['resource']);
$quantity = (int)$_POST['quantity'];
$name = $conn->real_escape_string($_POST['name']);
$location = $conn->real_escape_string($_POST['location']);
$contact = $conn->real_escape_string($_POST['contact']);

// Insert into donations table
$sql = "INSERT INTO donations (resource, quantity, name, location, contact)
        VALUES ('$resource', '$quantity', '$name', '$location', '$contact')";

if ($conn->query($sql) === TRUE) {
    // Update resource1 table (assuming this is your resources table name)
    $update_sql = "UPDATE resource1 SET quantity = quantity + $quantity 
                   WHERE resource_type = '$resource'";
    
    if ($conn->query($update_sql) === TRUE) {
        // Redirect after successful insert
        header("Location: thankyoud.html");
        exit();
    } else {
        echo "Error updating resources: " . $conn->error;
    }
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>