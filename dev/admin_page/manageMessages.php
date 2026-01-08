<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
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

// Create messages table if not exists
$sql = "CREATE TABLE IF NOT EXISTS student_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    reply_message TEXT,
    replied_by VARCHAR(100),
    sent_at DATETIME NOT NULL,
    replied_at DATETIME,
    INDEX idx_student_id (student_id),
    INDEX idx_sent_at (sent_at)
)";
$conn->query($sql);

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $message_id = intval($_POST['message_id'] ?? 0);
    $reply = trim($_POST['reply_message'] ?? '');
    $reply_by = $_SESSION['username'] ?? 'Admin';
    
    if (!empty($message_id) && !empty($reply)) {
        $stmt = $conn->prepare("UPDATE student_messages SET reply_message = ?, replied_by = ?, replied_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $reply, $reply_by, $message_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Reply sent successfully!";
        } else {
            $_SESSION['error'] = "Failed to send reply.";
        }
        $stmt->close();
    }
    header("Location: manageMessages.php");
    exit();
}

// Get all messages
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_clause = "1=1";
if ($filter !== 'all') {
    $where_clause .= " AND category = '" . $conn->real_escape_string($filter) . "'";
}
if (!empty($search)) {
    $where_clause .= " AND (student_name LIKE '%" . $conn->real_escape_string($search) . "%' OR subject LIKE '%" . $conn->real_escape_string($search) . "%' OR message LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$messages_sql = "SELECT * FROM student_messages WHERE $where_clause ORDER BY sent_at DESC";
$messages_result = $conn->query($messages_sql);
$messages = $messages_result->fetch_all(MYSQLI_ASSOC);

// Get message counts
$total_count = $conn->query("SELECT COUNT(*) as count FROM student_messages")->fetch_assoc()['count'];
$unread_count = $conn->query("SELECT COUNT(*) as count FROM student_messages WHERE reply_message IS NULL")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Manage Messages</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f7fa;
            min-height: 100vh;
        }

        nav {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            padding: 10px 14px;
            z-index: 1000;
        }

        nav.close {
            width: 73px;
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
            color: #fff;
        }

        nav .logo_name {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-left: 12px;
        }

        nav.close .logo_name {
            display: none;
        }

        nav .menu-items {
            height: calc(100% - 70px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .nav-links li {
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

        .nav-links li a:hover,
        .nav-links li a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-links li a i {
            font-size: 20px;
            min-width: 25px;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-links li a:hover i,
        .nav-links li a.active i {
            color: #fff;
        }

        .nav-links li a .link-name {
            font-size: 15px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
            margin-left: 12px;
        }

        nav.close .nav-links li a .link-name {
            display: none;
        }

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

        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            margin-bottom: 20px;
        }

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
        }

        .sidebar-toggle i {
            font-size: 20px;
            color: #667eea;
        }

        .dash-content {
            padding-top: 10px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: #fff;
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-info p {
            font-size: 14px;
            color: #718096;
        }

        /* Filter Bar */
        .filter-bar {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
        }

        .filter-tab {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: #f5f7fa;
            color: #718096;
            transition: all 0.3s ease;
        }

        .filter-tab:hover,
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f5f7fa;
            padding: 10px 15px;
            border-radius: 10px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 14px;
            width: 200px;
        }

        .search-box i {
            color: #718096;
        }

        /* Messages List */
        .messages-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .message-card.unread {
            border-left: 4px solid #667eea;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .message-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 18px;
        }

        .user-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
        }

        .user-info span {
            font-size: 13px;
            color: #718096;
        }

        .message-meta {
            text-align: right;
        }

        .message-category {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .message-category.Academic { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .message-category.Grades { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .message-category.Schedule { background: rgba(255, 193, 7, 0.1); color: #d39e00; }
        .message-category.Enrollment { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .message-category.Documents { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        .message-category.Technical { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .message-category.Feedback { background: rgba(253, 126, 20, 0.1); color: #fd7e14; }
        .message-category.Other { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

        .message-date {
            font-size: 12px;
            color: #718096;
        }

        .message-subject {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .message-content {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.6;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .message-actions {
            display: flex;
            justify-content: flex-end;
        }

        .reply-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .reply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        /* Reply Section */
        .reply-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f3f7;
            display: none;
        }

        .reply-section.show {
            display: block;
        }

        .reply-section textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            font-family: 'Poppins', sans-serif;
        }

        .reply-section textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .reply-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }

        .cancel-btn {
            padding: 10px 20px;
            background: #f5f7fa;
            color: #718096;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .send-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Existing Reply */
        .existing-reply {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            border-left: 4px solid #28a745;
        }

        .existing-reply h5 {
            font-size: 13px;
            color: #155724;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .existing-reply p {
            font-size: 14px;
            color: #155724;
            line-height: 1.6;
        }

        /* Alert Messages */
        .alert-message {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            max-width: 600px;
        }

        .alert-message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 5px solid #28a745;
            color: #155724;
        }

        .alert-message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 5px solid #dc3545;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            nav {
                width: 73px;
            }

            nav .logo_name,
            nav .nav-links li a .link-name {
                display: none;
            }

            .dashboard {
                left: 73px;
                width: calc(100% - 73px);
            }

            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-tabs {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Sidebar -->
    <nav>
        <div class="logo-name">
            <div class="logo-image">
                <i class="fas fa-user-shield"></i>
            </div>
            <span class="logo_name">Admin</span>
        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php">
                    <i class="fas fa-home"></i>
                    <span class="link-name">Dashboard</span>
                </a></li>
                <li><a href="manageStudents.php">
                    <i class="fas fa-users"></i>
                    <span class="link-name">Students</span>
                </a></li>
                <li><a href="manageSubjects.php">
                    <i class="fas fa-book"></i>
                    <span class="link-name">Subjects</span>
                </a></li>
                <li><a href="grades.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-name">Grades</span>
                </a></li>
                <li><a href="manageMessages.php" class="active">
                    <i class="fas fa-envelope"></i>
                    <span class="link-name">Messages</span>
                </a></li>
            </ul>
            <ul class="logout-mode">
                <li><a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-name">Logout</span>
                </a></li>
            </ul>
        </div>
    </nav>
    
    <!-- Main Dashboard -->
    <section class="dashboard">
        <div class="top">
            <span class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </span>
        </div>
        
        <!-- Success/Error Messages -->
        <?php
        if (isset($_SESSION['message'])) {
            echo '<div class="alert-message success">';
            echo '<i class="fas fa-check-circle"></i>';
            echo '<span>' . htmlspecialchars($_SESSION['message']) . '</span>';
            echo '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert-message error">';
            echo '<i class="fas fa-times-circle"></i>';
            echo '<span>' . htmlspecialchars($_SESSION['error']) . '</span>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <div class="dash-content">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_count; ?></h3>
                        <p>Total Messages</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $unread_count; ?></h3>
                        <p>Awaiting Reply</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_count - $unread_count; ?></h3>
                        <p>Replied</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo ($filter === 'all' ? 'active' : ''); ?>">All</a>
                    <a href="?filter=Academic" class="filter-tab <?php echo ($filter === 'Academic' ? 'active' : ''); ?>">Academic</a>
                    <a href="?filter=Grades" class="filter-tab <?php echo ($filter === 'Grades' ? 'active' : ''); ?>">Grades</a>
                    <a href="?filter=Schedule" class="filter-tab <?php echo ($filter === 'Schedule' ? 'active' : ''); ?>">Schedule</a>
                    <a href="?filter=Enrollment" class="filter-tab <?php echo ($filter === 'Enrollment' ? 'active' : ''); ?>">Enrollment</a>
                    <a href="?filter=Technical" class="filter-tab <?php echo ($filter === 'Technical' ? 'active' : ''); ?>">Technical</a>
                </div>
                <form method="GET" class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search messages..." value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                </form>
            </div>
            
            <!-- Messages List -->
            <div class="messages-container">
                <?php if (empty($messages)): ?>
                    <div class="message-card" style="text-align: center; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e0; margin-bottom: 15px;"></i>
                        <p style="color: #718096;">No messages found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <div class="message-card <?php echo empty($msg['reply_message']) ? 'unread' : ''; ?>">
                        <div class="message-header">
                            <div class="message-user">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($msg['student_name'], 0, 1)); ?>
                                </div>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($msg['student_name']); ?></h4>
                                    <span>Student ID: <?php echo $msg['student_id']; ?></span>
                                </div>
                            </div>
                            <div class="message-meta">
                                <span class="message-category <?php echo $msg['category']; ?>"><?php echo htmlspecialchars($msg['category']); ?></span>
                                <div class="message-date"><?php echo date('M d, Y - h:i A', strtotime($msg['sent_at'])); ?></div>
                            </div>
                        </div>
                        
                        <h3 class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></h3>
                        <div class="message-content"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                        
                        <?php if (!empty($msg['reply_message'])): ?>
                        <div class="existing-reply">
                            <h5><i class="fas fa-check-circle"></i> Replied by <?php echo htmlspecialchars($msg['replied_by']); ?> on <?php echo date('M d, Y - h:i A', strtotime($msg['replied_at'])); ?></h5>
                            <p><?php echo nl2br(htmlspecialchars($msg['reply_message'])); ?></p>
                        </div>
                        <?php else: ?>
                        <div class="message-actions">
                            <button class="reply-btn" onclick="showReplyForm(<?php echo $msg['id']; ?>)">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                        </div>
                        
                        <div class="reply-section" id="replySection<?php echo $msg['id']; ?>">
                            <form method="POST" onsubmit="return validateReplyForm(this, <?php echo $msg['id']; ?>);">
                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                <textarea id="replyContent<?php echo $msg['id']; ?>" name="reply_message" placeholder="Type your reply..."></textarea>
                                <div class="reply-actions">
                                    <button type="button" class="cancel-btn" onclick="hideReplyForm(<?php echo $msg['id']; ?>)">Cancel</button>
                                    <button type="button" class="send-btn" onclick="submitReply<?php echo $msg['id']; ?>()">Send Reply</button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        const body = document.querySelector("body");
        const sidebar = body.querySelector("nav");
        const sidebarToggle = body.querySelector(".sidebar-toggle");
        
        let getStatus = localStorage.getItem("status");
        if (getStatus && getStatus === "close") {
            sidebar.classList.add("close");
        } else {
            sidebar.classList.remove("close");
        }
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", () => {
                sidebar.classList.toggle("close");
                localStorage.setItem("status", sidebar.classList.contains("close") ? "close" : "open");
            });
        }

        function showReplyForm(id) {
            document.getElementById('replySection' + id).classList.add('show');
        }

        function hideReplyForm(id) {
            document.getElementById('replySection' + id).classList.remove('show');
        }

        function validateReplyForm(form, id) {
            return submitReply(id);
        }

        function submitReply(id) {
            const textarea = document.getElementById('replyContent' + id);
            if (!textarea || textarea.value.trim() === '') {
                alert('Please enter a reply message.');
                return false;
            }
            
            // Submit the form directly
            const form = textarea.closest('form');
            form.submit();
            return false; // Prevent default form submission behavior
        }

        setTimeout(() => {
            document.querySelectorAll('.alert-message').forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.style.display = 'none', 500);
            });
        }, 3000);
    </script>
</body>
</html>

