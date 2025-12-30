<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $skills   = $_POST['skills'];

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM volunteers WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already registered! Please login.'); window.location.href='signup.php';</script>";
        exit;
    }

    // Register user
    $stmt = $conn->prepare("INSERT INTO volunteers (name, email, password, skills) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $skills);

    if ($stmt->execute()) {
        // Success: Redirect to dashboard
        header("Location: voldash.html");
        exit;
    } else {
        echo "<script>alert('Error during registration. Try again.'); window.location.href='signup.php';</script>";
        exit;
    }
}
?>
