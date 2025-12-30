<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists in public_users table
    $stmt = $conn->prepare("SELECT id, name, password FROM public_users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // ❌ No such account
        echo "<script>alert('No account found with this email!'); window.location.href='index.html';</script>";
        exit;
    }

    $stmt->bind_result($user_id, $user_name, $hash);
    $stmt->fetch();

    // Verify password
    if (password_verify($password, $hash)) {
        // ✅ Login successful - Record login activity
        $login_time = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, user_type, email, login_time, ip_address) VALUES (?, 'public', ?, ?, ?)");
        $log_stmt->bind_param("isss", $user_id, $email, $login_time, $ip_address);
        $log_stmt->execute();
        
        // Start session and store user data
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_type'] = 'public';
        $_SESSION['login_time'] = $login_time;
        $_SESSION['email'] = $email;
        
        header("Location: success.html");
        exit;
    } else {
        // ❌ Incorrect password
        echo "<script>alert('Incorrect password!'); window.location.href='test.html';</script>";
        exit;
    }
}
?>