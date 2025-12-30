<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$query = '%' . $_GET['q'] . '%';

try {
    // Search disasters
    $stmt = $pdo->prepare("SELECT name as title, description, status, 'disaster' as type, 'fa-exclamation-triangle' as icon FROM disasters WHERE name LIKE ? OR description LIKE ?");
    $stmt->execute([$query, $query]);
    $disasters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search shelters
    $stmt = $pdo->prepare("SELECT name as title, CONCAT('Capacity: ', max_capacity, ' people') as description, 
                          CASE WHEN current_capacity < max_capacity THEN 'Available' ELSE 'Full' END as status, 
                          'shelter' as type, 'fa-home' as icon FROM shelters WHERE name LIKE ? OR location LIKE ?");
    $stmt->execute([$query, $query]);
    $shelters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search resources
    $stmt = $pdo->prepare("SELECT name as title, description, CONCAT(quantity, ' units') as status, 'resource' as type, 'fa-boxes' as icon FROM resources WHERE name LIKE ? OR description LIKE ?");
    $stmt->execute([$query, $query]);
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search AI suggestions
    $stmt = $pdo->prepare("SELECT title, description, 'Recommendation' as status, 'ai-suggestion' as type, 'fa-robot' as icon FROM ai_suggestions WHERE title LIKE ? OR description LIKE ?");
    $stmt->execute([$query, $query]);
    $aiSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = array_merge($disasters, $shelters, $resources, $aiSuggestions);
    
    echo json_encode($results);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>