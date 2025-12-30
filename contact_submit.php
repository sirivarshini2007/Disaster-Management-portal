<?php
// Include your config file
require_once 'config.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Method not allowed'); window.location.href = 'success.html';</script>";
    exit;
}

// Get and sanitize form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate required fields
$errors = [];

if (empty($name)) {
    $errors[] = 'Full name is required';
}

if (empty($email)) {
    $errors[] = 'Email address is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address';
}

if (empty($subject)) {
    $errors[] = 'Subject is required';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

// If there are errors, return them with alert and redirect
if (!empty($errors)) {
    $error_message = "Validation failed:\\n- " . implode("\\n- ", $errors);
    echo "<script>alert('$error_message'); window.location.href = 'success.html';</script>";
    exit;
}

try {
    // Prepare SQL statement
    $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Success response with alert and redirect
        echo "<script>
            alert('Thank you for your message! We will get back to you soon.');
            window.location.href = 'success.html';
        </script>";
        
        // Optional: Send email notification (uncomment and configure if needed)
        // sendEmailNotification($name, $email, $subject, $message);
        
    } else {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
    // Close statement
    $stmt->close();
    
} catch (Exception $e) {
    // Log error (in production, log to file instead of displaying)
    error_log("Contact form error: " . $e->getMessage());
    
    // Error response with alert and redirect
    echo "<script>
        alert('Sorry, there was an error submitting your message. Please try again later.');
        window.location.href = 'success.html';
    </script>";
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
    }
}

// Optional: Email notification function
function sendEmailNotification($name, $email, $subject, $message) {
    $to = "your-email@example.com"; // Change this to your email
    $email_subject = "New Contact Form Submission: " . $subject;
    $email_body = "
    You have received a new message from your website contact form.
    
    Name: $name
    Email: $email
    Subject: $subject
    
    Message:
    $message
    ";
    
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    mail($to, $email_subject, $email_body, $headers);
}
?>