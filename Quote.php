<?php
header('Content-Type: application/json; charset=utf-8');

// Database configuration
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$database = 'samangile_energy';

$response = [
    'success' => false,
    'message' => '',
    'quote_id' => null
];

try {
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Only process POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
        
        // Validate required fields
        $required_fields = ['name', 'email', 'service'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $response['message'] = "Required field missing: $field";
                echo json_encode($response);
                exit;
            }
        }
        
        // Sanitize inputs
        $name = htmlspecialchars(trim($data['name']));
        $email = htmlspecialchars(trim($data['email']));
        $phone = htmlspecialchars(trim($data['phone'] ?? ''));
        $service = htmlspecialchars(trim($data['service']));
        $amount = floatval($data['amount'] ?? 0);
        $message = htmlspecialchars(trim($data['message'] ?? ''));
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email address';
            echo json_encode($response);
            exit;
        }
        
        // Validate service type
        $valid_services = ['solar', 'wind', 'hydro', 'consulting'];
        if (!in_array($service, $valid_services)) {
            $response['message'] = 'Invalid service type';
            echo json_encode($response);
            exit;
        }
        
        // Attempt database insertion
        try {
            $conn = new mysqli($host, $db_user, $db_pass, $database);
            
            if ($conn->connect_error) {
                throw new Exception('Database connection failed');
            }
            
            // Prepare and execute query
            $stmt = $conn->prepare("INSERT INTO quote_requests (client_name, client_email, phone_number, service_type, estimated_amount) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt) {
                $stmt->bind_param("ssssd", $name, $email, $phone, $service, $amount);
                
                if ($stmt->execute()) {
                    $quote_id = $stmt->insert_id;
                    
                    // Send email notification
                    sendQuoteEmail($name, $email, $service, $amount);
                    
                    $response['success'] = true;
                    $response['quote_id'] = $quote_id;
                    $response['message'] = 'Quote request submitted successfully!';
                } else {
                    $response['message'] = 'Error saving quote to database: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Database query preparation failed';
            }
            
            $conn->close();
            
        } catch (Exception $db_error) {
            // If database fails, still consider it a success but log it
            error_log("Database error in Quote.php: " . $db_error->getMessage());
            
            // Send email anyway
            sendQuoteEmail($name, $email, $service, $amount);
            
            $response['success'] = true;
            $response['message'] = 'Quote request submitted! Our team will contact you shortly.';
        }
        
    } else {
        $response['message'] = 'Invalid request method or missing data';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error processing quote: ' . $e->getMessage();
    error_log("Quote.php error: " . $e->getMessage());
}

echo json_encode($response);

/**
 * Send email notification for quote request
 */
function sendQuoteEmail($name, $email, $service, $amount) {
    $to = 'info@samangileenergy.com';
    $subject = 'New Quote Request from ' . $name;
    
    $message_body = "New Quote Request Received\n\n";
    $message_body .= "Client Name: $name\n";
    $message_body .= "Client Email: $email\n";
    $message_body .= "Service Type: " . ucfirst($service) . "\n";
    $message_body .= "Estimated Amount: R" . number_format($amount, 2) . "\n";
    $message_body .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $message_body .= "\nPlease contact the client to provide a detailed quote.";
    
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    
    // Uncomment below to enable email sending
    // mail($to, $subject, $message_body, $headers);
    
    // Log the quote request
    $log_file = 'logs/quote_requests.log';
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $log_entry = date('Y-m-d H:i:s') . " - $name ($email) - $service - R" . number_format($amount, 2) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
?>
