<?php
// Debug: Check if PHP is executing
echo "<!-- PHP is executing -->";

// Start output buffering to ensure clean JSON output
ob_start();

// Enable proper error reporting for debugging (temporarily display errors)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check database connection
$db_connection_error = null;
if ($conn->connect_error) {
    $db_connection_error = $conn->connect_error;
    echo "<!-- Database connection failed: " . $conn->connect_error . " -->";
    if (!empty($_GET['action']) || !empty($_POST['action'])) {
        outputJsonAndExit(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error], $conn);
    }
    // For HTML page loads, don't die - show error in HTML instead
} else {
    echo "<!-- Database connection successful -->";
}

// Track if we've already sent a response
$response_sent = false;

// Helper function to output JSON and exit
function outputJsonAndExit($data, $conn) {
    global $response_sent;

    if ($response_sent) {
        return;
    }
    $response_sent = true;

    // Clean any existing output buffers - be aggressive about this
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    // Prevent any further output
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', 0);

    // Clear any existing headers first
    header_remove();

    // Set headers first before any output
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Ensure proper encoding and sanitization
    $data = sanitizeJsonData($data);

    // Encode and output
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    // Double-check that we have valid JSON
    if ($json === false) {
        $json = json_encode(['success' => false, 'message' => 'JSON encoding error: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
    }

    // Ensure no accidental output
    echo $json;

    // Close connection properly
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }

    exit;
}

// Helper function to recursively sanitize data for JSON encoding
function sanitizeJsonData($data) {
    if (is_array($data)) {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = sanitizeJsonData($value);
        }
        return $result;
    } elseif (is_string($data)) {
        // Check if valid UTF-8, convert if necessary
        if (!mb_check_encoding($data, 'UTF-8')) {
            $data = mb_convert_encoding($data, 'UTF-8', 'auto');
        }
        // Remove control characters except for newlines, tabs, and carriage returns
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
        // Ensure no BOM
        $data = preg_replace('/^\xEF\xBB\xBF/', '', $data);
        return $data;
    }
    return $data;
}

