<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $location = $_POST['location'];

    // Check if user already exists
    $stmt = $conn->prepare("SELECT email FROM public_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // ❌ Email already registered
        echo "<script>alert('User already registered!'); window.location.href='test.html';</script>";
        exit;
    }

    // ✅ Proceed to register user
    $stmt = $conn->prepare("INSERT INTO public_users (name, email, password, location) VALUES (?, ?, ?, ?)");
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $location);

    if ($stmt->execute()) {
        // ✅ Redirect to success
        header("Location: success.html");
        exit;
    } else {
        echo "<script>alert('Something went wrong during registration!'); window.location.href='publogin.php';</script>";
        exit;
    }
}
?>
