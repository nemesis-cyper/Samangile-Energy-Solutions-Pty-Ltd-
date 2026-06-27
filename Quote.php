<?php
/**
 * Quote Request API Endpoint
 * Handles customer quote requests and notifications
 * Database: samangile_energy.quote_requests
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
    'message' => '',
    'quote_id' => null,
    'timestamp' => date('Y-m-d H:i:s')
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
        $db_connected = false;
        try {
            $conn = new mysqli($host, $db_user, $db_pass, $database);
            
            if (!$conn->connect_error) {
                $db_connected = true;
                
                // Prepare and execute query
                $stmt = $conn->prepare("INSERT INTO quote_requests (client_name, client_email, phone_number, service_type, estimated_amount, details) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    $stmt->bind_param("ssssds", $name, $email, $phone, $service, $amount, $message);
                    
                    if ($stmt->execute()) {
                        $quote_id = $stmt->insert_id;
                        
                        // Send notifications
                        sendQuoteNotifications($name, $email, $phone, $service, $amount, $message);
                        
                        $response['success'] = true;
                        $response['quote_id'] = $quote_id;
                        $response['message'] = 'Quote request submitted successfully! Our team will contact you within 24 hours.';
                    } else {
                        $response['message'] = 'Error saving quote to database: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $response['message'] = 'Database query preparation failed';
                }
                
                $conn->close();
            }
        } catch (Exception $db_error) {
            error_log("Database error in Quote.php: " . $db_error->getMessage());
            // If database fails, still send notifications and mark as success
            sendQuoteNotifications($name, $email, $phone, $service, $amount, $message);
            
            $response['success'] = true;
            $response['message'] = 'Quote request submitted! Our team will contact you shortly.';
        }
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    } else {
        $response['message'] = 'Invalid request method or missing data. Only POST is allowed.';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error processing quote: ' . $e->getMessage();
    error_log("Quote.php error: " . $e->getMessage());
}

echo json_encode($response);
exit;

/**
 * Send quote request notifications
 * Emails to admin and optionally to customer
 */
function sendQuoteNotifications($name, $email, $phone, $service, $amount, $message) {
    $admin_email = getenv('ADMIN_EMAIL') ?: 'info@samangileenergy.com';
    $from_email = getenv('FROM_EMAIL') ?: 'noreply@samangileenergy.com';
    
    // Email to admin
    $admin_subject = 'New Quote Request from ' . $name;
    $admin_message = buildAdminEmailBody($name, $email, $phone, $service, $amount, $message);
    sendEmail($admin_email, $admin_subject, $admin_message, $from_email);
    
    // Email to customer (confirmation)
    $customer_subject = 'Quote Request Received - Samangile Energy Solutions';
    $customer_message = buildCustomerEmailBody($name, $service, $amount);
    sendEmail($email, $customer_subject, $customer_message, $from_email);
    
    // Log the quote request
    logQuoteRequest($name, $email, $phone, $service, $amount);
}

/**
 * Build admin notification email
 */
function buildAdminEmailBody($name, $email, $phone, $service, $amount, $message) {
    $body = "New Quote Request Received\n";
    $body .= "================================\n\n";
    $body .= "Client Name: $name\n";
    $body .= "Email: $email\n";
    $body .= "Phone: $phone\n";
    $body .= "Service Type: " . ucfirst($service) . "\n";
    $body .= "Estimated Budget: R" . number_format($amount, 2) . "\n";
    $body .= "Date: " . date('Y-m-d H:i:s') . "\n";
    if (!empty($message)) {
        $body .= "Message: $message\n";
    }
    $body .= "\nPlease contact the client to provide a detailed quote.\n";
    $body .= "\nRegards,\nSamangile Energy Solutions\n";
    return $body;
}

/**
 * Build customer confirmation email
 */
function buildCustomerEmailBody($name, $service, $amount) {
    $body = "Dear $name,\n\n";
    $body .= "Thank you for requesting a quote from Samangile Energy Solutions!\n\n";
    $body .= "We have received your quote request for " . ucfirst($service) . " services";
    if ($amount > 0) {
        $body .= " with an estimated budget of R" . number_format($amount, 2);
    }
    $body .= ".\n\n";
    $body .= "Our team will review your request and contact you within 24 hours to discuss your project in detail.\n\n";
    $body .= "If you have any urgent questions, please call us at +27 68 732 8637\n\n";
    $body .= "Best regards,\n";
    $body .= "Samangile Energy Solutions (Pty) Ltd\n";
    $body .= "Powering Innovation. Engineering the Future.\n";
    return $body;
}

/**
 * Send email via PHP mail() or alternative
 */
function sendEmail($to, $subject, $body, $from = null) {
    if (!$from) {
        $from = 'noreply@samangileenergy.com';
    }
    
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: info@samangileenergy.com\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    
    // Uncomment below to enable email sending (requires SMTP configuration)
    // mail($to, $subject, $body, $headers);
    
    // For now, log the email instead
    error_log("Email to $to: $subject");
}

/**
 * Log quote requests to file
 */
function logQuoteRequest($name, $email, $phone, $service, $amount) {
    $log_dir = __DIR__ . '/logs';
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/quote_requests.log';
    $log_entry = date('Y-m-d H:i:s') . " | Name: $name | Email: $email | Phone: $phone | Service: $service | Amount: R" . number_format($amount, 2) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Also keep a JSON backup
    $json_file = $log_dir . '/quote_requests.json';
    $data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'amount' => $amount
    ];
    
    if (file_exists($json_file)) {
        $json_data = json_decode(file_get_contents($json_file), true);
        if (!is_array($json_data)) $json_data = [];
    } else {
        $json_data = [];
    }
    
    $json_data[] = $data;
    file_put_contents($json_file, json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
?>
