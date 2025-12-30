<?php
// resource_submit.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration - UPDATE THESE FOR YOUR ENVIRONMENT
$servername = "localhost";
$username = "root";  // Change if different
$password = "";      // Change if different
$dbname = "ddms";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $response = [
        "success" => false, 
        "message" => "Database connection failed: " . $conn->connect_error
    ];
    echo json_encode($response);
    exit;
}

// Set charset
$conn->set_charset("utf8mb4");

// Log the request for debugging
error_log("=== RESOURCE SUBMIT REQUEST START ===");
error_log("POST Data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if all required fields are present
        $required_fields = ['requester_name', 'requester_contact', 'requester_location', 'urgency_level', 'resources_data', 'total_requested_quantity'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
        }

        // Get form data
        $requester_name = trim($_POST['requester_name']);
        $requester_contact = trim($_POST['requester_contact']);
        $requester_location = trim($_POST['requester_location']);
        $urgency_level = trim($_POST['urgency_level']);
        $additional_notes = isset($_POST['additional_notes']) ? trim($_POST['additional_notes']) : '';
        $total_requested_quantity = intval($_POST['total_requested_quantity']);
        
        // Decode resources data
        $resources_data = json_decode($_POST['resources_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid resources data format: " . json_last_error_msg());
        }
        
        error_log("Processing request for: $requester_name, Contact: $requester_contact");
        error_log("Resources data: " . print_r($resources_data, true));

        // Validate required fields
        if (empty($requester_name) || empty($requester_contact) || empty($requester_location) || empty($urgency_level)) {
            throw new Exception("All required fields must be filled.");
        }
        
        // Validate resources data
        if (empty($resources_data) || !is_array($resources_data)) {
            throw new Exception("No resources selected.");
        }
        
        // Validate phone number format
        if (!preg_match('/^[0-9]{10}$/', $requester_contact)) {
            throw new Exception("Please enter a valid 10-digit phone number.");
        }
        
        // Validate urgency level
        $valid_urgency_levels = ['Low', 'Medium', 'High', 'Critical'];
        if (!in_array($urgency_level, $valid_urgency_levels)) {
            throw new Exception("Invalid urgency level selected.");
        }

        // Begin transaction
        $conn->begin_transaction();
        error_log("Database transaction started");

        // Check resource availability and update quantities
        foreach ($resources_data as $index => $resource) {
            if (!isset($resource['id']) || !isset($resource['quantity']) || !isset($resource['max_quantity'])) {
                throw new Exception("Invalid resource data at index $index");
            }
            
            $resource_id = intval($resource['id']);
            $requested_quantity = intval($resource['quantity']);
            $max_quantity = intval($resource['max_quantity']);
            $resource_name = isset($resource['name']) ? $resource['name'] : "Resource ID $resource_id";
            
            error_log("Processing resource: $resource_name, ID: $resource_id, Qty: $requested_quantity, Max: $max_quantity");

            // Validate quantity
            if ($requested_quantity < 1) {
                throw new Exception("Quantity must be at least 1 for $resource_name");
            }
            
            if ($requested_quantity > $max_quantity) {
                throw new Exception("Cannot request more than $max_quantity units for $resource_name. Available: $max_quantity");
            }
            
            // First check current quantity
            $check_sql = "SELECT quantity, name FROM resources WHERE id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                throw new Exception("Failed to prepare check statement: " . $conn->error);
            }
            
            $check_stmt->bind_param("i", $resource_id);
            if (!$check_stmt->execute()) {
                throw new Exception("Failed to check resource availability: " . $check_stmt->error);
            }
            
            $result = $check_stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Resource not found: $resource_name (ID: $resource_id)");
            }
            
            $row = $result->fetch_assoc();
            $current_quantity = $row['quantity'];
            $actual_name = $row['name'];
            $check_stmt->close();
            
            error_log("Current quantity for $actual_name: $current_quantity");
            
            if ($current_quantity < $requested_quantity) {
                throw new Exception("Insufficient quantity available for $actual_name. Requested: $requested_quantity, Available: $current_quantity");
            }
            
            // Update resource quantity
            $update_sql = "UPDATE resources SET quantity = quantity - ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            
            $update_stmt->bind_param("ii", $requested_quantity, $resource_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update resource quantity for $actual_name: " . $update_stmt->error);
            }
            
            // Verify update
            if ($update_stmt->affected_rows === 0) {
                throw new Exception("Failed to update quantity for $actual_name. No rows affected.");
            }
            
            $update_stmt->close();
            error_log("Successfully updated resource: $actual_name, New quantity: " . ($current_quantity - $requested_quantity));
        }
        
        // Insert request into resource_requests table
        $insert_sql = "INSERT INTO resource_requests 
                      (requester_name, requester_contact, requester_location, urgency_level, additional_notes, resources_data, total_requested_quantity) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        if (!$insert_stmt) {
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }
        
        // Convert resources data back to JSON for storage
        $resources_json = json_encode($resources_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to encode resources data: " . json_last_error_msg());
        }
        
        $insert_stmt->bind_param("ssssssi", 
            $requester_name, 
            $requester_contact, 
            $requester_location, 
            $urgency_level, 
            $additional_notes, 
            $resources_json, 
            $total_requested_quantity
        );
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to save request details: " . $insert_stmt->error);
        }
        
        $request_id = $conn->insert_id;
        $insert_stmt->close();
        
        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully. Request ID: $request_id");
        
        // Success response
        $response = [
            "success" => true, 
            "message" => "Resource request submitted successfully! Request ID: #" . $request_id,
            "request_id" => $request_id,
            "total_items" => count($resources_data),
            "total_quantity" => $total_requested_quantity
        ];
        
        echo json_encode($response);
        error_log("Success response sent: " . json_encode($response));
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn) {
            $conn->rollback();
        }
        
        $error_message = $e->getMessage();
        error_log("ERROR: " . $error_message);
        
        $response = [
            "success" => false, 
            "message" => $error_message
        ];
        echo json_encode($response);
    }
    
} else {
    $response = [
        "success" => false, 
        "message" => "Invalid request method. Expected POST, got " . $_SERVER["REQUEST_METHOD"]
    ];
    echo json_encode($response);
    error_log("Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
}

if (isset($conn) && $conn) {
    $conn->close();
}

error_log("=== RESOURCE SUBMIT REQUEST END ===");
?>