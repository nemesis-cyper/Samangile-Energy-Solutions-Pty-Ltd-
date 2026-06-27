<?php
header('Content-Type: application/json; charset=utf-8');

// Database configuration
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$database = 'samangile_energy';

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$response = [
    'success' => false,
    'reply' => '',
    'message' => ''
];

try {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data && isset($data['message'])) {
        $message = htmlspecialchars(trim($data['message']));
        $sessionId = isset($data['sessionId']) ? intval($data['sessionId']) : 0;
        
        if (empty($message)) {
            $response['message'] = 'Message cannot be empty';
            echo json_encode($response);
            exit;
        }
        
        // Database connection (optional - only if database exists)
        try {
            $conn = new mysqli($host, $db_user, $db_pass, $database);
            
            if ($conn->connect_error) {
                throw new Exception('Database connection failed');
            }
            
            // Save message to database
            $stmt = $conn->prepare("INSERT INTO chat_messages (sender_name, sender_email, message) VALUES (?, ?, ?)");
            if ($stmt) {
                $sender_name = "Website Visitor";
                $sender_email = "visitor@samangile.energy";
                $stmt->bind_param("sss", $sender_name, $sender_email, $message);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->close();
        } catch (Exception $db_error) {
            // Log database error but continue
            error_log("Database error in Chat.php: " . $db_error->getMessage());
        }
        
        // Generate AI-like response based on keywords
        $reply = generateReply($message);
        
        $response['success'] = true;
        $response['reply'] = $reply;
        
    } else {
        $response['message'] = 'Invalid request method or missing data';
    }
} catch (Exception $e) {
    $response['message'] = 'Error processing chat: ' . $e->getMessage();
    error_log("Chat.php error: " . $e->getMessage());
}

echo json_encode($response);

/**
 * Generate a reply based on user message
 */
function generateReply($message) {
    $message_lower = strtolower($message);
    
    // Keywords and responses
    $responses = [
        'price' => 'Our pricing varies based on your energy needs. Please provide more details about your requirements for an accurate quote.',
        'cost' => 'We offer competitive pricing. Please request a quote to get exact pricing for your project.',
        'solar' => 'Our solar solutions are designed to save you money while reducing your carbon footprint. Would you like more information?',
        'wind' => 'Wind energy is a great renewable option. We can help you determine if wind energy is suitable for your location.',
        'installation' => 'We handle complete installation from assessment to deployment. Our team will guide you through the entire process.',
        'support' => 'We provide 24/7 support for all our customers. How can we help you?',
        'thank' => 'You\'re welcome! Is there anything else we can help you with?',
        'hello' => 'Hello! How can Samangile Energy Solutions assist you today?',
        'hi' => 'Hi there! Welcome to Samangile Energy Solutions. What can we help you with?',
        'how' => 'We\'re here to help! Please let us know what information you need.',
        'contact' => 'You can contact us at info@samangileenergy.com or call +27 123 456 7890',
        'email' => 'Our email is info@samangileenergy.com',
        'phone' => 'Our phone number is +27 123 456 7890',
        'service' => 'We offer solar, wind, hydroelectric, and energy consulting services.',
        'help' => 'I\'m here to help! Please tell me more about what you need.',
    ];
    
    // Check for keyword matches
    foreach ($responses as $keyword => $response) {
        if (strpos($message_lower, $keyword) !== false) {
            return $response;
        }
    }
    
    // Default response if no keywords match
    $default_responses = [
        'Thank you for your inquiry! Please be more specific so we can assist you better.',
        'That\'s a great question! Can you provide more details?',
        'I understand. Our team will be happy to help. Would you like to request a quote?',
        'Thank you for reaching out! One of our specialists will get back to you shortly.',
        'I appreciate your question. Please contact our team at info@samangileenergy.com for detailed assistance.'
    ];
    
    return $default_responses[array_rand($default_responses)];
}
?>
