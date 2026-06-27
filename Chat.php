<?php
/**
 * Chat API Endpoint
 * Handles customer inquiries and AI-powered responses
 * Database: samangile_energy.chat_messages
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'samangile_energy';

$response = [
    'success' => false,
    'reply' => '',
    'message' => '',
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['message'])) {
            $response['message'] = 'Missing message in request';
            echo json_encode($response);
            exit;
        }

        $message = htmlspecialchars(trim($data['message']));
        $sessionId = isset($data['sessionId']) ? htmlspecialchars($data['sessionId']) : 'guest_' . time();
        
        if (empty($message)) {
            $response['message'] = 'Message cannot be empty';
            echo json_encode($response);
            exit;
        }
        
        // Database connection (optional - only if database exists)
        $db_connected = false;
        try {
            $conn = new mysqli($host, $db_user, $db_pass, $database);
            
            if (!$conn->connect_error) {
                $db_connected = true;
                
                // Save message to database
                $stmt = $conn->prepare("INSERT INTO chat_messages (sender_name, sender_email, message) VALUES (?, ?, ?)");
                if ($stmt) {
                    $sender_name = "Website Visitor";
                    $sender_email = "visitor@samangile.energy";
                    $stmt->bind_param("sss", $sender_name, $sender_email, $message);
                    if ($stmt->execute()) {
                        $message_id = $stmt->insert_id;
                    }
                    $stmt->close();
                }
                
                $conn->close();
            }
        } catch (Exception $db_error) {
            // Log database error but continue
            error_log("Database error in Chat.php: " . $db_error->getMessage());
        }
        
        // Generate AI-like response based on keywords
        $reply = generateReply($message);
        
        $response['success'] = true;
        $response['reply'] = $reply;
        $response['message'] = $db_connected ? 'Message received and saved' : 'Message received (database offline)';
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    } else {
        $response['message'] = 'Invalid request method. Only POST is allowed.';
    }
} catch (Exception $e) {
    $response['message'] = 'Error processing chat: ' . $e->getMessage();
    error_log("Chat.php error: " . $e->getMessage());
}

echo json_encode($response);
exit;

/**
 * Generate a reply based on user message
 * Uses keyword matching and intelligent routing
 */
function generateReply($message) {
    $message_lower = strtolower($message);
    
    // Keywords and responses
    $responses = [
        'price|cost|pricing' => 'Our pricing varies based on your energy needs. Please provide more details about your requirements for an accurate quote. I can help you with a free assessment!',
        'solar|pv|photovoltaic' => 'Our solar solutions are designed to save you money while reducing your carbon footprint. We offer residential and commercial packages. Would you like more information?',
        'wind|turbine' => 'Wind energy is a great renewable option for suitable locations. We can help you determine if wind energy is suitable for your property.',
        'installation|install|setup' => 'We handle complete installation from assessment to deployment. Our certified team will guide you through the entire process. Would you like to discuss your project?',
        'support|help|issue|problem' => 'We provide 24/7 support for all our customers. What specific issue can we help you with?',
        'thank|thanks|appreciate' => 'You\'re welcome! Is there anything else we can help you with today?',
        'hello|hi|greetings' => 'Hello! Welcome to Samangile Energy Solutions. How can we assist you today?',
        'how|what|tell' => 'I\'m here to help! Please tell me more about what you need or ask me a specific question.',
        'contact|email|phone|reach' => 'You can contact us at info@samangileenergy.com or call +27 68 732 8637. Our team is available Monday-Friday 8am-5pm.',
        'service|offer|provide' => 'We offer solar, wind, hydroelectric, and energy consulting services. We also provide structural steel fabrication and electrical installations. Which interests you?',
        'fabrication|welding|steel|industrial' => 'Our fabrication team specializes in structural steel, MIG/TIG/ARC welding, and custom industrial projects. What are your specifications?',
        'maintenance|repair' => 'We provide industrial maintenance and repair services. Our technicians can help with preventive maintenance plans.',
        'quote|estimate|proposal' => 'I can help you get a quote! Please use our quote request form and provide details about your project. You can also call us directly.',
        'residential|commercial|industrial' => 'We work on residential, commercial, and industrial projects. Tell me more about your specific needs.',
        'hydro|hydroelectric|water' => 'Hydroelectric power is an excellent renewable option. We provide consulting and site assessments for hydro projects.',
    ];
    
    // Check for keyword matches
    foreach ($responses as $keywords => $response) {
        $keywords_array = explode('|', $keywords);
        foreach ($keywords_array as $keyword) {
            if (strpos($message_lower, trim($keyword)) !== false) {
                return $response;
            }
        }
    }
    
    // Default responses if no keywords match
    $default_responses = [
        'Thank you for your inquiry! To help you better, could you provide more details about your project?',
        'That\'s a great question! Our team specializes in renewable energy and industrial solutions. Can you tell me more?',
        'I understand your interest. Would you like to request a quote or speak with one of our specialists?',
        'Thank you for reaching out! One of our team members will be happy to help. Please use our quote form or call us.',
        'I appreciate your question. For detailed assistance, please contact our team at info@samangileenergy.com or +27 68 732 8637.',
    ];
    
    return $default_responses[array_rand($default_responses)];
}
?>