// Helper function for safe JSON encoding
function safeJsonEncode($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

// Handle AJAX requests
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if (!empty($action)) {
        // Don't set header here - let outputJsonAndExit handle it properly
        
        if ($action === 'get_message' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($id <= 0) {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid message ID'], $conn);
            }
            
            // Try student_messages table first (new format), fallback to messages table
            $sql = "SELECT m.*, s.email as student_email, COALESCE(s.id, 0) as student_db_id FROM student_messages m LEFT JOIN students s ON m.student_id = s.id WHERE m.id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $message = $result->fetch_assoc();
                $stmt->close();
                
                // Ensure student_db_id is always set
                if (!isset($message['student_db_id']) || $message['student_db_id'] === null) {
                    $message['student_db_id'] = 0;
                }
                // Ensure student_id is always set for direct access
                if (!isset($message['student_id']) || $message['student_id'] === null) {
                    $message['student_id'] = intval($message['student_db_id'] ?? 0);
                }
                // Use the stored email, or fall back to the student's email from students table
                if (empty($message['email']) || $message['email'] === 'N/A') {
                    $message['email'] = $message['student_email'] ?? '';
                }
                unset($message['student_email']); // Remove the temporary column
                outputJsonAndExit(['success' => true, 'data' => $message], $conn);
            } else {
                $stmt->close();
                // Fallback to old messages table
                $sql = "SELECT * FROM messages WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
                }
                
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $message = $result->fetch_assoc();
                    $stmt->close();
                    outputJsonAndExit(['success' => true, 'data' => $message], $conn);
                } else {
                    $stmt->close();
                    outputJsonAndExit(['success' => false, 'message' => 'Message not found'], $conn);
                }
            }
        }
        
        if ($action === 'delete_message' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($id <= 0) {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid message ID'], $conn);
            }
            
            // Try to delete from student_messages first (new format)
            $delete_sql = "DELETE FROM student_messages WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affected_rows > 0) {
                outputJsonAndExit(['success' => true], $conn);
            }
            
            // Fallback to old messages table
            $delete_sql = "DELETE FROM messages WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $stmt->close();
                outputJsonAndExit(['success' => true], $conn);
            } else {
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Message not found'], $conn);
            }
        }
    
        if ($action === 'send_message') {
            // Validate request method
            $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
            if ($request_method !== 'POST') {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid request method. Expected POST, got ' . $request_method], $conn);
            }
            
            // Validate required fields - accept either recipient_email or custom_email
            $recipient_email = isset($_POST['recipient_email']) ? trim($_POST['recipient_email']) : '';
            $custom_email = isset($_POST['custom_email']) ? trim($_POST['custom_email']) : '';
            
            // Use custom email if recipient_email is not provided or is the default placeholder
            if (empty($recipient_email) || $recipient_email === '') {
                $recipient_email = $custom_email;
            }
            
            if (empty($recipient_email)) {
                outputJsonAndExit(['success' => false, 'message' => 'Please enter or select a recipient email'], $conn);
            }
            
            if (empty($_POST['message'])) {
                outputJsonAndExit(['success' => false, 'message' => 'Please enter a message'], $conn);
            }
            
            // Validate email format - must be a valid email
            if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid recipient email format: ' . htmlspecialchars($recipient_email)], $conn);
            }
            
            // Check if student exists in database (optional - for existing students we have more info)
            $student_db_id = 0;
            $student_name = 'Recipient';
            $check_student = $conn->prepare("SELECT id, email, first_name, last_name FROM students WHERE email = ?");
            if ($check_student) {
                $check_student->bind_param("s", $recipient_email);
                $check_student->execute();
                $student_result = $check_student->get_result();
                
                if ($student_result && $student_result->num_rows > 0) {
                    $student_row = $student_result->fetch_assoc();
                    $student_db_id = intval($student_row['id']);
                    $student_name = $student_row['first_name'] . ' ' . $student_row['last_name'];
                }
                $check_student->close();
            } else {
                // Prepare failed, continue anyway - we'll use default values
            }
            
            // Sanitize inputs safely
            $message_text = isset($_POST['message']) ? trim($_POST['message']) : '';
            $username = isset($_POST['username']) ? trim($_POST['username']) : 'Admin';
            $admin_email = isset($_POST['email']) ? trim($_POST['email']) : 'admin@school.edu';
            $recipient_name = isset($_POST['recipient_name']) ? trim($_POST['recipient_name']) : '';
            
            // Use recipient_name from form if provided, otherwise use student_name from DB or 'Recipient'
            if (!empty($recipient_name)) {
                $student_name = $recipient_name;
            }
            
            // Ensure message is not empty after trimming
            if (empty(trim($message_text))) {
                outputJsonAndExit(['success' => false, 'message' => 'Message content is empty'], $conn);
            }
            
            // Check if connection is still valid
            if ($conn->connect_errno !== 0) {
                outputJsonAndExit(['success' => false, 'message' => 'Database connection error: ' . $conn->connect_error], $conn);
            }
            
            // Use prepared statements with proper escaping
            $username_escaped = $conn->real_escape_string($username);
            $admin_email_escaped = $conn->real_escape_string($admin_email);
            $recipient_email_escaped = $conn->real_escape_string($recipient_email);
            $message_text_escaped = $conn->real_escape_string($message_text);
            $student_name_escaped = $conn->real_escape_string($student_name);
            
            // Check if messages table exists, if not create it
            $table_check = $conn->query("SHOW TABLES LIKE 'messages'");
            if ($table_check === false) {
                // Table doesn't exist, try to create it
                $create_table = "CREATE TABLE messages (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(100) NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    message TEXT NOT NULL,
                    recipient_email VARCHAR(150) NOT NULL,
                    reg_date DATETIME NOT NULL
                )";
                $create_result = $conn->query($create_table);
                if ($create_result === false) {
                    outputJsonAndExit(['success' => false, 'message' => 'Failed to create messages table: ' . $conn->error], $conn);
                }
            } else {
                if ($table_check->num_rows === 0) {
                    $create_table = "CREATE TABLE messages (
                        id INT(11) AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(100) NOT NULL,
                        email VARCHAR(150) NOT NULL,
                        message TEXT NOT NULL,
                        recipient_email VARCHAR(150) NOT NULL,
                        reg_date DATETIME NOT NULL
                    )";
                    $create_result = $conn->query($create_table);
                    if ($create_result === false) {
                        $table_check->free();
                        outputJsonAndExit(['success' => false, 'message' => 'Failed to create messages table: ' . $conn->error], $conn);
                    }
                }
                $table_check->free();
            }
            
            // First insert into messages table (for backward compatibility)
            $stmt = $conn->prepare("INSERT INTO messages (username, email, message, recipient_email, reg_date) VALUES (?, ?, ?, ?, NOW())");
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error: ' . $conn->error], $conn);
            }
            
            $stmt->bind_param("ssss", $username_escaped, $admin_email_escaped, $message_text_escaped, $recipient_email_escaped);
            
            $execute_result = $stmt->execute();
            
            if (!$execute_result) {
                $error = $stmt->error;
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Error sending message: ' . $error], $conn);
            }
            $stmt->close();
            
            // Also insert into student_messages table for bidirectional communication
            // Check if student_messages table exists, if not create it
            $table_check2 = $conn->query("SHOW TABLES LIKE 'student_messages'");
            if ($table_check2 === false) {
                // Table doesn't exist, try to create it
                $create_table2 = "CREATE TABLE IF NOT EXISTS student_messages (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    student_id INT(11) DEFAULT 0,
                    student_name VARCHAR(150) NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    category VARCHAR(50) DEFAULT 'General',
                    subject VARCHAR(255) DEFAULT 'Message from Admin',
                    message TEXT NOT NULL,
                    reply_message TEXT,
                    replied_by VARCHAR(100),
                    replied_at DATETIME,
                    sent_at DATETIME NOT NULL,
                    reg_date DATETIME
                )";
                if (!$conn->query($create_table2)) {
                    // Table creation failed but main message was sent, still return success
                    outputJsonAndExit(['success' => true, 'message' => 'Message sent successfully! (Note: Could not create student_messages table)'], $conn);
                }
            } else {
                if ($table_check2->num_rows === 0) {
                    $create_table2 = "CREATE TABLE IF NOT EXISTS student_messages (
                        id INT(11) AUTO_INCREMENT PRIMARY KEY,
                        student_id INT(11) DEFAULT 0,
                        student_name VARCHAR(150) NOT NULL,
                        email VARCHAR(150) NOT NULL,
                        category VARCHAR(50) DEFAULT 'General',
                        subject VARCHAR(255) DEFAULT 'Message from Admin',
                        message TEXT NOT NULL,
                        reply_message TEXT,
                        replied_by VARCHAR(100),
                        replied_at DATETIME,
                        sent_at DATETIME NOT NULL,
                        reg_date DATETIME
                    )";
                    if (!$conn->query($create_table2)) {
                        $table_check2->free();
                        // Table creation failed but main message was sent, still return success
                        outputJsonAndExit(['success' => true, 'message' => 'Message sent successfully! (Note: Could not create student_messages table)'], $conn);
                    }
                }
                $table_check2->free();
            }
            
            // Insert into student_messages for bidirectional communication
            $category = 'Admin Message';
            $subject = 'Message from Admin';
            $sent_at = date('Y-m-d H:i:s');
            
            // Check if student_messages table exists before inserting
            $table_check3 = $conn->query("SHOW TABLES LIKE 'student_messages'");
            if ($table_check3 && $table_check3->num_rows > 0) {
                $table_check3->free();
                
                $student_msg_stmt = $conn->prepare("INSERT INTO student_messages (student_id, student_name, email, category, subject, message, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($student_msg_stmt) {
                    $student_msg_stmt->bind_param("issssss", $student_db_id, $student_name_escaped, $recipient_email_escaped, $category, $subject, $message_text_escaped, $sent_at);
                    $student_msg_stmt->execute();
                    $student_msg_stmt->close();
                    outputJsonAndExit(['success' => true, 'message' => 'Message sent successfully to ' . htmlspecialchars($recipient_email)], $conn);
                } else {
                    // Still return success since main message was sent
                    outputJsonAndExit(['success' => true, 'message' => 'Message sent successfully to ' . htmlspecialchars($recipient_email)], $conn);
                }
            } else {
                if ($table_check3) {
                    $table_check3->free();
                }
                // Still return success since main message was sent
                outputJsonAndExit(['success' => true, 'message' => 'Message sent successfully to ' . htmlspecialchars($recipient_email)], $conn);
            }
        }
    
        // Reply to a student's message
        if ($action === 'reply_message') {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid request method'], $conn);
            }
            
            // Validate message_id - handle various input formats
            if (!isset($_POST['message_id']) || empty(trim($_POST['message_id']))) {
                outputJsonAndExit(['success' => false, 'message' => 'Message ID is required'], $conn);
            }
            
            $message_id_val = trim($_POST['message_id']);
            
            // Check if it's a valid numeric value (integer or float)
            if (!is_numeric($message_id_val)) {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid message ID format'], $conn);
            }
            
            // Convert to integer
            $message_id = intval(floatval($message_id_val));
            
            // Ensure it's a positive integer
            if ($message_id <= 0) {
                outputJsonAndExit(['success' => false, 'message' => 'Message ID must be a positive number'], $conn);
            }
            
            $reply_text = trim($_POST['reply'] ?? '');
            $admin_name = $_SESSION['username'] ?? 'Admin';
            
            if (empty($reply_text)) {
                outputJsonAndExit(['success' => false, 'message' => 'Please enter a reply message'], $conn);
            }
            
            // Sanitize the reply text
            $reply_text_sanitized = htmlspecialchars(strip_tags($reply_text));
            
            // Update the student_messages table with the reply
            $stmt = $conn->prepare("UPDATE student_messages SET reply_message = ?, replied_by = ?, replied_at = NOW() WHERE id = ?");
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $stmt->bind_param("ssi", $reply_text_sanitized, $admin_name, $message_id);
            
            if ($stmt->execute()) {
                $affected_rows = $stmt->affected_rows;
                $stmt->close();
                
                if ($affected_rows > 0) {
                    // Get student info for notification
                    $msg_sql = "SELECT student_name FROM student_messages WHERE id = ?";
                    $msg_stmt = $conn->prepare($msg_sql);
                    if ($msg_stmt) {
                        $msg_stmt->bind_param("i", $message_id);
                        $msg_stmt->execute();
                        $msg_result = $msg_stmt->get_result();
                        $msg_data = $msg_result->fetch_assoc();
                        $msg_stmt->close();
                        outputJsonAndExit([
                            'success' => true, 
                            'message' => 'Reply sent successfully!',
                            'student_name' => $msg_data['student_name'] ?? 'Student'
                        ], $conn);
                    }
                    outputJsonAndExit(['success' => true, 'message' => 'Reply sent successfully!'], $conn);
                } else {
                    outputJsonAndExit(['success' => false, 'message' => 'Message not found or already replied'], $conn);
                }
            } else {
                $error = $stmt->error;
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Error sending reply: ' . $error], $conn);
            }
        }
    
        // Generate password reset link (supports both student_id and email)
        if ($action === 'generate_reset_link' && (isset($_POST['student_id']) || isset($_POST['email']) || isset($_GET['student_id']) || isset($_GET['email']))) {
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : (isset($_GET['student_id']) ? intval($_GET['student_id']) : 0);
            $email = isset($_POST['email']) ? trim($_POST['email']) : (isset($_GET['email']) ? trim($_GET['email']) : '');
            
            // Get student info by ID or email
            $student_sql = "SELECT id, username, email, first_name, last_name FROM students WHERE ";
            $params = [];
            $types = "";
            
            if ($student_id > 0) {
                $student_sql .= "id = ?";
                $params[] = $student_id;
                $types .= "i";
            } elseif (!empty($email)) {
                $student_sql .= "email = ?";
                $params[] = $email;
                $types .= "s";
            } else {
                outputJsonAndExit(['success' => false, 'message' => 'No student identifier provided'], $conn);
            }
            
            $stmt = $conn->prepare($student_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $student_result = $stmt->get_result();
            
            if ($student_result->num_rows === 0) {
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Student not found'], $conn);
            }
            
            $student = $student_result->fetch_assoc();
            $student_id = $student['id'];
            $stmt->close();
            
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Store token in database
            $insert_sql = "INSERT INTO password_resets (student_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $stmt->bind_param("iss", $student_id, $token, $expires_at);
            
            if ($stmt->execute()) {
                $stmt->close();
                $reset_link = "http://localhost/dev/student_page/reset_password.php?token=" . $token . "&student_id=" . $student_id;
                outputJsonAndExit([
                    'success' => true,
                    'message' => 'Password reset link generated!',
                    'data' => [
                        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                        'student_email' => $student['email'],
                        'reset_link' => $reset_link,
                        'expires_at' => date('M d, Y h:i A', strtotime($expires_at))
                    ]
                ], $conn);
            } else {
                $error = $stmt->error;
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Error generating reset link: ' . $error], $conn);
            }
        }
        
        // Copy password reset link to clipboard
        if ($action === 'get_reset_link' && isset($_GET['student_id'])) {
            $student_id = intval($_GET['student_id']);
            
            if ($student_id <= 0) {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid student ID'], $conn);
            }
            
            // Check if there's an existing valid token
            $check_sql = "SELECT token, expires_at FROM password_resets WHERE student_id = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($check_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing = $result->fetch_assoc();
                $stmt->close();
                $reset_link = "http://localhost/dev/student_page/reset_password.php?token=" . $existing['token'] . "&student_id=" . $student_id;
                outputJsonAndExit([
                    'success' => true,
                    'data' => [
                        'reset_link' => $reset_link,
                        'expires_at' => date('M d, Y h:i A', strtotime($existing['expires_at'])),
                        'existing' => true
                    ]
                ], $conn);
            } else {
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'No valid reset link found. Please generate a new one.'], $conn);
            }
        }
        
        // Generate username reset link
        if ($action === 'generate_username_link' && (isset($_POST['student_id']) || isset($_POST['email']) || isset($_GET['student_id']) || isset($_GET['email']))) {
            $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : (isset($_GET['student_id']) ? intval($_GET['student_id']) : 0);
            $email = isset($_POST['email']) ? trim($_POST['email']) : (isset($_GET['email']) ? trim($_GET['email']) : '');
            
            // Get student info by ID or email
            $student_sql = "SELECT id, username, email, first_name, last_name FROM students WHERE ";
            $params = [];
            $types = "";
            
            if ($student_id > 0) {
                $student_sql .= "id = ?";
                $params[] = $student_id;
                $types .= "i";
            } elseif (!empty($email)) {
                $student_sql .= "email = ?";
                $params[] = $email;
                $types .= "s";
            } else {
                outputJsonAndExit(['success' => false, 'message' => 'No student identifier provided'], $conn);
            }
            
            $stmt = $conn->prepare($student_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $student_result = $stmt->get_result();
            
            if ($student_result->num_rows === 0) {
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Student not found'], $conn);
            }
            
            $student = $student_result->fetch_assoc();
            $student_id = $student['id'];
            $stmt->close();
            
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Store token in username_resets table
            $insert_sql = "INSERT INTO username_resets (student_id, token, new_username, expires_at) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $empty_username = '';
            $stmt->bind_param("isss", $student_id, $token, $empty_username, $expires_at);
            
            if ($stmt->execute()) {
                $stmt->close();
                $reset_link = "http://localhost/dev/student_page/reset_username.php?token=" . $token . "&student_id=" . $student_id;
                outputJsonAndExit([
                    'success' => true,
                    'message' => 'Username reset link generated!',
                    'data' => [
                        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                        'student_email' => $student['email'],
                        'current_username' => $student['username'],
                        'new_username' => '(new_username@student)',
                        'reset_link' => $reset_link,
                        'expires_at' => date('M d, Y h:i A', strtotime($expires_at))
                    ]
                ], $conn);
            } else {
                $error = $stmt->error;
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Error generating username reset link: ' . $error], $conn);
            }
        }
        
        // Get existing username reset link
        if ($action === 'get_username_link' && isset($_GET['student_id'])) {
            $student_id = intval($_GET['student_id']);
            
            if ($student_id <= 0) {
                outputJsonAndExit(['success' => false, 'message' => 'Invalid student ID'], $conn);
            }
            
            // Check if there's an existing valid token
            $check_sql = "SELECT token, expires_at FROM username_resets WHERE student_id = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($check_sql);
            if (!$stmt) {
                outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
            }
            
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing = $result->fetch_assoc();
                $stmt->close();
                $reset_link = "http://localhost/dev/student_page/reset_username.php?token=" . $existing['token'] . "&student_id=" . $student_id;
                outputJsonAndExit([
                    'success' => true,
                    'data' => [
                        'reset_link' => $reset_link,
                        'expires_at' => date('M d, Y h:i A', strtotime($existing['expires_at'])),
                        'existing' => true
                    ]
                ], $conn);
            } else {
                $stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'No valid username reset link found. Please generate a new one.'], $conn);
            }
        }
    }

    // End of AJAX action handler

    // Get all students for the compose dropdown (only for HTML page loads)
    $students = [];
    $students_sql = "SELECT id, username, email, first_name, last_name FROM students ORDER BY first_name, last_name";
    $students_result = $conn->query($students_sql);
    if ($students_result && $students_result->num_rows > 0) {
        while ($row = $students_result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    echo "<!-- Found " . count($students) . " students -->";

    // Fetch all student messages for display (only for HTML page loads)
    $messages = [];

    // First try student_messages table (new format)
    $sql = "SELECT * FROM student_messages ORDER BY sent_at DESC";
    $result = $conn->query($sql);

    if ($result === false) {
        // Table might not exist, try to create it
        echo "<!-- student_messages table query failed, attempting to create table -->";
        $create_table = "CREATE TABLE IF NOT EXISTS student_messages (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            student_id INT(11) DEFAULT 0,
            student_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            category VARCHAR(50) DEFAULT 'General',
            subject VARCHAR(255) DEFAULT 'Message from Admin',
            message TEXT NOT NULL,
            reply_message TEXT,
            replied_by VARCHAR(100),
            replied_at DATETIME,
            sent_at DATETIME NOT NULL,
            reg_date DATETIME
        )";
        if ($conn->query($create_table)) {
            echo "<!-- student_messages table created successfully -->";
            // Retry the query
            $result = $conn->query($sql);
        } else {
            echo "<!-- Failed to create student_messages table: " . $conn->error . " -->";
        }
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        echo "<!-- Found " . count($messages) . " messages in student_messages table -->";
    } else {
        // Fallback to old messages table
        echo "<!-- No messages in student_messages table, trying messages table -->";
        $sql = "SELECT
            id,
            '' as student_id,
            username as student_name,
            recipient_email as email,
            'General' as category,
            'Message from Student' as subject,
            message,
            '' as reply_message,
            '' as replied_by,
            NULL as replied_at,
            reg_date as sent_at,
            reg_date
        FROM messages ORDER BY reg_date DESC";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
            echo "<!-- Found " . count($messages) . " messages in messages table -->";
        } else {
            echo "<!-- No messages found in either table -->";
            if ($result === false) {
                echo "<!-- Query failed: " . $conn->error . " -->";
            }
        }
    }
}

