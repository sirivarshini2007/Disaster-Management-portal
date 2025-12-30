<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ddms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = $_POST['user_name'];
    $user_location = $_POST['user_location'];
    $user_contact = $_POST['user_contact'];
    $shelter_name = $_POST['shelter_name'];
    $people_count = $_POST['people_count'];
    
    // First, get the shelter details from shelters table
    $shelter_query = "SELECT id, current_capacity, max_capacity FROM shelters WHERE name = ?";
    $shelter_stmt = $conn->prepare($shelter_query);
    $shelter_stmt->bind_param("s", $shelter_name);
    $shelter_stmt->execute();
    $shelter_result = $shelter_stmt->get_result();
    
    if ($shelter_result->num_rows > 0) {
        $shelter = $shelter_result->fetch_assoc();
        $shelter_id = $shelter['id'];
        $current_capacity = $shelter['current_capacity'];
        $max_capacity = $shelter['max_capacity'];
        
        // Check if there's enough capacity
        $new_capacity = $current_capacity + $people_count;
        
        if ($new_capacity <= $max_capacity) {
            // Insert registration into database - FIXED COLUMN NAMES
            $sql = "INSERT INTO shelter_registrations (user_name, user_location, user_contact, shelter_name, registration_date, status, created_at) 
                    VALUES (?, ?, ?, ?, NOW(), 'active', NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $user_name, $user_location, $user_contact, $shelter_name);
            
            if ($stmt->execute()) {
                // Update shelter capacity
                $update_sql = "UPDATE shelters SET current_capacity = current_capacity + ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $people_count, $shelter_id);
                
                if ($update_stmt->execute()) {
                    echo "Registration successful! Shelter capacity updated from $current_capacity to $new_capacity.";
                } else {
                    echo "Error updating capacity: " . $conn->error;
                }
                $update_stmt->close();
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
            $stmt->close();
        } else {
            $available_space = $max_capacity - $current_capacity;
            echo "Error: Shelter is at full capacity. Available space: $available_space people.";
        }
    } else {
        echo "Error: Shelter not found!";
    }
    
    $shelter_stmt->close();
    $conn->close();
}
?>