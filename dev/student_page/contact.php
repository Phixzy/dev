<?php
session_start();

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

// Get logged-in student info first (needed for form submission)
$student_id = $_SESSION['userid'];
$student_sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows > 0) {
    $current_student = $student_result->fetch_assoc();
} else {
    session_destroy();
    header('Location: login.php');
    exit();
}

$stmt->close();

// Handle message submission AFTER we have student info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate inputs
    if (empty($subject) || empty($category) || empty($message)) {
        $_SESSION['error'] = "Please fill in all required fields.";
    } else {
        // Insert message into database (including email)
        $stmt = $conn->prepare("INSERT INTO student_messages (student_id, student_name, email, subject, category, message, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $student_name = $current_student['first_name'] . ' ' . $current_student['last_name'];
        $student_email = $current_student['email'] ?? '';
        $stmt->bind_param("isssss", $student_id, $student_name, $student_email, $subject, $category, $message);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Your message has been sent to the admin successfully!";
        } else {
            $_SESSION['error'] = "Failed to send message. Please try again.";
        }
        $stmt->close();
    }
    header("Location: contact.php");
    exit();
}

// Get student's previous messages
$messages_sql = "SELECT * FROM student_messages WHERE student_id = ? ORDER BY sent_at DESC LIMIT 5";
$stmt = $conn->prepare($messages_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$student_messages = $messages_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Get display username
$display_username = $current_student['username'];
if (strpos($display_username, '@student') !== false) {
    $display_username = str_replace('@student', '', $display_username);
}

// Get profile image path
$profile_image = '';
if (!empty($current_student['image'])) {
    $profile_image = '../' . $current_student['image'];
    // Check if file exists
    $full_path = '/Applications/XAMPP/xamppfiles/htdocs/dev/' . $current_student['image'];
    if (!file_exists($full_path)) {
        $profile_image = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title>Contact Admin</title>
</head>
<body>
    <!-- Navigation Sidebar -->
    <nav>
        <div class="logo-name">
            <div class="logo-image">
                <?php
                if (!empty($profile_image)) {
                    echo '<img src="' . htmlspecialchars($profile_image) . '" alt="Profile">';
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
                <li><a href="student_emails.php">
                    <i class="fas fa-comments"></i>
                    <span class="link-name">Emails</span>
                </a></li>
                <li><a href="contact.php" class="active">
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
    
    <!-- Main Dashboard Section -->
    <section class="dashboard">
        <div class="top">
            <span class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </span>
        </div>
        
        <!-- Success/Error Messages -->
        <?php
        if (isset($_SESSION['message'])) {
            echo '<div class="alert-message success" style="margin: 90px auto 20px auto;">';
            echo '<i class="fas fa-check-circle"></i>';
            echo '<span>' . htmlspecialchars($_SESSION['message']) . '</span>';
            echo '</div>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert-message error" style="margin: 90px auto 20px auto;">';
            echo '<i class="fas fa-times-circle"></i>';
            echo '<span>' . htmlspecialchars($_SESSION['error']) . '</span>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <div class="dash-content">
            <!-- Contact Header -->
            <div class="contact-header">
                <div class="header-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="header-text">
                    <h1>Contact Administration</h1>
                    <p>Have questions or concerns? We're here to help! Send us a message and we'll get back to you as soon as possible.</p>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="contact-grid">
                <!-- Contact Form -->
                <div class="contact-form-card">
                    <div class="card-header">
                        <i class="fas fa-paper-plane"></i>
                        <h3>Send a Message</h3>
                    </div>
                    <form method="POST" action="" class="contact-form" id="contactForm">
                        <div class="form-group">
                            <label for="category">
                                <i class="fas fa-tag"></i> Category
                            </label>
                            <select id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="academics">Academics</option>
                                <option value="enrollment">Enrollment</option>
                                <option value="shift">Shifting Course</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">
                                <i class="fas fa-heading"></i> Subject
                            </label>
                            <input type="text" id="subject" name="subject" placeholder="Brief summary of your concern" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">
                                <i class="fas fa-comment-alt"></i> Your Message
                            </label>
                            <textarea id="message" name="message" rows="6" placeholder="Describe your concern in detail..." required></textarea>
                            <div class="char-count">
                                <span id="charCount">0</span> characters
                            </div>
                        </div>
                        
                        <button type="submit" name="send_message" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
                
                <!-- Message Tips Sidebar -->
                <div class="tips-sidebar">
                    
                    
                    <!-- Suggested Topics -->
                    <div class="topics-card">
                        <div class="card-header topics-header">
                            <i class="fas fa-list-ul"></i>
                            <h3>Common Topics</h3>
                        </div>
                        <div class="topics-content">
                            
                            

                            <div class="topic-item" onclick="selectTopic('Evaluate', 'academics')">
                                <i class="fas fa-file-alt"></i>
                                <span>Academics</span>
                            </div>
                            <div class="topic-item" onclick="selectTopic('Document Request', 'enrollment')">
                                <i class="fas fa-laptop"></i>
                                <span>Enrollment</span>
                            </div>
                            <div class="topic-item" onclick="selectTopic('Shift Course', 'shift')">
                                <i class="fas fa-exchange-alt"></i>
                                <span>Shift Course</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Previous Messages -->
                    <?php if (!empty($student_messages)): ?>
                    <div class="history-card">
                        <div class="card-header history-header">
                            <i class="fas fa-history"></i>
                            <h3>Recent Messages</h3>
                        </div>
                        <div class="history-content">
                            <?php foreach ($student_messages as $msg): ?>
                            <div class="history-item">
                                <div class="history-item-header">
                                    <span class="history-category"><?php echo htmlspecialchars($msg['category']); ?></span>
                                    <span class="history-date"><?php echo date('M d, Y', strtotime($msg['sent_at'])); ?></span>
                                </div>
                                <p class="history-subject"><?php echo htmlspecialchars($msg['subject']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Contact Info Footer -->
            <div class="contact-footer">
                <div class="footer-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h4>Email Us</h4>
                        <p>admin@school.edu</p>
                    </div>
                </div>
                
                <div class="footer-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h4>Office Hours</h4>
                        <p>Mon-Fri: 8AM - 5PM</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Sidebar functionality
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

        // Character count for message
        const messageField = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        
        if (messageField && charCount) {
            messageField.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
        }

        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.alert-message').forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.style.display = 'none', 500);
            });
        }, 4000);

        // Select topic function
        function selectTopic(subject, category) {
            document.getElementById('subject').value = subject;
            document.getElementById('category').value = category;
            
            // Add visual feedback
            document.getElementById('subject').style.backgroundColor = '#e8f5e8';
            document.getElementById('category').style.backgroundColor = '#e8f5e8';
            setTimeout(() => {
                document.getElementById('subject').style.backgroundColor = '';
                document.getElementById('category').style.backgroundColor = '';
            }, 1000);
        }

    </script>
    
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

        /* Navigation Sidebar */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            padding: 10px 14px;
            transition: all 0.4s ease;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
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

        nav .logo-image img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        nav .logo_name {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
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

        .menu-items::-webkit-scrollbar {
            display: none;
        }

        .menu-items ul {
            list-style: none;
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

        .logout-mode li a {
            padding: 12px 15px;
        }

        /* Dashboard */
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
        }

        .dashboard .dash-content {
            padding-top: 10px;
        }

        /* Contact Header */
        .contact-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .header-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .header-icon i {
            font-size: 35px;
            color: #fff;
        }

        .header-text h1 {
            font-size: 28px;
            color: #fff;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header-text p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
        }

        /* Main Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Cards */
        .contact-form-card,
        .tips-card,
        .topics-card,
        .history-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .contact-form-card:hover,
        .tips-card:hover,
        .topics-card:hover,
        .history-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f3f7;
        }

        .card-header i {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }

        .tips-header i,
        .topics-header i,
        .history-header i {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-group label i {
            color: #667eea;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .char-count {
            font-size: 12px;
            color: #718096;
            text-align: right;
            margin-top: 5px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Tips */
        .tips-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .tip-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .tip-item:hover {
            background: #f0f3f7;
        }

        .tip-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .tip-icon i {
            font-size: 14px;
            color: #fff;
        }

        .tip-text h4 {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
        }

        .tip-text p {
            font-size: 12px;
            color: #718096;
            line-height: 1.4;
        }

        /* Topics */
        .topics-content {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .topic-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .topic-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            transform: translateX(5px);
        }

        .topic-item i {
            width: 30px;
            color: #667eea;
            transition: color 0.3s ease;
        }

        .topic-item:hover i {
            color: #fff;
        }

        .topic-item span {
            font-size: 13px;
            font-weight: 500;
        }

        /* History */
        .history-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .history-item {
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            border-left: 3px solid #667eea;
        }

        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .history-category {
            font-size: 11px;
            padding: 3px 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 10px;
            font-weight: 500;
        }

        .history-date {
            font-size: 11px;
            color: #718096;
        }

        .history-subject {
            font-size: 13px;
            color: #2d3748;
            font-weight: 500;
        }

        /* Contact Footer */
        .contact-footer {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .footer-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .footer-item i {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
        }

        .footer-item h4 {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
        }

        .footer-item p {
            font-size: 13px;
            color: #718096;
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
            margin-left: auto;
            margin-right: auto;
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

        .alert-message i {
            font-size: 24px;
            flex-shrink: 0;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .tips-sidebar {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .tips-card,
            .topics-card {
                margin: 0;
            }
            
            .history-card {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            nav {
                width: 73px;
            }

            nav .logo_name,
            nav .nav-links li a .link-name {
                opacity: 0;
                pointer-events: none;
            }

            .dashboard {
                left: 73px;
                width: calc(100% - 73px);
                padding: 15px 20px;
            }

            .contact-header {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
            }

            .tips-sidebar {
                grid-template-columns: 1fr;
            }

            .history-card {
                grid-column: span 1;
            }

            .contact-footer {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .header-icon {
                width: 60px;
                height: 60px;
            }

            .header-icon i {
                font-size: 25px;
            }

            .header-text h1 {
                font-size: 22px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .contact-form-card,
        .tips-card,
        .topics-card,
        .history-card {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .contact-form-card { animation-delay: 0.1s; }
        .tips-card { animation-delay: 0.2s; }
        .topics-card { animation-delay: 0.3s; }
        .history-card { animation-delay: 0.4s; }
    </style>
</body>
</html>