// Only close connection if it hasn't been closed by outputJsonAndExit
if ($conn && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- Font Awesome 6 - Primary Icon Library -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <!-- Material Icons as additional backup -->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Unicons CSS for icon support -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <title>Student Status Management</title>
</head>
<body>
  
    <nav>
        <div class="logo-name">
             <span class="logo_name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="link-name">Appointments</span>
                </a></li>   
                <li><a href="student_status.php" >
                    <i class="fas fa-user-check"></i>
                    <span class="link-name">Student Status</span>
                </a></li>
                <li><a href="grades.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-name">Grades</span>
                </a></li>
                <li><a href="master_list.php">
                    <i class="fas fa-list-alt"></i>
                    <span class="link-name">Master List</span>
                </a></li>
           
                <li><a href="emails.php" class="active">
                    <i class="fas fa-envelope"></i>
                    <span class="link-name">Emails</span>
                </a></li>
               <li><a href="admin_user.php">
                    <i class="fas fa-user-shield"></i>
                    <span class="link-name">Admin User Management</span>
                </a></li>
            </ul>
            
            <ul class="logout-mode">
                <li><a href="#">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-name">Logout</span>
                </a></li>
                <li class="mode">
                    
               
            </li>
            </ul>
        </div>
    </nav>
    <section class="dashboard">
        <div class="top">
            <span class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </span>
        </div>
        
        <!-- Global Success/Error Messages -->
        <?php
        if (isset($_SESSION['message'])) {
            echo '<div class="alert-message success" style="margin: 90px auto 20px auto; max-width: 600px; padding: 16px 24px; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 5px solid #28a745; border-radius: 8px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2); animation: slideIn 0.4s ease-out; position: relative; z-index: 5;">';
            echo '<i class="fas fa-check-circle" style="color: #28a745; font-size: 24px; flex-shrink: 0;"></i>';
            echo '<span style="color: #155724; font-weight: 500; font-size: 15px;">' . htmlspecialchars($_SESSION['message']) . '</span>';
            unset($_SESSION['message']);
            echo '</div>';
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert-message error" style="margin: 90px auto 20px auto; max-width: 600px; padding: 16px 24px; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border-left: 5px solid #dc3545; border-radius: 8px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); animation: slideIn 0.4s ease-out; position: relative; z-index: 5;">';
            echo '<i class="fas fa-times-circle" style="color: #dc3545; font-size: 24px; flex-shrink: 0;"></i>';
            echo '<span style="color: #721c24; font-weight: 500; font-size: 15px;">' . htmlspecialchars($_SESSION['error']) . '</span>';
            unset($_SESSION['error']);
            echo '</div>';
        } ?>
        
        <div class="dash-content">

            <div class="title">
                <i class="fas fa-envelope"></i>
                <span class="text">Messages</span>
            </div>

            <?php if ($db_connection_error): ?>
                <div class="alert-message error" style="margin: 20px auto; max-width: 600px; padding: 16px 24px; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border-left: 5px solid #dc3545; border-radius: 8px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); animation: slideIn 0.4s ease-out; position: relative; z-index: 5;">
                    <i class="fas fa-exclamation-triangle" style="color: #dc3545; font-size: 24px; flex-shrink: 0;"></i>
                    <div>
                        <strong style="color: #721c24;">Database Connection Error</strong>
                        <p style="margin: 5px 0 0 0; color: #721c24; font-size: 14px;"><?php echo htmlspecialchars($db_connection_error); ?></p>
                        <p style="margin: 5px 0 0 0; color: #721c24; font-size: 12px;">Please check your database configuration and try again.</p>
                    </div>
                </div>
            <?php else: ?>

            <!-- Compose Message Button -->
            <div style="margin-bottom: 20px;">
                <button class="compose-btn" onclick="openComposeModal()">
                    <i class="fas fa-pen"></i> Compose Message
                </button>
            </div>
            
           <!-- Messages Container -->
                <div class="activity">
                    <div class="message-container" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <?php if (empty($messages)): ?>
                            <div class="no-messages">
                                <i class="uil uil-envelope-open"></i>
                                <p>No messages from students yet</p>
                            </div>
                        <?php else: ?>
                            <div class="message-list">
                                <?php foreach ($messages as $message): ?>
                                    <div class="message" onclick="showMessageDetails(<?php echo $message['id']; ?>)">
                                        <div class="message-header">
                                            <h2><?php echo htmlspecialchars($message['student_name']); ?></h2>
                                            <span class="message-date">
                                                <?php 
                                                $date = new DateTime($message['sent_at']);
                                                echo $date->format('M d, Y h:i A');
                                                ?>
                                            </span>
                                        </div>
                                        <div class="message-category">
                                            <span class="category-badge"><?php echo htmlspecialchars($message['category']); ?></span>
                                        </div>
                                        <div class="message-subject">
                                            <i class="fas fa-heading"></i> <?php echo htmlspecialchars($message['subject']); ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 150)); ?>
                                            <?php if (strlen($message['message']) > 150): ?>...<?php endif; ?>
                                        </div>
                                        <div class="view-details">
                                            <i class="uil uil-eye"></i> View Details
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
    </section>
    <!-- Message Details Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Message Details</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="messageDetails">
                    <!-- Message details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
