<?php
// admin.php
session_start();
require_once 'config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = trim($_POST['admin_id']);
    $admin_pass = trim($_POST['admin_pass']);
    
    // Validate credentials
    if (!empty($admin_id) && !empty($admin_pass)) {
        
        // Insert into admin table
        $insert_sql = "INSERT INTO admin (admin_id, password) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        
        if ($stmt) {
            // Bind parameters and execute insert
            $stmt->bind_param("ss", $admin_id, $admin_pass);
            
            if ($stmt->execute()) {
                // Successfully inserted - set session variables
                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['admin_logged_in'] = true;
                
                // Close statement and connection
                $stmt->close();
                $conn->close();
                
                // Redirect to test3.html in admin folder
                header("Location: admin/test3.html");
                exit;
            } else {
                // Insert failed (likely duplicate admin_id)
                $stmt->close();
                header("Location: index.html?error=Admin%20ID%20already%20exists%20or%20insertion%20failed");
                exit;
            }
        } else {
            // Prepare statement failed
            header("Location: index.html?error=Database%20error.%20Please%20try%20again.");
            exit;
        }
    } else {
        // Empty fields
        header("Location: index.html?error=Please%20fill%20in%20all%20fields");
        exit;
    }
} else {
    // If not POST request, redirect to main page
    header("Location: index.html");
    exit;
}
?>