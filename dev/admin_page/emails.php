
<?php
// Start output buffering to ensure clean JSON output for AJAX requests
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check database connection
if ($conn->connect_error) {
    if (!empty($_GET['action']) || !empty($_POST['action'])) {
        // For AJAX requests, return JSON error
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    // For HTML page loads, continue without dying
}

$response_sent = false;

function outputJsonAndExit($data, $conn) {
    global $response_sent;
    if ($response_sent) return;
    $response_sent = true;
    
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
    exit;
}

// Handle AJAX requests
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!empty($action)) {
    if ($action === 'get_message' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if ($id <= 0) {
            outputJsonAndExit(['success' => false, 'message' => 'Invalid message ID'], $conn);
        }
        
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
            
            if (!isset($message['student_db_id']) || $message['student_db_id'] === null) {
                $message['student_db_id'] = 0;
            }
            if (!isset($message['student_id']) || $message['student_id'] === null) {
                $message['student_id'] = intval($message['student_db_id'] ?? 0);
            }
            if (empty($message['email']) || $message['email'] === 'N/A') {
                $message['email'] = $message['student_email'] ?? '';
            }
            unset($message['student_email']);
            outputJsonAndExit(['success' => true, 'data' => $message], $conn);
        } else {
            $stmt->close();
            // Fallback to old messages table
            $sql = "SELECT * FROM messages WHERE id = ?";
            $stmt = $conn->prepare($sql);
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
    
    if ($action === 'reply_message') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            outputJsonAndExit(['success' => false, 'message' => 'Invalid request method'], $conn);
        }
        
        if (!isset($_POST['message_id']) || empty(trim($_POST['message_id']))) {
            outputJsonAndExit(['success' => false, 'message' => 'Message ID is required'], $conn);
        }
        
        $message_id_val = trim($_POST['message_id']);
        if (!is_numeric($message_id_val)) {
            outputJsonAndExit(['success' => false, 'message' => 'Invalid message ID format'], $conn);
        }
        
        $message_id = intval(floatval($message_id_val));
        if ($message_id <= 0) {
            outputJsonAndExit(['success' => false, 'message' => 'Message ID must be a positive number'], $conn);
        }
        
        $reply_text = trim($_POST['reply'] ?? '');
        $admin_name = $_SESSION['admin_username'] ?? 'Admin';
        
        if (empty($reply_text)) {
            outputJsonAndExit(['success' => false, 'message' => 'Please enter a reply message'], $conn);
        }
        
        $reply_text_sanitized = htmlspecialchars(strip_tags($reply_text));
        
        $stmt = $conn->prepare("UPDATE student_messages SET reply_message = ?, replied_by = ?, replied_at = NOW() WHERE id = ?");
        if (!$stmt) {
            outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
        }
        
        $stmt->bind_param("ssi", $reply_text_sanitized, $admin_name, $message_id);
        
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affected_rows > 0) {
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
    
    if ($action === 'send_message') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            outputJsonAndExit(['success' => false, 'message' => 'Invalid request method'], $conn);
        }
        
        $recipient_email = isset($_POST['recipient_email']) ? trim($_POST['recipient_email']) : '';
        $custom_email = isset($_POST['custom_email']) ? trim($_POST['custom_email']) : '';
        
        if (empty($recipient_email) || $recipient_email === '') {
            $recipient_email = $custom_email;
        }
        
        if (empty($recipient_email)) {
            outputJsonAndExit(['success' => false, 'message' => 'Please enter or select a recipient email'], $conn);
        }
        
        if (empty($_POST['message'])) {
            outputJsonAndExit(['success' => false, 'message' => 'Please enter a message'], $conn);
        }
        
        if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            outputJsonAndExit(['success' => false, 'message' => 'Invalid recipient email format'], $conn);
        }
        
        // Check if student exists
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
        }
        
        $message_text = isset($_POST['message']) ? trim($_POST['message']) : '';
        $username = $_SESSION['admin_username'] ?? 'Admin';
        $admin_email = isset($_POST['email']) ? trim($_POST['email']) : 'admin@school.edu';
        $recipient_name = isset($_POST['recipient_name']) ? trim($_POST['recipient_name']) : '';
        
        if (!empty($recipient_name)) {
            $student_name = $recipient_name;
        }
        
        if (empty(trim($message_text))) {
            outputJsonAndExit(['success' => false, 'message' => 'Message content is empty'], $conn);
        }
        
        $username_escaped = $conn->real_escape_string($username);
        $admin_email_escaped = $conn->real_escape_string($admin_email);
        $recipient_email_escaped = $conn->real_escape_string($recipient_email);
        $message_text_escaped = $conn->real_escape_string($message_text);
        $student_name_escaped = $conn->real_escape_string($student_name);
        
        // Insert into student_messages for bidirectional communication
        $table_check = $conn->query("SHOW TABLES LIKE 'student_messages'");
        if ($table_check === false || $table_check->num_rows === 0) {
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
            $conn->query($create_table);
        } else {
            $table_check->free();
        }
        
        $category = 'Admin Message';
        $subject = 'Message from Admin';
        $sent_at = date('Y-m-d H:i:s');
        
        $student_msg_stmt = $conn->prepare("INSERT INTO student_messages (student_id, student_name, email, category, subject, message, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($student_msg_stmt) {
            $student_msg_stmt->bind_param("issssss", $student_db_id, $student_name_escaped, $recipient_email_escaped, $category, $subject, $message_text_escaped, $sent_at);
            if ($student_msg_stmt->execute()) {
                $student_msg_stmt->close();
                outputJsonAndExit(['success' => true, 'message' => 'Message sent successfully to ' . htmlspecialchars($recipient_email)], $conn);
            } else {
                $student_msg_stmt->close();
                outputJsonAndExit(['success' => false, 'message' => 'Error sending message'], $conn);
            }
        } else {
            outputJsonAndExit(['success' => false, 'message' => 'Database prepare error'], $conn);
        }
    }
    
    if ($action === 'get_messages_list') {
        // Return messages list for the inbox
        $messages = [];
        
        $sql = "SELECT * FROM student_messages ORDER BY sent_at DESC";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
        
        // Also get sent messages (where category = 'Admin Message')
        $sent_sql = "SELECT * FROM student_messages WHERE category = 'Admin Message' ORDER BY sent_at DESC";
        $sent_result = $conn->query($sent_sql);
        $sent_messages = [];
        
        if ($sent_result && $sent_result->num_rows > 0) {
            while ($row = $sent_result->fetch_assoc()) {
                $sent_messages[] = $row;
            }
        }
        
        // Get all students for compose dropdown
        $students = [];
        $students_sql = "SELECT id, username, email, first_name, last_name FROM students ORDER BY first_name, last_name";
        $students_result = $conn->query($students_sql);
        if ($students_result && $students_result->num_rows > 0) {
            while ($row = $students_result->fetch_assoc()) {
                $students[] = $row;
            }
        }
        
        // Get stats
        $total_inbox = count($messages);
        $unread = 0;
        foreach ($messages as $msg) {
            if (empty($msg['reply_message'])) {
                $unread++;
            }
        }
        $sent_count = count($sent_messages);
        
        outputJsonAndExit([
            'success' => true,
            'data' => [
                'messages' => $messages,
                'sent_messages' => $sent_messages,
                'students' => $students,
                'stats' => [
                    'inbox' => $total_inbox,
                    'unread' => $unread,
                    'sent' => $sent_count
                ]
            ]
        ], $conn);
    }
}

