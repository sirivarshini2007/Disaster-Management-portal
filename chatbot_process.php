<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ddms";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get POST data
$userMessage = strtolower(trim($_POST['user_message']));
$disastersData = json_decode($_POST['disasters_data'], true);
$sheltersData = json_decode($_POST['shelters_data'], true);
$resourcesData = json_decode($_POST['resources_data'], true);
$aiSuggestionsData = json_decode($_POST['ai_suggestions_data'], true);

function generateResponse($userMessage, $pdo, $disastersData, $sheltersData, $resourcesData, $aiSuggestionsData) {
    $response = "";
    $quickActions = [];
    $navigateTo = null;
    
    // Check for disasters-related queries
    if (strpos($userMessage, 'disaster') !== false || 
        strpos($userMessage, 'flood') !== false || 
        strpos($userMessage, 'earthquake') !== false ||
        strpos($userMessage, 'active') !== false) {
        
        $activeDisasters = array_filter($disastersData, function($disaster) {
            return $disaster['status'] === 'Active';
        });
        
        if (count($activeDisasters) > 0) {
            $response = "Currently there are " . count($activeDisasters) . " active disasters:\n\n";
            foreach($activeDisasters as $disaster) {
                $response .= "• {$disaster['name']} ({$disaster['type']}) in {$disaster['location']} - {$disaster['status']}\n";
            }
            $response .= "\nI recommend checking the shelters and resources sections for assistance.";
        } else {
            $response = "No active disasters currently. All situations are being monitored.";
        }
        
        $quickActions = [
            ['text' => 'Find Shelters', 'action' => 'shelters'],
            ['text' => 'Check Resources', 'action' => 'resources']
        ];
        $navigateTo = 'disasters';
    }
    
    // Check for shelters-related queries
    else if (strpos($userMessage, 'shelter') !== false || 
             strpos($userMessage, 'safe') !== false || 
             strpos($userMessage, 'accommodation') !== false ||
             strpos($userMessage, 'stay') !== false) {
        
        $availableShelters = array_filter($sheltersData, function($shelter) {
            return intval($shelter['current_capacity']) < intval($shelter['max_capacity']);
        });
        
        if (count($availableShelters) > 0) {
            $response = "There are " . count($availableShelters) . " shelters with available space:\n\n";
            foreach($availableShelters as $shelter) {
                $available = intval($shelter['max_capacity']) - intval($shelter['current_capacity']);
                $response .= "• {$shelter['name']} in {$shelter['location']} - {$available} spots available\n";
            }
        } else {
            $response = "All shelters are currently at full capacity. Emergency teams are working to open more shelters.";
        }
        
        $quickActions = [
            ['text' => 'View on Map', 'action' => 'shelters'],
            ['text' => 'Check Resources', 'action' => 'resources']
        ];
        $navigateTo = 'shelters';
    }
    
    // Check for resources-related queries
    else if (strpos($userMessage, 'resource') !== false || 
             strpos($userMessage, 'food') !== false || 
             strpos($userMessage, 'water') !== false ||
             strpos($userMessage, 'medical') !== false ||
             strpos($userMessage, 'supply') !== false) {
        
        $lowResources = array_filter($resourcesData, function($resource) {
            return $resource['quantity'] < 50;
        });
        
        $response = "Available resources:\n\n";
        foreach($resourcesData as $resource) {
            $status = $resource['quantity'] < 50 ? "⚠️ LOW" : "✅ Available";
            $response .= "• {$resource['name']}: {$resource['quantity']} units {$status}\n";
        }
        
        if (count($lowResources) > 0) {
            $response .= "\n⚠️ Alert: " . count($lowResources) . " resources are running low and need replenishment.";
        }
        
        $quickActions = [
            ['text' => 'Request Resources', 'action' => 'resources'],
            ['text' => 'View Shelters', 'action' => 'shelters']
        ];
        $navigateTo = 'resources';
    }
    
    // Check for AI suggestions
    else if (strpos($userMessage, 'suggestion') !== false || 
             strpos($userMessage, 'advice') !== false || 
             strpos($userMessage, 'recommend') !== false ||
             strpos($userMessage, 'what should i do') !== false) {
        
        if (count($aiSuggestionsData) > 0) {
            $response = "Based on current data, here are my recommendations:\n\n";
            foreach($aiSuggestionsData as $suggestion) {
                $response .= "• {$suggestion['title']}: {$suggestion['description']}\n";
            }
        } else {
            // Fetch additional suggestions from database
            $stmt = $pdo->query("SELECT * FROM ai_suggestions ORDER BY created_at DESC LIMIT 5");
            $dbSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($dbSuggestions) > 0) {
                $response = "AI-powered recommendations:\n\n";
                foreach($dbSuggestions as $suggestion) {
                    $response .= "• {$suggestion['title']}: {$suggestion['description']}\n";
                }
            } else {
                $response = "Based on current situation, I recommend:\n1. Stay informed about weather updates\n2. Keep emergency supplies ready\n3. Know your nearest shelter location\n4. Follow official evacuation orders";
            }
        }
        
        $navigateTo = 'ai-suggestions';
    }
    
    // Check for help or general queries
    else if (strpos($userMessage, 'help') !== false || 
             strpos($userMessage, 'hello') !== false || 
             strpos($userMessage, 'hi') !== false) {
        
        $response = "I'm your Disaster Response Assistant! I can help you with:\n\n• Current disaster information\n• Shelter locations and availability\n• Resource inventory and requests\n• AI-powered safety recommendations\n• Emergency guidance\n\nWhat would you like to know?";
        
        $quickActions = [
            ['text' => 'Active Disasters', 'action' => 'disasters'],
            ['text' => 'Find Shelters', 'action' => 'shelters'],
            ['text' => 'Check Resources', 'action' => 'resources'],
            ['text' => 'Get Suggestions', 'action' => 'safety']
        ];
    }
    
    // Safety tips
    else if (strpos($userMessage, 'safety') !== false || 
             strpos($userMessage, 'tip') !== false || 
             strpos($userMessage, 'emergency') !== false) {
        
        $response = "🚨 **Emergency Safety Tips** 🚨\n\n";
        $response .= "• Stay tuned to official weather alerts\n";
        $response .= "• Prepare an emergency kit with essentials\n";
        $response .= "• Know your evacuation routes\n";
        $response .= "• Keep important documents in waterproof containers\n";
        $response .= "• Charge your electronic devices\n";
        $response .= "• Follow instructions from emergency personnel\n";
        $response .= "• Check on neighbors, especially elderly\n\n";
        $response .= "For specific disaster guidance, ask me about current situations.";
        
        $quickActions = [
            ['text' => 'Active Disasters', 'action' => 'disasters'],
            ['text' => 'Nearest Shelters', 'action' => 'shelters']
        ];
    }
    
    // Default response for unrecognized queries
    else {
        $response = "I understand you're asking about: \"{$userMessage}\". \n\nI can help you with disaster information, shelter locations, resource availability, and safety recommendations. Could you be more specific about what you need?";
        
        $quickActions = [
            ['text' => 'Disaster Info', 'action' => 'disasters'],
            ['text' => 'Shelter Locations', 'action' => 'shelters'],
            ['text' => 'Resource Status', 'action' => 'resources'],
            ['text' => 'Safety Tips', 'action' => 'safety']
        ];
    }
    
    return [
        'response' => $response,
        'quick_actions' => $quickActions,
        'navigate_to' => $navigateTo
    ];
}

// Generate response
$result = generateResponse($userMessage, $pdo, $disastersData, $sheltersData, $resourcesData, $aiSuggestionsData);

echo json_encode([
    'success' => true,
    'response' => $result['response'],
    'quick_actions' => $result['quick_actions'],
    'navigate_to' => $result['navigate_to']
]);
?>