<!-- Compose Message Modal -->
    <div id="composeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-pen"></i> Compose Message</h2>
                <span class="close-compose">&times;</span>
            </div>
            <div class="modal-body">
                <form id="composeForm" novalidate>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Select Recipient:</label>
                        <select id="recipient_email" name="recipient_email" onchange="handleRecipientChange()">
                            <option value="">-- Select a student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['email']); ?>" 
                                        data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="custom" data-name="">-- Enter custom email --</option>
                        </select>
                    </div>
                    <div class="form-group" id="custom_email_group" style="display: none;">
                        <label for="custom_email"><i class="fas fa-envelope"></i> Custom Email Address:</label>
                        <input type="email" id="custom_email" name="custom_email" placeholder="recipient@gmail.com">
                    </div>
                    <div class="form-group">
                        <label for="recipient_name"><i class="fas fa-id-card"></i> Recipient Name:</label>
                        <input type="text" id="recipient_name" name="recipient_name" readonly placeholder="Select a recipient or enter custom email">
                    </div>
                    <div class="form-group">
                        <label for="message_text"><i class="fas fa-comment"></i> Message:</label>
                        <textarea id="message_text" name="message" rows="6" placeholder="Enter your message here..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeComposeModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="send-btn" id="sendBtn" onclick="submitComposeForm()">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-reply"></i> Reply to Message</h2>
                <span class="close-reply">&times;</span>
            </div>
            <div class="modal-body">
                <div id="replyToInfo" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;" id="replyAvatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; color: #2d3748; font-size: 16px;" id="replyStudentName">Student Name</h4>
                            <p style="margin: 3px 0 0 0; color: #718096; font-size: 13px;" id="replyStudentEmail">email@example.com</p>
                        </div>
                    </div>
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                        <p style="margin: 0; font-size: 13px; color: #718096;">
                            <strong style="color: #2d3748;">Subject:</strong> <span id="replySubject">Subject</span>
                        </p>
                    </div>
                </div>
                
                <!-- Password Reset Link Section -->
                <div id="passwordResetSection" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h5 style="margin: 0 0 5px 0; color: #856404; font-size: 14px;">
                                <i class="fas fa-key"></i> Password Reset Link
                            </h5>
                            <p style="margin: 0; font-size: 12px; color: #856404;">Generate a secure link for the student to reset their password</p>
                        </div>
                        <button type="button" class="reset-link-btn" onclick="generateResetLink()" id="generateResetBtn" style="padding: 8px 16px; background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #212529; border: none; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-link"></i> Generate Link
                        </button>
                    </div>
                    
                    <!-- Reset Link Display -->
                    <div id="resetLinkDisplay" style="display: none; margin-top: 12px;">
                        <div style="background: white; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #28a745;">
                                <i class="fas fa-check-circle"></i> Link generated! Expires: <span id="resetLinkExpiry">-</span>
                            </p>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="resetLinkInput" readonly style="flex: 1; padding: 8px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa; color: #495057;">
                                <button type="button" onclick="copyResetLink()" style="padding: 8px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Copy link">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <p style="margin: 8px 0 0 0; font-size: 11px; color: #6c757d;">
                                <i class="fas fa-info-circle"></i> Click "Copy link" then paste it into your reply message
                            </p>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div id="resetLinkLoading" style="display: none; text-align: center; padding: 10px;">
                        <i class="fas fa-spinner fa-spin" style="color: #856404;"></i> Generating link...
                    </div>
                </div>
                
                <!-- Username Reset Link Section -->
                <div id="usernameResetSection" style="background: linear-gradient(135deg, #e7f3ff 0%, #d4e8ff 100%); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h5 style="margin: 0 0 5px 0; color: #667eea; font-size: 14px;">
                                <i class="fas fa-user-edit"></i> Username Reset Link
                            </h5>
                            <p style="margin: 0; font-size: 12px; color: #667eea;">Generate a secure link for the student to change their username</p>
                        </div>
                        <button type="button" class="reset-link-btn" onclick="generateUsernameResetLink()" id="generateUsernameResetBtn" style="padding: 8px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-link"></i> Generate Link
                        </button>
                    </div>
                    
                    <!-- Username Reset Link Display -->
                    <div id="usernameResetLinkDisplay" style="display: none; margin-top: 12px;">
                        <div style="background: white; padding: 10px; border-radius: 6px; border: 1px solid #ddd;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #667eea;">
                                <i class="fas fa-check-circle"></i> Username link generated! Expires: <span id="usernameResetLinkExpiry">-</span>
                            </p>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="usernameResetLinkInput" readonly style="flex: 1; padding: 8px; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa; color: #495057;">
                                <button type="button" onclick="copyUsernameResetLink()" style="padding: 8px 12px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer;" title="Copy link">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <p style="margin: 8px 0 0 0; font-size: 11px; color: #6c757d;">
                                <i class="fas fa-info-circle"></i> Click "Copy link" then paste it into your reply message
                            </p>
                        </div>
                    </div>
                    
                    <!-- Username Reset Loading State -->
                    <div id="usernameResetLinkLoading" style="display: none; text-align: center; padding: 10px;">
                        <i class="fas fa-spinner fa-spin" style="color: #667eea;"></i> Generating link...
                    </div>
                </div>
                
                <form id="replyForm" novalidate>
                    <input type="hidden" id="replyMessageId" name="message_id" value="" formnovalidate>
                    <div class="form-group">
                        <label for="reply_text"><i class="fas fa-comment-dots"></i> Your Reply:</label>
                        <textarea id="reply_text" name="reply" rows="8" placeholder="Type your reply here...

