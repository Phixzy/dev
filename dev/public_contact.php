<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($category) || empty($message_text)) {
        $error = "Please fill in all required fields.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Insert message into database
        $sql = "INSERT INTO student_messages (student_id, student_name, email, subject, category, message, sent_at) 
                VALUES (0, '$name', '$email', '$subject', '$category', '$message_text', NOW())";
        
        if ($conn->query($sql)) {
            $message = "Your message has been sent to the admin successfully!";
            $_POST = array(); // Clear form data
        } else {
            $error = "Failed to send message. Please try again. Error: " . $conn->error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Admin - University</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Header */
        header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            color: #667eea;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        header h1 i {
            font-size: 1.8rem;
        }

        .header-nav {
            display: flex;
            gap: 1.5rem;
        }

        .header-nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-nav a:hover {
            color: #667eea;
        }

        /* Main Container */
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Success/Error Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.4s ease-out;
        }

        .alert.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .alert.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .alert i {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        /* Contact Card */
        .contact-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .contact-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .contact-header i {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 1rem;
        }

        .contact-header h2 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .contact-header p {
            color: #666;
            font-size: 0.95rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-group label i {
            color: #667eea;
            font-size: 0.9rem;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 140px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #764ba2;
            transform: translateX(-5px);
        }

        /* Footer Contact Info */
        .contact-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-item i {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .info-item h4 {
            font-size: 0.9rem;
            color: #333;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .info-item p {
            font-size: 0.85rem;
            color: #666;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .contact-card {
                padding: 1.5rem;
            }

            .contact-info {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .info-item {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-graduation-cap"></i> University</h1>
        <nav class="header-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="student_page/enroll.php"><i class="fas fa-user-plus"></i> Enroll Now</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-times-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="contact-card">
            <div class="contact-header">
                <i class="fas fa-headset"></i>
                <h2>Contact Administration</h2>
                <p>Have questions or concerns? Send us a message and we'll get back to you as soon as possible.</p>
            </div>

            <form method="POST" action="" id="contactForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Your Name
                        </label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">
                            <i class="fas fa-tag"></i> Category
                        </label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="enrollment" <?php echo (($_POST['category'] ?? '') === 'enrollment') ? 'selected' : ''; ?>>Enrollment Inquiry</option>
                            <option value="requirements" <?php echo (($_POST['category'] ?? '') === 'requirements') ? 'selected' : ''; ?>>Document Requirements</option>
                            <option value="schedule" <?php echo (($_POST['category'] ?? '') === 'schedule') ? 'selected' : ''; ?>>Class Schedule</option>
                            <option value="fees" <?php echo (($_POST['category'] ?? '') === 'fees') ? 'selected' : ''; ?>>Fees & Payment</option>
                            <option value="status" <?php echo (($_POST['category'] ?? '') === 'status') ? 'selected' : ''; ?>>Application Status</option>
                            <option value="other" <?php echo (($_POST['category'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">
                            <i class="fas fa-heading"></i> Subject
                        </label>
                        <input type="text" id="subject" name="subject" placeholder="Brief summary of your inquiry" required
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="message">
                        <i class="fas fa-comment-alt"></i> Your Message
                    </label>
                    <textarea id="message" name="message" rows="6" placeholder="Describe your inquiry in detail..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="send_message" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>

            <div class="contact-info">
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h4>Email Us</h4>
                        <p>admin@school.edu</p>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h4>Office Hours</h4>
                        <p>Mon-Fri: 8AM - 5PM</p>
                    </div>
                </div>
            </div>

            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.style.display = 'none', 500);
            });
        }, 5000);
    </script>
</body>
</html>

