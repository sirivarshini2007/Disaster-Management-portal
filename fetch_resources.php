<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration - UPDATE THESE IF NEEDED
$host = '127.0.0.1';
$dbname = 'ddms';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to fetch all resources from your database
    $stmt = $pdo->query("SELECT id, name, description, quantity, category FROM resources ORDER BY name");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($resources) {
        echo json_encode($resources);
    } else {
        echo json_encode([]);
    }
    
} catch (PDOException $e) {
    // Return error message
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>