Tip: You can include the password reset link above by generating it and pasting into your message."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeReplyModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="send-btn" id="replySendBtn" onclick="submitReplyForm()">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
   // Sidebar functionality
    const body = document.querySelector("body");
    const modeToggle = body.querySelector(".mode-toggle");
    const sidebar = body.querySelector("nav");
    const sidebarToggle = body.querySelector(".sidebar-toggle");
    
    let getMode = localStorage.getItem("mode");
    if(getMode && getMode ==="dark"){
        body.classList.toggle("dark");
    }
    let getStatus = localStorage.getItem("status");
    if(getStatus && getStatus ==="close"){
        sidebar.classList.add("close");
    } else {
        sidebar.classList.remove("close");
    }
    
    if (modeToggle) {
        modeToggle.addEventListener("click", () => {
            body.classList.toggle("dark");
            if(body.classList.contains("dark")){
                localStorage.setItem("mode", "dark");
            }else{
                localStorage.setItem("mode", "light");
            }
        });
    }
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("close");
            if(sidebar.classList.contains("close")){
                localStorage.setItem("status", "close");
            }else{
                localStorage.setItem("status", "open");
            }
        });
    }


    // Get the modal
        const modal = document.getElementById('messageModal');
        
        // Get the <span> element that closes the modal
        const span = document.getElementsByClassName('close')[0];
        
        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = 'none';
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Function to show message details
        function showMessageDetails(messageId) {
            // Show loading indicator
            const detailsDiv = document.getElementById('messageDetails');
            detailsDiv.innerHTML = '<div class="loading"><i class="uil uil-spinner"></i> Loading message details...</div>';
            modal.style.display = 'block';
            
            // Fetch message details via AJAX
            fetch(`emails.php?action=get_message&id=${messageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = data.data;
                        const date = new Date(message.sent_at || message.reg_date);
                        const formattedDate = date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                        
                        // Get student name and email
                        const studentName = message.student_name || message.username || 'Unknown Student';
                        const studentEmail = message.email || 'N/A';
                        
                        detailsDiv.innerHTML = `
                            <div class="detail-row">
                                <label><i class="uil uil-user"></i> Name:</label>
                                <span>${escapeHtml(studentName)}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="uil uil-at"></i> Email:</label>
                                <span><a href="mailto:${escapeHtml(studentEmail)}">${escapeHtml(studentEmail)}</a></span>
                            </div>
                            <div class="detail-row">
                                <label><i class="uil uil-tag"></i> Category:</label>
                                <span>${escapeHtml(message.category || 'N/A')}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="uil uil-heading"></i> Subject:</label>
                                <span>${escapeHtml(message.subject || 'N/A')}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="uil uil-calendar"></i> Date:</label>
                                <span>${formattedDate}</span>
                            </div>
                            <div class="detail-row full-width">
                                <label><i class="uil uil-message"></i> Message:</label>
                                <div class="message-content">${escapeHtml(message.message)}</div>
                            </div>
                            <div class="detail-actions">
                                <button class="reply-btn" onclick="replyToMessage(${message.id})">
                                    <i class="uil uil-reply"></i> Reply
                                </button>
                                <button class="copy-btn" onclick="copyToClipboard('${escapeJs(studentEmail)}')">
                                    <i class="uil uil-copy"></i> Copy Email
                                </button>
                                <button class="delete-btn" onclick="deleteMessage(${message.id}, '${escapeJs(studentName)}')">
                                    <i class="uil uil-trash"></i> Delete
                                </button>
                            </div>
                        `;
                    } else {
                        detailsDiv.innerHTML = '<div class="error"><i class="uil uil-exclamation-triangle"></i> Message not found.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    detailsDiv.innerHTML = '<div class="error"><i class="uil uil-exclamation-triangle"></i> Error loading message details.</div>';
                });
        }
        
        // Function to escape HTML but keep URLs clickable
        function escapeHtml(text) {
            if (!text) return '';
            
            // First URL-encode special characters in URLs to protect them
            // Then convert URLs to clickable links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            
            // First escape HTML special characters (except for URLs we haven't added yet)
            const escaped = text.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '<')
                .replace(/>/g, '>')
                .replace(/"/g, '"')
                .replace(/'/g, '&#039;');
            
            // Now convert URLs to clickable links
            return escaped.replace(urlRegex, function(url) {
                // Decode HTML entities in the URL for display
                let displayUrl = url
                    .replace(/"/g, '"')
                    .replace(/&#039;/g, "'")
                    .replace(/</g, '<')
                    .replace(/>/g, '>')
                    .replace(/&amp;/g, '&');
                
                return '<a href="' + url + '" target="_blank" style="color: #667eea; text-decoration: underline; word-break: break-all;">' + displayUrl + '</a>';
            });
        }
        
        // Function to escape JavaScript strings (for inline handlers)
        function escapeJs(str) {
            if (!str) return '';
            return str.replace(/\\/g, '\\\\')
                      .replace(/'/g, "\\'")
                      .replace(/"/g, '\\"')
                      .replace(/\n/g, '\\n')
                      .replace(/\r/g, '\\r')
                      .replace(/</g, '\\x3C')
                      .replace(/>/g, '\\x3E');
        }
        
        // Function to reply to message
        function replyToMessage(messageId) {
            openReplyModal(messageId);
        }
        
        function openReplyModal(messageId) {
            document.getElementById('replyStudentName').textContent = 'Loading...';
            document.getElementById('replyStudentEmail').textContent = '...';
            document.getElementById('replyMessageId').value = messageId;
            document.getElementById('replyModal').style.display = 'block';
            
            fetch(`emails.php?action=get_message&id=${messageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = data.data;
                        const studentName = message.student_name || message.username || 'Unknown Student';
                        document.getElementById('replyStudentName').textContent = studentName;
                        document.getElementById('replyStudentEmail').textContent = message.email || 'N/A';
                    }
                });
        }
        
        function closeReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
            document.getElementById('replyForm').reset();
            document.getElementById('resetLinkDisplay').style.display = 'none';
            document.getElementById('replyMessageId').value = '';
        }
        
        let currentStudentId = null;
        
        async function generateResetLink() {
            const messageId = document.getElementById('replyMessageId').value;
            if (!messageId) {
                alert('No message selected');
                return;
            }
            
            try {
                // Show loading state
                document.getElementById('resetLinkLoading').style.display = 'block';
                document.getElementById('resetLinkDisplay').style.display = 'none';
                document.getElementById('generateResetBtn').disabled = true;
                
                // First, get the message details
                const messageResponse = await fetch(`emails.php?action=get_message&id=${messageId}`);
                const messageData = await messageResponse.json();
                
                if (!messageData.success) {
                    throw new Error('Could not get message data');
                }
                
                const message = messageData.data;
                const studentDbId = message.student_db_id;
                const studentEmail = message.email || '';
                
                // Prepare parameters manually to avoid Safari validation issues
                let url = 'emails.php?action=generate_reset_link';
                
                if (studentDbId && !isNaN(parseInt(studentDbId)) && parseInt(studentDbId) > 0) {
                    url += '&student_id=' + encodeURIComponent(parseInt(studentDbId));
                } else if (studentEmail && studentEmail !== 'N/A' && studentEmail.trim() !== '') {
                    url += '&email=' + encodeURIComponent(studentEmail.trim());
                } else {
                    throw new Error('No valid student identifier found');
                }
                
                // Generate the reset link using GET request instead of POST
                const resetResponse = await fetch(url, {
                    method: 'GET'
                });
                
                // Check if response is valid
                if (!resetResponse.ok) {
                    throw new Error('Network response was not ok: ' + resetResponse.status);
                }
                
                const resetData = await resetResponse.json();
                
                document.getElementById('resetLinkLoading').style.display = 'none';
                document.getElementById('generateResetBtn').disabled = false;
                
                if (resetData.success) {
                    document.getElementById('resetLinkDisplay').style.display = 'block';
                    document.getElementById('resetLinkInput').value = resetData.data.reset_link;
                    document.getElementById('resetLinkExpiry').textContent = resetData.data.expires_at;
                } else {
                    alert(resetData.message || 'Error generating reset link');
                }
            } catch (error) {
                document.getElementById('resetLinkLoading').style.display = 'none';
                document.getElementById('generateResetBtn').disabled = false;
                alert('Error generating reset link: ' + error.message);
            }
        }
        
        function copyResetLink() {
            const linkInput = document.getElementById('resetLinkInput');
            linkInput.select();
            navigator.clipboard.writeText(linkInput.value).then(function() {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = '<i class="uil uil-check"></i> Password reset link copied!';
                notification.style.background = '#27ae60';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 2000);
            }).catch(function() {
                alert('Failed to copy link');
            });
        }
        
        // Username Reset Link Functions
        async function generateUsernameResetLink() {
            const messageId = document.getElementById('replyMessageId').value;
            if (!messageId) {
                alert('No message selected');
                return;
            }
            
            try {
                // Show loading state
                document.getElementById('usernameResetLinkLoading').style.display = 'block';
                document.getElementById('usernameResetLinkDisplay').style.display = 'none';
                document.getElementById('generateUsernameResetBtn').disabled = true;
                
                // First, get the message details
                const messageResponse = await fetch(`emails.php?action=get_message&id=${messageId}`);
                const messageData = await messageResponse.json();
                
                if (!messageData.success) {
                    throw new Error('Could not get message data');
                }
                
                const message = messageData.data;
                const studentDbId = message.student_db_id;
                const studentEmail = message.email || '';
                
                // Prepare parameters manually to avoid Safari validation issues
                let url = 'emails.php?action=generate_username_link';
                
                if (studentDbId && !isNaN(parseInt(studentDbId)) && parseInt(studentDbId) > 0) {
                    url += '&student_id=' + encodeURIComponent(parseInt(studentDbId));
                } else if (studentEmail && studentEmail !== 'N/A' && studentEmail.trim() !== '') {
                    url += '&email=' + encodeURIComponent(studentEmail.trim());
                } else {
                    throw new Error('No valid student identifier found');
                }
                
                // Generate the username reset link using GET request
                const resetResponse = await fetch(url, {
                    method: 'GET'
                });
                
                // Check if response is valid
                if (!resetResponse.ok) {
                    throw new Error('Network response was not ok: ' + resetResponse.status);
                }
                
                const resetData = await resetResponse.json();
                
                document.getElementById('usernameResetLinkLoading').style.display = 'none';
                document.getElementById('generateUsernameResetBtn').disabled = false;
                
                if (resetData.success) {
                    document.getElementById('usernameResetLinkDisplay').style.display = 'block';
                    document.getElementById('usernameResetLinkInput').value = resetData.data.reset_link;
                    document.getElementById('usernameResetLinkExpiry').textContent = resetData.data.expires_at;
                } else {
                    alert(resetData.message || 'Error generating username reset link');
                }
            } catch (error) {
                document.getElementById('usernameResetLinkLoading').style.display = 'none';
                document.getElementById('generateUsernameResetBtn').disabled = false;
                alert('Error generating username reset link: ' + error.message);
            }
        }
        
        function copyUsernameResetLink() {
            const linkInput = document.getElementById('usernameResetLinkInput');
            linkInput.select();
            navigator.clipboard.writeText(linkInput.value).then(function() {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = '<i class="uil uil-check"></i> Username reset link copied!';
                notification.style.background = '#27ae60';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 2000);
            }).catch(function() {
                alert('Failed to copy link');
            });
        }
        
        const replyForm = document.getElementById('replyForm');
        const replyModalElement = document.getElementById('replyModal');
        const closeReplySpan = document.getElementsByClassName('close-reply')[0];
        
        closeReplySpan.onclick = function() {
            closeReplyModal();
        }
        
        window.onclick = function(event) {
            if (event.target === replyModalElement) {
                closeReplyModal();
            }
        }
        
        // New submitReplyForm function - bypasses browser validation
        function submitReplyForm() {
            const messageIdInput = document.getElementById('replyMessageId');
            let messageId = messageIdInput.value;
            const replyText = document.getElementById('reply_text').value;
            const replySendBtn = document.getElementById('replySendBtn');
            
            if (!messageId || !replyText) {
                alert('Please enter a reply message');
                return;
            }
            
            // Clean message_id - remove any non-numeric characters
            messageId = messageId.replace(/\D/g, '');
            
            // Ensure message_id is a valid positive integer
            const messageIdNum = parseInt(messageId, 10);
            
            if (isNaN(messageIdNum) || messageIdNum <= 0) {
                alert('Invalid message ID. Please try opening the message again.');
                return;
            }
            
            // Update the hidden input with cleaned value
            messageIdInput.value = messageIdNum.toString();
            
            replySendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            replySendBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'reply_message');
            formData.append('message_id', messageIdNum.toString());
            formData.append('reply', replyText);
            
            fetch('emails.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                // Check if response is OK and has JSON content-type
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                
                // Get the content type
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    // Try to get text for debugging
                    return response.text().then(text => {
                        console.error('Non-JSON response received:', text.substring(0, 500));
                        throw new Error('Invalid response format - expected JSON');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const notification = document.createElement('div');
                    notification.className = 'notification';
                    notification.innerHTML = '<i class="uil uil-check"></i> Reply sent successfully!';
                    notification.style.background = '#27ae60';
                    document.body.appendChild(notification);
                    
                    closeReplyModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to send reply');
                }
            })
            .catch(error => {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = '<i class="uil uil-exclamation-triangle"></i> ' + (error.message || 'Error sending reply!');
                notification.style.background = '#e74c3c';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            })
            .finally(() => {
                replySendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reply';
                replySendBtn.disabled = false;
            });
        }
        
        // Function to copy email to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = '<i class="uil uil-check"></i> Email copied to clipboard!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 2000);
            }).catch(function() {
                alert('Failed to copy email address');
            });
        }
        
        // Function to delete message
        function deleteMessage(messageId, username) {
            if (confirm(`Are you sure you want to delete the message from "${username}"?\n\nThis action cannot be undone.`)) {
                // Show loading state
                const button = event.target.closest('.delete-btn');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="uil uil-spinner"></i> Deleting...';
                button.disabled = true;
                
                fetch(`emails.php?action=delete_message&id=${messageId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success notification
                            const notification = document.createElement('div');
                            notification.className = 'notification';
                            notification.innerHTML = '<i class="uil uil-check"></i> Message deleted successfully!';
                            notification.style.background = '#27ae60';
                            document.body.appendChild(notification);
                            
                            // Close modal and refresh page
                            modal.style.display = 'none';
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            throw new Error(data.message || 'Failed to delete message');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Show error notification
                        const notification = document.createElement('div');
                        notification.className = 'notification';
                        notification.innerHTML = '<i class="uil uil-exclamation-triangle"></i> Error deleting message!';
                        notification.style.background = '#e74c3c';
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            notification.remove();
                        }, 3000);
                    })
                    .finally(() => {
                        // Restore button state
                        button.innerHTML = originalText;
                        button.disabled = false;
                    });
            }
        }
        
        // Compose Message Functions
        const composeModal = document.getElementById('composeModal');
        const closeComposeSpan = document.getElementsByClassName('close-compose')[0];
        const recipientSelect = document.getElementById('recipient_email');
        const recipientNameInput = document.getElementById('recipient_name');
        const composeForm = document.getElementById('composeForm');
        
        function openComposeModal() {
            composeModal.style.display = 'block';
        }
        
        function closeComposeModal() {
            composeModal.style.display = 'none';
            composeForm.reset();
            recipientNameInput.value = '';
            document.getElementById('custom_email_group').style.display = 'none';
        }
        
        // Close compose modal on X click
        closeComposeSpan.onclick = function() {
            closeComposeModal();
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target === composeModal) {
                closeComposeModal();
            }
        }
        
        // Update recipient name when selection changes
        recipientSelect.addEventListener('change', function() {
            handleRecipientChange();
        });

        function handleRecipientChange() {
            const selectedOption = recipientSelect.options[recipientSelect.selectedIndex];
            
            if (recipientSelect.value === 'custom') {
                // Show custom email input
                document.getElementById('custom_email_group').style.display = 'block';
                document.getElementById('custom_email').value = '';
                recipientNameInput.value = '';
                document.getElementById('custom_email').focus();
            } else if (recipientSelect.value) {
                // Hide custom email input
                document.getElementById('custom_email_group').style.display = 'none';
                recipientNameInput.value = selectedOption.getAttribute('data-name') || '';
            } else {
                // Hide custom email input and clear name
                document.getElementById('custom_email_group').style.display = 'none';
                recipientNameInput.value = '';
            }
        }
        
        // New submitComposeForm function - bypasses browser validation
        function submitComposeForm() {
            const recipient_email = recipientSelect.value;
            const custom_email = document.getElementById('custom_email').value.trim();
            const recipient_name = recipientNameInput.value;
            const message = document.getElementById('message_text').value;
            const sendBtn = document.getElementById('sendBtn');
            
            // Handle custom email selection
            let finalRecipientEmail = recipient_email;
            if (recipient_email === 'custom') {
                if (!custom_email) {
                    alert('Please enter a custom email address');
                    return;
                }
                // Validate custom email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(custom_email)) {
                    alert('Please enter a valid email address');
                    return;
                }
                finalRecipientEmail = custom_email;
            }
            
            if (!finalRecipientEmail || finalRecipientEmail === 'custom') {
                alert('Please select or enter a recipient email');
                return;
            }
            
            if (!message.trim()) {
                alert('Please enter a message');
                return;
            }
            
            // Show loading state
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            sendBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('recipient_email', finalRecipientEmail);  // Always send the final email
            formData.append('custom_email', custom_email);
            formData.append('recipient_name', recipient_name);
            formData.append('message', message);
            formData.append('username', 'Admin');
            formData.append('email', 'admin@school.edu');
            
            fetch('emails.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                // Check if response is OK and has JSON content-type
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                
                // Get the content type
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    // Try to get text for debugging
                    return response.text().then(text => {
                        console.error('Non-JSON response received:', text.substring(0, 500));
                        throw new Error('Invalid response format - expected JSON');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success notification
                    const notification = document.createElement('div');
                    notification.className = 'notification';
                    notification.innerHTML = '<i class="uil uil-check"></i> ' + data.message;
                    notification.style.background = '#27ae60';
                    document.body.appendChild(notification);
                    
                    // Close modal and refresh page
                    closeComposeModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to send message');
                }
            })
            .catch(error => {
                // Show error notification
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = '<i class="uil uil-exclamation-triangle"></i> ' + (error.message || 'Error sending message!');
                notification.style.background = '#e74c3c';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            })
            .finally(() => {
                // Restore button state
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Message';
                sendBtn.disabled = false;
            });
        }
    </script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
    
    :root{
        /* ===== Colors ===== */
        --primary-color: #0E4BF1;
        --panel-color: #FFF;
        --text-color: #000;
        --black-light-color: #707070;
        --border-color: #e6e5e5;
        --toggle-color: #DDD;
        --box1-color: #4DA3FF;
        --box2-color: #FFE6AC;
        --box3-color: #E7D1FC;
        --title-icon-color: #fff;
        
        /* ====== Transition ====== */
        --tran-05: all 0.5s ease;
        --tran-03: all 0.3s ease;
    }
    
    *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }
    
    body{
        min-height: 100vh;
        background-color: var(--panel-color); /* Changed from blue to white */
    }

.message-list {
    max-height: 500px;
    overflow-y: auto;
    margin-bottom: 20px;
}

.message {
    background: white;
    padding: 15px;
    margin: 8px 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 4px solid var(--primary-color);
}

.message:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    border-left-color: #0b3cc1;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.message-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-color);
}

.message-date {
    font-size: 12px;
    color: var(--black-light-color);
    background: var(--border-color);
    padding: 4px 8px;
    border-radius: 12px;
    white-space: nowrap;
}

.message-preview {
    margin: 8px 0;
    color: var(--black-light-color);
    line-height: 1.4;
    font-size: 14px;
}

.message-email {
    font-size: 12px;
    color: var(--primary-color);
    margin: 5px 0;
    font-weight: 500;
}

.message-category {
    margin: 8px 0;
}

.category-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-badge.Academic,
.category-badge.username { 
    background: rgba(102, 126, 234, 0.1); 
    color: #667eea; 
}

.category-badge.Grades,
.category-badge.password { 
    background: rgba(40, 167, 69, 0.1); 
    color: #28a745; 
}

.category-badge.Schedule,
.category-badge.shift { 
    background: rgba(255, 193, 7, 0.1); 
    color: #d39e00; 
}

.category-badge.Enrollment { 
    background: rgba(23, 162, 184, 0.1); 
    color: #17a2b8; 
}

.category-badge.Documents { 
    background: rgba(111, 66, 193, 0.1); 
    color: #6f42c1; 
}

.category-badge.Technical { 
    background: rgba(220, 53, 69, 0.1); 
    color: #dc3545; 
}

.category-badge.Feedback { 
    background: rgba(253, 126, 20, 0.1); 
    color: #fd7e14; 
}

.category-badge.Other { 
    background: rgba(108, 117, 125, 0.1); 
    color: #6c757d; 
}

.message-subject {
    font-size: 14px;
    color: var(--text-color);
    font-weight: 500;
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.message-subject i {
    color: var(--primary-color);
    font-size: 14px;
}
/* Navigation styles - Updated to match student_status.php */
    nav {
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%) !important;
        border-right: none !important;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    }

    nav .logo-name {
        display: flex;
        align-items: center;
        padding: 15px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 15px;
    }

    nav .logo-image {
        display: flex;
        justify-content: center;
        align-items: center;
        min-width: 45px;
        border-radius: 12px;
    }

    nav .logo-image i {
        font-size: 28px;
        color: #fff !important;
    }

    nav .logo-name .logo_name {
        font-size: 18px;
        font-weight: 600;
        color: #fff !important;
        margin-left: 12px;
        white-space: nowrap;
        transition: opacity 0.3s ease;
    }

    nav.close .logo_name {
        opacity: 0;
        pointer-events: none;
    }

    nav .menu-items {
        height: calc(100% - 70px);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow-y: auto;
    }

    nav .menu-items::-webkit-scrollbar {
        display: none;
    }

    .nav-links li {
        position: relative;
        margin: 5px 0;
    }

    .nav-links li a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .nav-links li a:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateX(5px);
    }

    .nav-links li a.active {
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .nav-links li a i {
        font-size: 20px;
        min-width: 25px;
        color: rgba(255, 255, 255, 0.8) !important;
        transition: color 0.3s ease;
    }

    .nav-links li a:hover i,
    .nav-links li a.active i {
        color: #fff !important;
    }

    .nav-links li a .link-name {
        font-size: 15px !important;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9) !important;
        white-space: nowrap;
        transition: opacity 0.3s ease;
    }

    nav.close .nav-links li a .link-name {
        opacity: 0;
        pointer-events: none;
    }

    .menu-items .logout-mode {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        padding-top: 15px;
    }

    /* Sidebar Toggle Button */
    .sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #fff;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .sidebar-toggle:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .sidebar-toggle:hover i {
        color: #fff;
    }

    .sidebar-toggle i {
        font-size: 20px;
        color: #667eea;
        transition: color 0.3s ease;
    }

    /* Dashboard Section */
    .dashboard {
        position: relative;
        left: 250px;
        width: calc(100% - 250px);
        min-height: 100vh;
        background: #f5f7fa;
        padding: 20px 30px;
        transition: all 0.4s ease;
    }

    nav.close ~ .dashboard {
        left: 73px;
        width: calc(100% - 73px);
    }

    .dashboard .top {
        position: fixed;
        top: 0;
        left: 250px;
        display: flex;
        width: calc(100% - 250px);
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: #f5f7fa;
        transition: all 0.4s ease;
        z-index: 10;
    }

    nav.close ~ .dashboard .top {
        left: 73px;
        width: calc(100% - 73px);
    }

    /* Title Section */
    .dashboard .title {
        display: flex;
        align-items: center;
        margin: 80px 0 30px 0;
    }

    .dashboard .title i {
        position: relative;
        height: 40px;
        width: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .dashboard .title .text {
        font-size: 24px;
        font-weight: 600;
        color: #2d3748;
        margin-left: 12px;
    }

    /* Body Background */
    body {
        background: #f5f7fa;
        min-height: 100vh;
    }
    
.view-details {
    text-align: right;
    margin-top: 8px;
    font-size: 12px;
    color: var(--primary-color);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.no-messages {
    text-align: center;
    padding: 60px 20px;
    color: var(--black-light-color);
}

.no-messages i {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-messages p {
    font-size: 16px;
    margin: 0;
}

/* Compose Button */
.compose-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #0E4BF1, #0b3cc1);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(14, 75, 241, 0.3);
}

.compose-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(14, 75, 241, 0.4);
}

.compose-btn i {
    font-size: 18px;
}

/* Compose Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group label i {
    color: var(--primary-color);
    font-size: 16px;
}

.form-group select,
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    background-color: var(--panel-color);
    color: var(--text-color);
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(14, 75, 241, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-group select {
    cursor: pointer;
}

.form-group input[readonly] {
    background-color: var(--border-color);
    cursor: default;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.cancel-btn,
.send-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.cancel-btn {
    background: var(--border-color);
    color: var(--text-color);
}

.cancel-btn:hover {
    background: #e0e0e0;
}

.send-btn {
    background: linear-gradient(135deg, #27ae60, #219a52);
    color: white;
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
}

.send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.close-compose {
    color: var(--black-light-color);
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close-compose:hover {
    color: var(--primary-color);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
}

.modal-content {
    background-color: var(--panel-color);
    margin: 5% auto;
    border: none;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: var(--text-color);
}

.close {
    color: var(--black-light-color);
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: var(--primary-color);
}

.modal-body {
    padding: 30px;
    max-height: 60vh;
    overflow-y: auto;
}

.detail-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    gap: 15px;
}

.detail-row.full-width {
    flex-direction: column;
}

.detail-row label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: var(--text-color);
    min-width: 100px;
    font-size: 14px;
}

.detail-row label i {
    color: var(--primary-color);
    font-size: 16px;
}

.detail-row span {
    flex: 1;
    color: var(--black-light-color);
    line-height: 1.5;
}

.detail-row a {
    color: var(--primary-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.detail-row a:hover {
    color: #0b3cc1;
    text-decoration: underline;
}

.message-content {
    background: var(--border-color);
    padding: 15px;
    border-radius: 8px;
    margin-top: 8px;
    white-space: pre-wrap;
    line-height: 1.6;
    color: var(--text-color);
    border-left: 4px solid var(--primary-color);
}

.detail-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.reply-btn, .copy-btn, .delete-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.reply-btn {
    background: linear-gradient(135deg, var(--primary-color), #0b3cc1);
    color: white;
}

.reply-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14, 75, 241, 0.3);
}

.copy-btn {
    background: var(--border-color);
    color: var(--text-color);
}

.copy-btn:hover {
    background: var(--primary-color);
    color: white;
}

.delete-btn {
    background: #e74c3c;
    color: white;
}

.delete-btn:hover {
    background: #c0392b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.delete-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.loading, .error {
    text-align: center;
    padding: 40px 20px;
    color: var(--black-light-color);
}

.loading i, .error i {
    font-size: 24px;
    margin-bottom: 15px;
    display: block;
}

.error {
    color: #e74c3c;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #27ae60;
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
    z-index: 1001;
    animation: notificationSlideIn 0.3s ease-out;
}

@keyframes notificationSlideIn {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.notification i {
    margin-right: 8px;
}

.message-input {
    display: flex;
}

.message-input textarea {
    flex: 1;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.message-input button {
    padding: 10px 15px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.message-input button:hover {
    background: #0056b3;
}
.data-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px; /* Adjust spacing as needed */
}

.data {
    flex: 1; /* Allows each data item to take equal space */
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Aligns titles and lists to the start */
}

.data-title {
    font-weight: bold; /* Makes the title stand out */
}

.data-list {
    margin-top: 5px; /* Adds space between title and list */
}

/* === Custom Scroll Bar CSS === */
::-webkit-scrollbar {
    width: 8px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 12px;
    transition: all 0.3s ease;
}
::-webkit-scrollbar-thumb:hover {
    background: #0b3cc1;
}
body.dark::-webkit-scrollbar-thumb:hover,
body.dark .activity-data::-webkit-scrollbar-thumb:hover{
    background: #3A3B3C;
}

/* Force white sidebar colors - Override all conflicting styles */
nav,
nav.close {
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%) !important;
    background-color: transparent !important;
}

nav .logo-image i,
nav.close .logo-image i {
    color: #fff !important;
}

nav .logo-name .logo_name,
nav.close .logo-name .logo_name {
    color: #fff !important;
}

.menu-items li a i,
nav .menu-items li a i,
.nav-links li a i {
    color: rgba(255, 255, 255, 0.8) !important;
}

.menu-items li a .link-name,
nav .menu-items li a .link-name,
.nav-links li a .link-name {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 15px !important;
}

.menu-items li a:hover i,
.nav-links li a:hover i {
    color: #fff !important;
}

.menu-items li a:hover .link-name,
.nav-links li a:hover .link-name {
    color: #fff !important;
}

body.dark{
    --primary-color: #3A3B3C;
    --panel-color: #242526;
    --text-color: #CCC;
    --black-light-color: #CCC;
    --border-color: #4D4C4C;
    --toggle-color: #FFF;
    --box1-color: #3A3B3C;
    --box2-color: #3A3B3C;
    --box3-color: #3A3B3C;
    --title-icon-color: #CCC;
}

nav{
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 250px;
    padding: 10px 14px;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%) !important;
    background-color: transparent !important;
    border-right: none !important;
    transition: all 0.4s ease;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
}
nav.close{
    width: 73px;
}
nav .logo-name{
    display: flex;
    align-items: center;
}
nav .logo-image{
    display: flex;
    justify-content: center;
    min-width: 45px;
}
nav .logo-image i{
    font-size: 40px;
    color: var(--text-color);
}
nav .logo-name .logo_name{
    font-size: 22px;
    font-weight: 600;
    color: var(--text-color);
    margin-left: 14px;
    transition: var(--tran-05);
}
nav.close .logo_name{
    opacity: 0;
    pointer-events: none;
}
nav .menu-items{
    margin-top: 40px;
    height: calc(100% - 90px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.menu-items li{
    list-style: none;
}
.menu-items li a{
    display: flex;
    align-items: center;
    height: 50px;
    text-decoration: none;
    position: relative;
    color: rgba(255, 255, 255, 0.9) !important;
}
.nav-links li a:hover:before{
    content: "";
    position: absolute;
    left: -7px;
    height: 5px;
    width: 5px;
    border-radius: 50%;
    background-color: #fff;
}
body.dark li a:hover:before{
    background-color: #fff;
}
.menu-items li a i{
    font-size: 20px !important;
    min-width: 25px !important;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.8) !important;
}
.menu-items li a .link-name{
    font-size: 15px !important;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9) !important;
    transition: var(--tran-05);
}
nav.close li a .link-name{
    opacity: 0;
    pointer-events: none;
}
.nav-links li a:hover i,
.nav-links li a:hover .link-name{
    color: #fff !important;
}
body.dark .nav-links li a:hover i,
body.dark .nav-links li a:hover .link-name{
    color: #fff !important;
}
.menu-items .logout-mode{
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}
.menu-items .mode{
    display: flex;
    align-items: center;
    white-space: nowrap;
}
.menu-items .mode-toggle{
    position: absolute;
    right: 14px;
    height: 50px;
    min-width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.mode-toggle .switch{
    position: relative;
    display: inline-block;
    height: 22px;
    width: 40px;
    border-radius: 25px;
    background-color: var(--toggle-color);
}
.switch:before{
    content: "";
    position: absolute;
    left: 5px;
    top: 50%;
    transform: translateY(-50%);
    height: 15px;
    width: 15px;
    background-color: var(--panel-color);
    border-radius: 50%;
    transition: var(--tran-03);
}
body.dark .switch:before{
    left: 20px;
}
.dashboard{
    position: relative;
    left: 250px;
    background-color: var(--panel-color);
    min-height: 100vh;
    width: calc(100% - 250px);
    padding: 10px 14px;
    transition: var(--tran-05);
}

nav.close ~ .dashboard .top{
    left: 73px;
    width: calc(100% - 73px);
}
.dashboard .top .sidebar-toggle{
    font-size: 26px;
    color: var(--text-color);
    cursor: pointer;
}
.dashboard .top .search-box{
    position: relative;
    height: 45px;
    max-width: 600px;
    width: 100%;
    margin: 0 30px;
}
.top .search-box input{
    position: absolute;
    border: 1px solid var(--border-color);
    background-color: var(--panel-color);
    padding: 0 25px 0 50px;
    border-radius: 5px;
    height: 100%;
    width: 100%;
    color: var(--text-color);
    font-size: 15px;
    font-weight: 400;
    outline: none;
}
.top .search-box i{
    position: absolute;
    left: 15px;
    font-size: 22px;
    z-index: 10;
    top: 50%;
    transform: translateY(-50%);
    color: var(--black-light-color);
}
.top img{
    width: 40px;
    border-radius: 50%;
}

.dash-content .title{
    display: flex;
    align-items: center;
    margin: 60px 0 30px 0;
}
.dash-content .title i{
    position: relative;
    height: 35px;
    width: 35px;
    background-color: var(--primary-color);
    border-radius: 6px;
    color: var(--title-icon-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.dash-content .title .text{
    font-size: 24px;
    font-weight: 500;
    color: var(--text-color);
    margin-left: 10px;
}
.boxes .box i{
    font-size: 35px;
    color: var(--text-color);
}
.boxes .box .text{
    white-space: nowrap;
    font-size: 18px;
    font-weight: 500;
    color: var(--text-color);
}
.boxes .box .number{
    font-size: 40px;
    font-weight: 500;
    color: var(--text-color);
}
.boxes .box.box2{
    background-color: var(--box2-color);
}
.boxes .box.box3{
    background-color: var(--box3-color);
}

.activity .activity-data{
    display: flex;
}
.activity-data .data{
    display: flex;
    flex-direction: column;
    margin: 0 15px;
}
.activity-data .data-title{
    font-size: 20px;
    font-weight: 500;
    color: var(--text-color);
}
.activity-data .data .data-list{
    font-size: 18px;
    font-weight: 400;
    margin-top: 20px;
    white-space: nowrap;
    color: var(--text-color);
}
@media (max-width: 1000px) {
    nav{
        width: 73px;
    }
    nav.close{
        width: 250px;
    }
    nav .logo_name{
        opacity: 0;
        pointer-events: none;
    }
    nav.close .logo_name{
        opacity: 1;
        pointer-events: auto;
    }
    nav li a .link-name{
        opacity: 0;
        pointer-events: none;
    }
    nav.close li a .link-name{
        opacity: 1;
        pointer-events: auto;
    }
    nav ~ .dashboard{
        left: 73px;
        width: calc(100% - 73px);
    }
    nav.close ~ .dashboard{
        left: 250px;
        width: calc(100% - 250px);
    }
    nav ~ .dashboard .top{
        left: 73px;
        width: calc(100% - 73px);
    }
    nav.close ~ .dashboard .top{
        left: 250px;
        width: calc(100% - 250px);
    }
    .activity .activity-data{
        overflow-X: scroll;
    }
}
@media (max-width: 780px) {
    .dash-content .boxes .box{
        width: calc(100% / 2 - 15px);
        margin-top: 15px;
    }
}
@media (max-width: 560px) {
    .dash-content .boxes .box{
        width: 100% ;
    }
}
@media (max-width: 400px) {
    nav{
        width: 0px;
    }
    nav.close{
        width: 73px;
    }
    nav .logo_name{
        opacity: 0;
        pointer-events: none;
    }
    nav.close .logo_name{
        opacity: 0;
        pointer-events: none;
    }
    nav li a .link-name{
        opacity: 0;
        pointer-events: none;
    }
    nav.close li a .link-name{
        opacity: 0;
        pointer-events: none;
    }
    nav ~ .dashboard{
        left: 0;
        width: 100%;
    }
    nav.close ~ .dashboard{
        left: 73px;
        width: calc(100% - 73px);
    }
    nav ~ .dashboard .top{
        left: 0;
        width: 100%;
    }
    nav.close ~ .dashboard .top{
        left: 0;
        width: 100%;
    }
}

</style>