// Fetch data for HTML page (only if not AJAX)
$messages = [];
$sent_messages = [];
$students = [];

// Get messages from students (inbox)
$sql = "SELECT * FROM student_messages WHERE category != 'Admin Message' ORDER BY sent_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

// Get sent messages (admin messages)
$sent_sql = "SELECT * FROM student_messages WHERE category = 'Admin Message' ORDER BY sent_at DESC";
$sent_result = $conn->query($sent_sql);
if ($sent_result && $sent_result->num_rows > 0) {
    while ($row = $sent_result->fetch_assoc()) {
        $sent_messages[] = $row;
    }
}

// Get all students for compose dropdown
$students_sql = "SELECT id, username, email, first_name, last_name FROM students ORDER BY first_name, last_name";
$students_result = $conn->query($students_sql);
if ($students_result && $students_result->num_rows > 0) {
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// Calculate stats
$total_inbox = count($messages);
$unread = 0;
foreach ($messages as $msg) {
    if (empty($msg['reply_message'])) {
        $unread++;
    }
}
$sent_count = count($sent_messages);

// Close connection if not already closed
if ($conn && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}

// Helper function to get category color
function getCategoryColor($category) {
    $colors = [
        'Academic' => '#667eea',
        'Grades' => '#28a745',
        'Schedule' => '#ffc107',
        'Enrollment' => '#17a2b8',
        'Technical' => '#dc3545',
        'Feedback' => '#fd7e14',
        'Other' => '#6c757d',
        'Admin Message' => '#11998e'
    ];
    return $colors[$category] ?? '#6c757d';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Font Awesome 6 - Primary Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Material Icons as additional backup -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Unicons CSS for icon support -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <title>Student Status Management</title>
</head>
<body>
    <nav>
        <div class="logo-name">
            <span class="logo_name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="link-name">Appointments</span>
                </a></li>  
                
               <li><a href="student_status.php">
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
                <li><a href="edit_homepage.php">
                    <i class="fas fa-edit"></i>
                    <span class="link-name">Edit Homepage</span>
                </a></li>
                 <li><a href="admin_user.php">
                    <i class="fas fa-user-shield"></i>
                    <span class="link-name">Admin User Management</span>
                </a></li>
            </ul>
            
            <ul class="logout-mode">
                <li><a href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-name">Logout</span>
                </a></li>
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
        }
        ?>
        
        <div class="dash-content">
            
            <div class="title">
                <i class="fas fa-envelope"></i>
                <span class="text">Messages</span>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-inbox" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <h3 style="font-size: 24px; font-weight: 600; color: var(--text-color); margin: 0;"><?php echo $total_inbox; ?></h3>
                        <p style="font-size: 13px; color: var(--black-light-color); margin: 0;">Inbox</p>
                    </div>
                </div>
                <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-envelope-open" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <h3 style="font-size: 24px; font-weight: 600; color: var(--text-color); margin: 0;"><?php echo $unread; ?></h3>
                        <p style="font-size: 13px; color: var(--black-light-color); margin: 0;">Unread</p>
                    </div>
                </div>
                <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-paper-plane" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <h3 style="font-size: 24px; font-weight: 600; color: var(--text-color); margin: 0;"><?php echo $sent_count; ?></h3>
                        <p style="font-size: 13px; color: var(--black-light-color); margin: 0;">Sent</p>
                    </div>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div class="messages-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                <button class="tab-btn active" data-tab="inbox" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                    <i class="fas fa-inbox"></i> Inbox
                </button>
                <button class="tab-btn" data-tab="sent" style="padding: 12px 24px; background: white; color: var(--text-color); border: 2px solid var(--border-color); border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                    <i class="fas fa-paper-plane"></i> Sent Messages
                </button>
                <button class="tab-btn" data-tab="compose" style="padding: 12px 24px; background: white; color: var(--text-color); border: 2px solid var(--border-color); border-radius: 10px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                    <i class="fas fa-pen"></i> Compose New Message
                </button>
            </div>
            
            <!-- Inbox Tab -->
            <div class="tab-content" id="inbox-tab" style="display: block;">
                <div class="activity">
                    <div class="message-container" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <?php if (empty($messages)): ?>
                            <div class="no-messages">
                                <i class="fas fa-inbox" style="font-size: 48px; color: var(--black-light-color); margin-bottom: 15px; opacity: 0.5;"></i>
                                <p style="color: var(--black-light-color); font-size: 16px;">No messages in your inbox</p>
                                <p style="color: var(--black-light-color); font-size: 13px; margin-top: 5px;">Messages from students will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="message-list">
                                <?php foreach ($messages as $msg): ?>
                                    <?php 
                                    $date = new DateTime($msg['sent_at']);
                                    $formattedDate = $date->format('M d, Y - h:i A');
                                    $isUnread = empty($msg['reply_message']);
                                    $studentName = htmlspecialchars($msg['student_name'] ?? 'Unknown Student');
                                    $categoryColor = getCategoryColor($msg['category'] ?? 'Other');
                                    $messagePreview = htmlspecialchars(substr($msg['message'] ?? '', 0, 100) . (strlen($msg['message'] ?? '') > 100 ? '...' : ''));
                                    $replyMessage = htmlspecialchars($msg['reply_message'] ?? '');
                                    ?>
                                    <div class="message <?php echo $isUnread ? 'unread' : ''; ?>" onclick="showMessageDetails(<?php echo $msg['id']; ?>, 'inbox')" style="<?php echo $isUnread ? 'border-left-color: #667eea;' : 'border-left-color: #38ef7d;'; ?>">
                                        <div class="message-header">
                                            <h2>
                                                <?php echo $studentName; ?>
                                                <span class="message-category-badge" style="background: <?php echo $categoryColor; ?>; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; margin-left: 10px; font-weight: 500;">
                                                    <?php echo htmlspecialchars($msg['category'] ?? 'General'); ?>
                                                </span>
                                                <?php if ($isUnread): ?>
                                                    <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px; font-weight: 500;">NEW</span>
                                                <?php endif; ?>
                                            </h2>
                                            <span class="message-date"><?php echo $formattedDate; ?></span>
                                        </div>
                                        <div class="message-preview"><?php echo $messagePreview; ?></div>
                                        <div class="message-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($msg['email'] ?? 'N/A'); ?></div>
                                        
                                        <?php if (!empty($msg['reply_message'])): ?>
                                            <div class="reply-preview" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 10px 15px; border-radius: 8px; margin-top: 10px; font-size: 13px; color: #155724;">
                                                <i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i>
                                                <strong>Replied by <?php echo htmlspecialchars($msg['replied_by'] ?? 'Admin'); ?>:</strong> 
                                                <?php echo htmlspecialchars(substr($msg['reply_message'], 0, 80) . (strlen($msg['reply_message']) > 80 ? '...' : '')); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="reply-preview" style="background: rgba(255, 193, 7, 0.1); padding: 10px 15px; border-radius: 8px; margin-top: 10px; font-size: 13px; color: #856404;">
                                                <i class="fas fa-clock" style="color: #ffc107; margin-right: 5px;"></i>
                                                <strong>Awaiting reply...</strong>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="view-details">
                                            <i class="fas fa-eye"></i> View Details
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sent Tab -->
            <div class="tab-content" id="sent-tab" style="display: none;">
                <div class="activity">
                    <div class="message-container" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <?php if (empty($sent_messages)): ?>
                            <div class="no-messages">
                                <i class="fas fa-paper-plane" style="font-size: 48px; color: var(--black-light-color); margin-bottom: 15px; opacity: 0.5;"></i>
                                <p style="color: var(--black-light-color); font-size: 16px;">No sent messages yet</p>
                                <p style="color: var(--black-light-color); font-size: 13px; margin-top: 5px;">Click "Compose New Message" to send a message to a student</p>
                            </div>
                        <?php else: ?>
                            <div class="message-list">
                                <?php foreach ($sent_messages as $msg): ?>
                                    <?php 
                                    $date = new DateTime($msg['sent_at']);
                                    $formattedDate = $date->format('M d, Y - h:i A');
                                    $hasReply = !empty($msg['reply_message']);
                                    $recipientEmail = htmlspecialchars($msg['email'] ?? 'N/A');
                                    $recipientName = htmlspecialchars($msg['student_name'] ?? 'Recipient');
                                    $messagePreview = htmlspecialchars(substr($msg['message'] ?? '', 0, 100) . (strlen($msg['message'] ?? '') > 100 ? '...' : ''));
                                    ?>
                                    <div class="message" onclick="showMessageDetails(<?php echo $msg['id']; ?>, 'sent')" style="border-left: 4px solid #38ef7d;">
                                        <div class="message-header">
                                            <h2>
                                                To: <?php echo $recipientName; ?>
                                                <span class="message-category-badge" style="background: #11998e; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; margin-left: 10px; font-weight: 500;">
                                                    Admin Message
                                                </span>
                                            </h2>
                                            <span class="message-date"><?php echo $formattedDate; ?></span>
                                        </div>
                                        <div class="message-preview"><?php echo $messagePreview; ?></div>
                                        <div class="message-email"><i class="fas fa-envelope"></i> <?php echo $recipientEmail; ?></div>
                                        
                                        <?php if ($hasReply): ?>
                                            <div class="reply-preview" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 10px 15px; border-radius: 8px; margin-top: 10px; font-size: 13px; color: #155724;">
                                                <i class="fas fa-reply" style="color: #28a745; margin-right: 5px;"></i>
                                                <strong>Student replied:</strong> 
                                                <?php echo htmlspecialchars(substr($msg['reply_message'], 0, 80) . (strlen($msg['reply_message']) > 80 ? '...' : '')); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="reply-preview" style="background: rgba(255, 193, 7, 0.1); padding: 10px 15px; border-radius: 8px; margin-top: 10px; font-size: 13px; color: #856404;">
                                                <i class="fas fa-clock" style="color: #ffc107; margin-right: 5px;"></i>
                                                <strong>Awaiting reply...</strong>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="view-details">
                                            <i class="fas fa-eye"></i> View Details
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Compose Tab -->
            <div class="tab-content" id="compose-tab" style="display: none;">
                <div class="activity">
                    <div class="message-container" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <h3 style="font-size: 20px; font-weight: 600; color: var(--text-color); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-pen" style="color: var(--primary-color);"></i>
                            Compose New Message
                        </h3>
                        <form id="composeForm">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 600; color: var(--text-color); margin-bottom: 8px; font-size: 14px;">
                                    <i class="fas fa-user-graduate" style="color: var(--primary-color); margin-right: 8px;"></i>Select Student <span style="color: #dc3545;">*</span>
                                </label>
                                <select id="recipientSelect" onchange="handleStudentSelect()" style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; background: white; cursor: pointer;">
                                    <option value="">-- Select a student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo htmlspecialchars($student['email']); ?>" data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> (<?php echo htmlspecialchars($student['email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="custom">-- Enter custom email --</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="customEmailGroup" style="margin-bottom: 20px; display: none;">
                                <label style="display: block; font-weight: 600; color: var(--text-color); margin-bottom: 8px; font-size: 14px;">
                                    <i class="fas fa-envelope" style="color: var(--primary-color); margin-right: 8px;"></i>Recipient Email <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="email" id="customEmail" placeholder="Enter recipient email address..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; transition: all 0.3s ease;">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 600; color: var(--text-color); margin-bottom: 8px; font-size: 14px;">
                                    <i class="fas fa-heading" style="color: var(--primary-color); margin-right: 8px;"></i>Subject <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="text" id="subject" required placeholder="Enter message subject..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; transition: all 0.3s ease;">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 600; color: var(--text-color); margin-bottom: 8px; font-size: 14px;">
                                    <i class="fas fa-comment-alt" style="color: var(--primary-color); margin-right: 8px;"></i>Message <span style="color: #dc3545;">*</span>
                                </label>
                                <textarea id="message" required rows="6" placeholder="Type your message here..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; resize: vertical; transition: all 0.3s ease;"></textarea>
                            </div>
                            
                            <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" onclick="clearComposeForm()" style="padding: 12px 24px; background: var(--border-color); color: var(--text-color); border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                                <button type="button" onclick="sendAdminMessage()" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                            </div>
                        </form>
                    </div>
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
<script>
    // Function to show stored PHP-style message from sessionStorage
    function showStoredPhpMessage() {
        const message = sessionStorage.getItem('phpMessage');
        const messageType = sessionStorage.getItem('phpMessageType');
        
        if (message) {
            // Create the PHP-style message element
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert-message ' + messageType;
            alertDiv.style.cssText = 'margin: 90px auto 20px auto; max-width: 600px; padding: 16px 24px; border-radius: 8px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); animation: slideIn 0.4s ease-out; position: relative; z-index: 5;';
            
            if (messageType === 'success') {
                alertDiv.style.background = 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)';
                alertDiv.style.borderLeft = '5px solid #28a745';
                alertDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #28a745; font-size: 24px; flex-shrink: 0;"></i>';
                alertDiv.innerHTML += '<span style="color: #155724; font-weight: 500; font-size: 15px;">' + message + '</span>';
            } else {
                alertDiv.style.background = 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)';
                alertDiv.style.borderLeft = '5px solid #dc3545';
                alertDiv.innerHTML = '<i class="fas fa-times-circle" style="color: #dc3545; font-size: 24px; flex-shrink: 0;"></i>';
                alertDiv.innerHTML += '<span style="color: #721c24; font-weight: 500; font-size: 15px;">' + message + '</span>';
            }
            
            // Insert after the top div (dashboard)
            const dashboard = document.querySelector('.dashboard');
            if (dashboard) {
                dashboard.insertBefore(alertDiv, dashboard.firstChild);
            }
            
            // Clear from sessionStorage
            sessionStorage.removeItem('phpMessage');
            sessionStorage.removeItem('phpMessageType');
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alertDiv.remove(), 500);
            }, 5000);
        }
    }
    
    // Run on page load
    document.addEventListener('DOMContentLoaded', function() {
        showStoredPhpMessage();
    });
    
    // Also run immediately in case DOM is already loaded
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(showStoredPhpMessage, 100);
    }
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
    
    // Get category color
    function getCategoryColor(category) {
        const colors = {
            'Academic': '#667eea',
            'Grades': '#28a745',
            'Schedule': '#ffc107',
            'Enrollment': '#17a2b8',
            'Technical': '#dc3545',
            'Feedback': '#fd7e14',
            'Other': '#6c757d'
        };
        return colors[category] || '#6c757d';
    }
    
    // Function to switch tabs
    function switchTab(tabName) {
        // Update button styles
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            if (btn.dataset.tab === tabName) {
                btn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                btn.style.color = 'white';
                btn.style.border = 'none';
            } else {
                btn.style.background = 'white';
                btn.style.color = 'var(--text-color)';
                btn.style.border = '2px solid var(--border-color)';
            }
        });
        
        // Show/hide tab content
        const tabs = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.style.display = 'none';
        });
        document.getElementById(tabName + '-tab').style.display = 'block';
    }
    
    // Add tab button event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                switchTab(this.dataset.tab);
            });
        });
    });
    
    // Function to clear form
    function clearForm() {
        document.getElementById('composeForm').reset();
    }
    
    // Function to handle student select
    function handleStudentSelect() {
        const select = document.getElementById('recipientSelect');
        const customEmailGroup = document.getElementById('customEmailGroup');
        
        if (select.value === 'custom') {
            customEmailGroup.style.display = 'block';
        } else {
            customEmailGroup.style.display = 'none';
        }
    }
    
    // Function to clear compose form
    function clearComposeForm() {
        document.getElementById('composeForm').reset();
        document.getElementById('customEmailGroup').style.display = 'none';
    }
    
    // Function to send admin message
    function sendAdminMessage() {
        const recipientSelect = document.getElementById('recipientSelect');
        const customEmail = document.getElementById('customEmail').value.trim();
        const subject = document.getElementById('subject').value.trim();
        const message = document.getElementById('message').value.trim();
        
        let recipientEmail = recipientSelect.value;
        
        if (!recipientEmail || recipientEmail === '') {
            alert('Please select a student or enter a custom email');
            return;
        }
        
        if (recipientEmail === 'custom') {
            recipientEmail = customEmail;
        }
        
        if (!recipientEmail || recipientEmail === '') {
            alert('Please enter a recipient email address');
            return;
        }
        
        if (!subject) {
            alert('Please enter a subject');
            return;
        }
        
        if (!message) {
            alert('Please enter a message');
            return;
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(recipientEmail)) {
            alert('Please enter a valid email address');
            return;
        }
        
        // Send message via AJAX
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('recipient_email', recipientEmail);
        formData.append('custom_email', customEmail);
        formData.append('subject', subject);
        formData.append('message', message);
        
        const sendBtn = event.target;
        const originalText = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i class="fas fa-spinner"></i> Sending...';
        sendBtn.disabled = true;
        
        fetch('emails.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store message in sessionStorage for display after reload
                sessionStorage.setItem('phpMessage', data.message);
                sessionStorage.setItem('phpMessageType', 'success');
                
                // Refresh page to show new message in sent tab
                setTimeout(() => {
                    switchTab('sent');
                    // Check if we need to show the stored message
                    showStoredPhpMessage();
                }, 500);
            } else {
                sessionStorage.setItem('phpMessage', data.message);
                sessionStorage.setItem('phpMessageType', 'error');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            sessionStorage.setItem('phpMessage', 'An error occurred while sending the message');
            sessionStorage.setItem('phpMessageType', 'error');
            location.reload();
        })
        .finally(() => {
            sendBtn.innerHTML = originalText;
            sendBtn.disabled = false;
        });
    }
    
    // Function to escape HTML but keep URLs clickable
    function escapeHtml(text) {
        if (!text) return '';
        
        // First URL-encode special characters in URLs to protect them
        // Then convert URLs to clickable links
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        
        // First escape HTML special characters (except for URLs we haven't added yet)
        const escaped = text
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
    
    // Function to show message details
    function showMessageDetails(messageId, msgType) {
            // Show loading indicator
            const detailsDiv = document.getElementById('messageDetails');
            detailsDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i> Loading message details...</div>';
            modal.style.display = 'block';
            
            // Fetch message details via AJAX
            fetch(`emails.php?action=get_message&id=${messageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const message = data.data;
                        const date = new Date(message.sent_at);
                        const formattedDate = date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                        
                        const isInbox = msgType === 'inbox';
                        const replyStatus = message.reply_message;
                        
                        let replySection = '';
                        if (replyStatus) {
                            replySection = `
                            <div class="detail-row full-width" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 15px; border-radius: 10px; margin-top: 15px; border-left: 4px solid #28a745;">
                                <label style="color: #155724;"><i class="fas fa-reply"></i> ${isInbox ? 'Admin' : 'Student'} Reply:</label>
                                <div class="message-content" style="background: transparent; border: none; margin-top: 8px; color: #155724;">${escapeHtml(message.reply_message)}</div>
                                <div style="margin-top: 10px; font-size: 12px; color: #721c24;">
                                    <i class="fas fa-user"></i> By: ${escapeHtml(message.replied_by || 'Admin')}
                                </div>
                            </div>`;
                        } else if (isInbox) {
                            // Add reply form for inbox messages
                            replySection = `
                            <div class="detail-row full-width" style="background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 10px; margin-top: 15px; border-left: 4px solid #667eea;">
                                <label style="color: #667eea;"><i class="fas fa-reply"></i> Reply to this message:</label>
                                <textarea id="replyMessage" rows="4" placeholder="Type your reply here..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; resize: vertical; margin-top: 10px;"></textarea>
                                <button onclick="sendReply(${message.id})" style="margin-top: 10px; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer;">
                                    <i class="fas fa-paper-plane"></i> Send Reply
                                </button>
                            </div>`;
                        }
                        
                        detailsDiv.innerHTML = `
                            <div class="detail-row">
                                <label><i class="fas fa-user"></i> ${isInbox ? 'From:' : 'To:'}</label>
                                <span style="font-weight: 600; color: var(--text-color);">${escapeHtml(message.student_name)}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="fas fa-envelope"></i> Email:</label>
                                <span>${escapeHtml(message.email)}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="fas fa-heading"></i> Subject:</label>
                                <span style="font-weight: 600; color: var(--text-color);">${escapeHtml(message.subject || 'No Subject')}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="fas fa-tag"></i> Category:</label>
                                <span style="background: ${getCategoryColor(message.category)}; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 500;">${escapeHtml(message.category || 'General')}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="fas fa-calendar"></i> Sent:</label>
                                <span>${formattedDate}</span>
                            </div>
                            <div class="detail-row full-width">
                                <label><i class="fas fa-comment-alt"></i> Message:</label>
                                <div class="message-content">${escapeHtml(message.message)}</div>
                            </div>
                            ${replySection}
                            <div class="detail-actions">
                                <button class="delete-btn" onclick="deleteMessage(${message.id}, '${escapeHtml(message.student_name)}')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        `;
                    } else {
                        detailsDiv.innerHTML = '<div class="error"><i class="fas fa-exclamation-triangle"></i> Message not found.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    detailsDiv.innerHTML = '<div class="error"><i class="fas fa-exclamation-triangle"></i> Error loading message details.</div>';
                });
        }
        
        // Function to send reply
        function sendReply(messageId) {
            const replyText = document.getElementById('replyMessage').value.trim();
            
            if (!replyText) {
                alert('Please enter a reply message');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'reply_message');
            formData.append('message_id', messageId);
            formData.append('reply', replyText);
            
            const replyBtn = event.target;
            const originalText = replyBtn.innerHTML;
            replyBtn.innerHTML = '<i class="fas fa-spinner"></i> Sending...';
            replyBtn.disabled = true;
            
        fetch('emails.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                sessionStorage.setItem('phpMessage', 'Reply sent successfully!');
                sessionStorage.setItem('phpMessageType', 'success');
                modal.style.display = 'none';
                // Refresh page to show updated message
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                sessionStorage.setItem('phpMessage', 'Error: ' + data.message);
                sessionStorage.setItem('phpMessageType', 'error');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            sessionStorage.setItem('phpMessage', 'An error occurred while sending the reply');
            sessionStorage.setItem('phpMessageType', 'error');
            location.reload();
        })
        .finally(() => {
            replyBtn.innerHTML = originalText;
            replyBtn.disabled = false;
        });
        }
        
        // Function to reply to message
        function replyToMessage(email, username) {
            const subject = encodeURIComponent(`Re: Message from ${username}`);
            const body = encodeURIComponent(`\n\n--- Original message ---\nFrom: ${email}\nTo: admin@school.edu`);
            window.open(`mailto:${email}?subject=${subject}&body=${body}`);
        }
        
        // Function to copy email to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = '<i class="fas fa-check"></i> Email copied to clipboard!';
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
                button.innerHTML = '<i class="fas fa-spinner"></i> Deleting...';
                button.disabled = true;
                
                fetch(`emails.php?action=delete_message&id=${messageId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success notification
                            const notification = document.createElement('div');
                            notification.className = 'notification';
                            notification.innerHTML = '<i class="fas fa-check"></i> Message deleted successfully!';
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
                        notification.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error deleting message!';
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
   /* Navigation styles */
    nav .nav-links li a.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
    }

    nav .nav-links li a.active i,
    nav .nav-links li a.active .link-name {
        color: white;
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
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    border-right: none;
    transition: var(--tran-05);
    z-index: 1000;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
}
nav.close{
    width: 73px;
}
nav .logo-name{
    display: flex;
    align-items: center;
    padding: 15px 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    margin-bottom: 15px;
}
nav .logo-image{
    display: flex;
    justify-content: center;
    align-items: center;
    min-width: 45px;
    border-radius: 12px;
}
nav .logo-image i{
    font-size: 28px;
    color: #fff;
}
nav .logo-image img{
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.3);
}
nav .logo-name .logo_name{
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    margin-left: 12px;
    white-space: nowrap;
    transition: opacity 0.3s ease;
}
nav.close .logo_name{
    opacity: 0;
    pointer-events: none;
}
nav .menu-items{
    margin-top: 0;
    height: calc(100% - 70px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow-y: auto;
}
nav .menu-items::-webkit-scrollbar {
    display: none;
}
.menu-items li{
    list-style: none;
    margin: 5px 0;
}
.menu-items li a{
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-radius: 10px;
    text-decoration: none;
    position: relative;
    transition: all 0.3s ease;
}
.nav-links li a:hover{
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(5px);
}
body.dark li a:hover:before{
    background-color: var(--text-color);
}
.menu-items li a i{
    font-size: 20px;
    min-width: 25px;
    color: rgba(255, 255, 255, 0.8);
    transition: color 0.3s ease;
}
.menu-items li a .link-name{
    font-size: 15px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.9);
    white-space: nowrap;
    transition: opacity 0.3s ease;
}
nav.close li a .link-name{
    opacity: 0;
    pointer-events: none;
}
.nav-links li a:hover i,
.nav-links li a:hover .link-name{
    color: #fff;
}
body.dark .nav-links li a:hover i,
body.dark .nav-links li a:hover .link-name{
    color: var(--text-color);
}
/* Active state for nav links */
nav .nav-links li a.active{
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
nav .nav-links li a.active i,
nav .nav-links li a.active .link-name{
    color: #fff;
}
.menu-items .logout-mode{
    padding-top: 15px;
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
nav.close ~ .dashboard{
    left: 73px;
    width: calc(100% - 73px);
}
.dashboard .top{
    position: fixed;
    top: 0;
    left: 250px;
    display: flex;
    width: calc(100% - 250px);
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background-color: var(--panel-color);
    transition: var(--tran-05);
    z-index: 10;
}
nav.close ~ .dashboard .top{
    left: 73px;
    width: calc(100% - 73px);
}
.dashboard .top .sidebar-toggle{
    font-size: 26px;
    color: var(--text-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}
.dashboard .top .sidebar-toggle:hover{
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.dashboard .top .sidebar-toggle:hover i{
    color: #fff;
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
.pagination-wrapper {
    margin-top: 20px;
}

.pagination-btn:hover:not(.disabled):not(.active) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border-color: transparent !important;
    color: white !important;
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