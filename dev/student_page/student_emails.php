<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if student is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get logged-in student info
$student_id = $_SESSION['userid'];
$student_sql = "SELECT id, username, first_name, last_name, email, image, college_course, college_year, status 
                FROM students WHERE id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows > 0) {
    $current_student = $student_result->fetch_assoc();
} else {
    // Student not found, redirect to login
    session_destroy();
    header('Location: login.php');
    exit();
}

// Get student full name
$student_name = $current_student['first_name'] . ' ' . $current_student['last_name'];
$student_email = $current_student['email'];

// Handle form submission for sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $category = $_POST['category'] ?? 'Other';
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($subject) && !empty($message)) {
        // Insert into student_messages table
        $insert_sql = "INSERT INTO student_messages (student_id, student_name, email, subject, category, message, sent_at) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("isssss", $student_id, $student_name, $student_email, $subject, $category, $message);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Message sent successfully!";
        } else {
            $_SESSION['error'] = "Failed to send message. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Please fill in all required fields.";
    }
    header('Location: student_emails.php');
    exit();
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Get message details (only if belongs to this student)
    if ($action === 'get_message' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $msg_type = $_GET['type'] ?? 'inbox';
        
        if ($msg_type === 'sent') {
            // For sent messages, check by student_id
            $sql = "SELECT * FROM student_messages WHERE id = ? AND student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $student_id);
        } else {
            // For inbox messages, check by student_id
            $sql = "SELECT * FROM student_messages WHERE id = ? AND student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $student_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $message = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $message, 'type' => $msg_type]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Message not found or not authorized']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // Delete message (only if belongs to this student)
    if ($action === 'delete_message' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Only delete messages that belong to this student
        $sql = "DELETE FROM student_messages WHERE id = ? AND student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $student_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Message not found or not authorized']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
}

// Fetch inbox messages (messages received by student from admin)
$inbox_messages = [];
$inbox_sql = "SELECT * FROM student_messages WHERE student_id = ? ORDER BY sent_at DESC";
$stmt = $conn->prepare($inbox_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$inbox_result = $stmt->get_result();

if ($inbox_result && $inbox_result->num_rows > 0) {
    while ($row = $inbox_result->fetch_assoc()) {
        $inbox_messages[] = $row;
    }
}
$stmt->close();

// Fetch sent messages (messages sent by student)
$sent_messages = [];
$sent_sql = "SELECT * FROM student_messages WHERE student_id = ? ORDER BY sent_at DESC";
$stmt = $conn->prepare($sent_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$sent_result = $stmt->get_result();

if ($sent_result && $sent_result->num_rows > 0) {
    while ($row = $sent_result->fetch_assoc()) {
        $sent_messages[] = $row;
    }
}
$stmt->close();

// Calculate total counts for stats (before pagination)
$total_inbox_sql = "SELECT COUNT(*) as total FROM student_messages WHERE student_id = ?";
$stmt = $conn->prepare($total_inbox_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$total_inbox_result = $stmt->get_result();
$total_inbox = $total_inbox_result->fetch_assoc()['total'];
$stmt->close();

$total_sent_sql = "SELECT COUNT(*) as total FROM student_messages WHERE student_id = ?";
$stmt = $conn->prepare($total_sent_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$total_sent_result = $stmt->get_result();
$total_sent = $total_sent_result->fetch_assoc()['total'];
$stmt->close();

// Calculate unread count from total
$unread_sql = "SELECT COUNT(*) as total FROM student_messages WHERE student_id = ? AND (reply_message IS NULL OR reply_message = '')";
$stmt = $conn->prepare($unread_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['total'];
$stmt->close();

// Pagination parameters
$limit = 10; // Messages per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$inbox_page = isset($_GET['inbox_page']) ? max(1, intval($_GET['inbox_page'])) : 1;
$sent_page = isset($_GET['sent_page']) ? max(1, intval($_GET['sent_page'])) : 1;

// Calculate offsets
$inbox_offset = ($inbox_page - 1) * $limit;
$sent_offset = ($sent_page - 1) * $limit;

// Calculate total pages
$inbox_total_pages = ceil($total_inbox / $limit);
$sent_total_pages = ceil($total_sent / $limit);

// Fetch paginated inbox messages
$inbox_messages = [];
$inbox_sql = "SELECT * FROM student_messages WHERE student_id = ? ORDER BY sent_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($inbox_sql);
$stmt->bind_param("iii", $student_id, $limit, $inbox_offset);
$stmt->execute();
$inbox_result = $stmt->get_result();

if ($inbox_result && $inbox_result->num_rows > 0) {
    while ($row = $inbox_result->fetch_assoc()) {
        $inbox_messages[] = $row;
    }
}
$stmt->close();

// Fetch paginated sent messages
$sent_messages = [];
$sent_sql = "SELECT * FROM student_messages WHERE student_id = ? ORDER BY sent_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sent_sql);
$stmt->bind_param("iii", $student_id, $limit, $sent_offset);
$stmt->execute();
$sent_result = $stmt->get_result();

if ($sent_result && $sent_result->num_rows > 0) {
    while ($row = $sent_result->fetch_assoc()) {
        $sent_messages[] = $row;
    }
}
$stmt->close();

// Calculate displayed counts
$inbox_count = $total_inbox;
$sent_count = $total_sent;

// Pagination helper function
function generatePagination($currentPage, $totalPages, $baseUrl, $paramName) {
    $html = '<div class="pagination-container" style="display: flex; justify-content: center; align-items: center; margin-top: 25px; gap: 8px; flex-wrap: wrap;">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $prevUrl = strpos($baseUrl, '?') !== false ? $baseUrl . '&' . $paramName . '=' . $prevPage : $baseUrl . '?' . $paramName . '=' . $prevPage;
        $html .= '<a href="' . $prevUrl . '" class="pagination-btn" style="padding: 8px 14px; background: white; border: 2px solid var(--border-color); border-radius: 8px; color: var(--text-color); text-decoration: none; font-weight: 500; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px;"><i class="fas fa-chevron-left"></i> Prev</a>';
    } else {
        $html .= '<span class="pagination-btn disabled" style="padding: 8px 14px; background: var(--border-color); border: 2px solid var(--border-color); border-radius: 8px; color: #999; font-weight: 500; display: flex; align-items: center; gap: 5px; cursor: not-allowed;"><i class="fas fa-chevron-left"></i> Prev</span>';
    }
    
    // Page numbers
    $startPage = max(1, min($currentPage - 2, $totalPages - 4));
    $endPage = min($totalPages, max(5, $currentPage + 2));
    
    // Adjust if at edges
    if ($totalPages <= 5) {
        $startPage = 1;
        $endPage = $totalPages;
    } elseif ($currentPage <= 3) {
        $startPage = 1;
        $endPage = 5;
    } elseif ($currentPage >= $totalPages - 2) {
        $startPage = max(1, $totalPages - 4);
        $endPage = $totalPages;
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $pageUrl = strpos($baseUrl, '?') !== false ? $baseUrl . '&' . $paramName . '=' . $i : $baseUrl . '?' . $paramName . '=' . $i;
        if ($i === $currentPage) {
            $html .= '<span class="pagination-btn active" style="padding: 8px 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 2px solid transparent; border-radius: 8px; color: white; font-weight: 600; cursor: default;">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $pageUrl . '" class="pagination-btn" style="padding: 8px 14px; background: white; border: 2px solid var(--border-color); border-radius: 8px; color: var(--text-color); text-decoration: none; font-weight: 500; transition: all 0.3s ease;">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $nextUrl = strpos($baseUrl, '?') !== false ? $baseUrl . '&' . $paramName . '=' . $nextPage : $baseUrl . '?' . $paramName . '=' . $nextPage;
        $html .= '<a href="' . $nextUrl . '" class="pagination-btn" style="padding: 8px 14px; background: white; border: 2px solid var(--border-color); border-radius: 8px; color: var(--text-color); text-decoration: none; font-weight: 500; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px;">Next <i class="fas fa-chevron-right"></i></a>';
    } else {
        $html .= '<span class="pagination-btn disabled" style="padding: 8px 14px; background: var(--border-color); border: 2px solid var(--border-color); border-radius: 8px; color: #999; font-weight: 500; display: flex; align-items: center; gap: 5px; cursor: not-allowed;">Next <i class="fas fa-chevron-right"></i></span>';
    }
    
    $html .= '</div>';
    
    // Add page info
    $html .= '<div class="pagination-info" style="text-align: center; margin-top: 12px; font-size: 13px; color: var(--black-light-color);">';
    $html .= 'Page ' . $currentPage . ' of ' . max(1, $totalPages);
    $html .= '</div>';
    
    return $html;
}

$conn->close();
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
    <title>Student Status Management</title>
</head>
<body>
    <nav>
        <div class="logo-name">
            <div class="logo-image">
                <?php
                // Get the image path - check if student has a profile image
                $profile_image = '';
                if (!empty($current_student['image'])) {
                    $profile_image = '../' . $current_student['image'];
                    // Check if file exists
                    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/dev/' . $current_student['image'])) {
                        $profile_image = '';
                    }
                }
                
                // Get display username (remove @student suffix if present)
                $display_username = $current_student['username'];
                if (strpos($display_username, '@student') !== false) {
                    $display_username = str_replace('@student', '', $display_username);
                }
                
                if (!empty($profile_image)) {
                    echo '<img src="' . htmlspecialchars($profile_image) . '" alt="Profile" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">';
                } else {
                    echo '<i class="fas fa-user-graduate"></i>';
                }
                ?>
            </div>
            <span class="logo_name"><?php echo htmlspecialchars($display_username); ?></span>
        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="studentpage.php">
                    <i class="fas fa-home"></i>
                    <span class="link-name">Academic</span>
                </a></li>   
                
                <li><a href="student_grades.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-name">Grades</span>
                </a></li>
           
                <li><a href="student_emails.php" class="active">
                    <i class="fas fa-comments"></i>
                    <span class="link-name">Emails</span>
                </a></li>
                <li><a href="contact.php">
                    <i class="fas fa-phone"></i>
                    <span class="link-name">Contact</span>
                </a></li>
                <li><a href="student_profile.php">
                    <i class="fas fa-user"></i>
                    <span class="link-name">Profile</span>
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
                        <h3 style="font-size: 24px; font-weight: 600; color: var(--text-color); margin: 0;"><?php echo $inbox_count; ?></h3>
                        <p style="font-size: 13px; color: var(--black-light-color); margin: 0;">Inbox</p>
                    </div>
                </div>
                <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px;">
                    <div class="stat-icon" style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-envelope-open" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <h3 style="font-size: 24px; font-weight: 600; color: var(--text-color); margin: 0;"><?php echo $unread_count; ?></h3>
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
                        <?php if (empty($inbox_messages)): ?>
                            <div class="no-messages">
                                <i class="fas fa-inbox" style="font-size: 48px; color: var(--black-light-color); margin-bottom: 15px; opacity: 0.5;"></i>
                                <p style="color: var(--black-light-color); font-size: 16px;">No messages in your inbox</p>
                                <p style="color: var(--black-light-color); font-size: 13px; margin-top: 5px;">Messages from admin will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="message-list">
                                <?php foreach ($inbox_messages as $message): ?>
                                    <div class="message" onclick="showMessageDetails(<?php echo $message['id']; ?>, 'inbox')" style="<?php echo empty($message['reply_message']) ? 'border-left: 4px solid #667eea;' : 'border-left: 4px solid #28a745;'; ?>">
                                        <div class="message-header">
                                            <h2>
                                                <?php echo htmlspecialchars($message['subject']); ?>
                                                <?php if (empty($message['reply_message'])): ?>
                                                    <span style="background: #667eea; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 10px; font-weight: 500;">NEW</span>
                                                <?php elseif (!empty($message['reply_message'])): ?>
                                                    <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 10px; font-weight: 500;">REPLIED</span>
                                                <?php endif; ?>
                                            </h2>
                                            <span class="message-date">
                                                <?php 
                                                $date = new DateTime($message['sent_at']);
                                                echo $date->format('M d, Y h:i A');
                                                ?>
                                            </span>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 150)); ?>
                                            <?php if (strlen($message['message']) > 150): ?>...<?php endif; ?>
                                        </div>
                                        <?php if (!empty($message['reply_message'])): ?>
                                        <div class="reply-preview" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 10px 15px; border-radius: 8px; margin-top: 10px; font-size: 13px; color: #155724;">
                                            <i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i>
                                            <strong>Admin replied:</strong> <?php echo htmlspecialchars(substr($message['reply_message'], 0, 100)); ?>...
                                        </div>
                                        <?php endif; ?>
                                        <div class="view-details">
                                            <i class="fas fa-eye"></i> View Details
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($inbox_total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <?php echo generatePagination($inbox_page, $inbox_total_pages, 'student_emails.php', 'inbox_page'); ?>
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
                                <p style="color: var(--black-light-color); font-size: 13px; margin-top: 5px;">Click "Compose New Message" to send a message to admin</p>
                            </div>
                        <?php else: ?>
                            <div class="message-list">
                                <?php foreach ($sent_messages as $message): ?>
                                    <div class="message" onclick="showMessageDetails(<?php echo $message['id']; ?>, 'sent')" style="border-left: 4px solid #38ef7d;">
                                        <div class="message-header">
                                            <h2><?php echo htmlspecialchars($message['subject']); ?>
                                                <span class="message-category-badge" style="background: <?php 
                                                    $cat_colors = [
                                                        'Academic' => '#667eea',
                                                        'Grades' => '#28a745',
                                                        'Schedule' => '#ffc107',
                                                        'Enrollment' => '#17a2b8',
                                                        'Technical' => '#dc3545',
                                                        'Feedback' => '#fd7e14',
                                                        'Other' => '#6c757d'
                                                    ];
                                                    echo $cat_colors[$message['category']] ?? '#6c757d';
                                                ?>; color: white; padding: 3px 10px; border-radius: 12px; font-size: 11px; margin-left: 10px; font-weight: 500;"><?php echo htmlspecialchars($message['category']); ?></span>
                                            </h2>
                                            <span class="message-date">
                                                <?php 
                                                $date = new DateTime($message['sent_at']);
                                                echo $date->format('M d, Y h:i A');
                                                ?>
                                            </span>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 150)); ?>
                                            <?php if (strlen($message['message']) > 150): ?>...<?php endif; ?>
                                        </div>
                                        <?php if (!empty($message['reply_message'])): ?>
                                        <div class="reply-preview" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 10px 15px; border-radius: 8px; margin-top: 10px; font-size: 13px; color: #155724;">
                                            <i class="fas fa-reply" style="color: #28a745; margin-right: 5px;"></i>
                                            <strong>Admin replied:</strong> <?php echo htmlspecialchars(substr($message['reply_message'], 0, 100)); ?>...
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
                        
                        <?php if ($sent_total_pages > 1): ?>
                        <div class="pagination-wrapper">
                            <?php echo generatePagination($sent_page, $sent_total_pages, 'student_emails.php', 'sent_page'); ?>
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
                        <form method="POST" action="student_emails.php" id="composeForm">
                            <div class="form-group" style="margin-bottom: 20px;">
                           
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 600; color: var(--text-color); margin-bottom: 8px; font-size: 14px;">
                                    <i class="fas fa-tag" style="color: var(--primary-color); margin-right: 8px;"></i>Category
                                </label>
                                <select name="category" style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; background: white; cursor: pointer;">
                                    <option value="Academic">Academic</option>
                                    <option value="Grades">Grades</option>
                                    <option value="Schedule">Schedule</option>
                                    <option value="Enrollment">Enrollment</option>
                                    <option value="Technical">Technical</option>
                                    <option value="Feedback">Feedback</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                                 <label style="display: block; font-weight: 600; color: var(--text-color); margin-bottom: 8px; font-size: 14px;">
                                    <i class="fas fa-heading" style="color: var(--primary-color); margin-right: 8px;"></i>Subject <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="text" name="subject" required placeholder="Enter message subject..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; transition: all 0.3s ease;">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 600; color: var(--text-color); margin-bottom: 8px; font-size: 14px;">
                                    <i class="fas fa-comment-alt" style="color: var(--primary-color); margin-right: 8px;"></i>Message <span style="color: #dc3545;">*</span>
                                </label>
                                <textarea name="message" required rows="6" placeholder="Type your message here..." style="width: 100%; padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: 'Poppins', sans-serif; resize: vertical; transition: all 0.3s ease;"></textarea>
                            </div>
                            
                            <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" onclick="clearForm()" style="padding: 12px 24px; background: var(--border-color); color: var(--text-color); border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                                <button type="submit" name="send_message" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;">
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
            fetch(`student_emails.php?action=get_message&id=${messageId}&type=${msgType}`)
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
                        
                        detailsDiv.innerHTML = `
                            <div class="detail-row">
                                <label><i class="fas fa-heading"></i> Subject:</label>
                                <span style="font-weight: 600; color: var(--text-color);">${escapeHtml(message.subject)}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="fas fa-tag"></i> Category:</label>
                                <span style="background: ${getCategoryColor(message.category)}; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 500;">${escapeHtml(message.category)}</span>
                            </div>
                            <div class="detail-row">
                                <label><i class="fas fa-calendar"></i> Sent:</label>
                                <span>${formattedDate}</span>
                            </div>
                            <div class="detail-row full-width">
                                <label><i class="fas fa-comment-alt"></i> Message:</label>
                                <div class="message-content">${escapeHtml(message.message)}</div>
                            </div>
                            ${message.reply_message ? `
                            <div class="detail-row full-width" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); padding: 15px; border-radius: 10px; margin-top: 15px; border-left: 4px solid #28a745;">
                                <label style="color: #155724;"><i class="fas fa-reply"></i> Admin Reply:</label>
                                <div class="message-content" style="background: transparent; border: none; margin-top: 8px; color: #155724;">${escapeHtml(message.reply_message)}</div>
                            </div>
                            ` : ''}
                            <div class="detail-actions">
                                <button class="delete-btn" onclick="deleteMessage(${message.id}, '${escapeHtml(message.subject)}')">
                                    <i class="fas fa-trash"></i> Delete
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
        
        function deleteMessage(messageId, username) {
            if (confirm(`Are you sure you want to delete the message from "${username}"?\n\nThis action cannot be undone.`)) {
                // Show loading state
                const button = event.target.closest('.delete-btn');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="uil uil-spinner"></i> Deleting...';
                button.disabled = true;
                
                fetch(`student_emails.php?action=delete_message&id=${messageId}`)
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