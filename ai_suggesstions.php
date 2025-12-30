<?php
include 'config.php';

// Get search query if provided
$search_query = isset($_GET['search_query']) ? $conn->real_escape_string($_GET['search_query']) : '';

// Fetch AI suggestions based on search query
if (!empty($search_query)) {
    $sql = "SELECT * FROM ai_suggestions WHERE title LIKE '%$search_query%' OR description LIKE '%$search_query%'";
} else {
    $sql = "SELECT * FROM ai_suggestions ORDER BY created_at DESC LIMIT 5";
}

$result = $conn->query($sql);

$suggestions = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
}

// Return JSON for AJAX or render HTML page
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($suggestions);
} else {
    // Render HTML page with suggestions
    // This would be a complete HTML page displaying the search results
}
?>