<?php
session_start();

// Debug: Log everything
error_log("=== DEBUG: Page loaded ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST: " . print_r($_POST, true));

$message = '';
$error = '';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    error_log("=== Form submitted! ===");
    
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $category = $_POST['category'] ?? '';
    $message_text = $_POST['message'] ?? '';
    
    error_log("name=$name, email=$email, subject=$subject, category=$category");
    
    // Validate
    if (empty($name) || empty($email) || empty($subject) || empty($category) || empty($message_text)) {
        $error = "All fields are required!";
        error_log("Validation failed: empty fields");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
        error_log("Validation failed: invalid email");
    } else {
        // Insert using simple query (not prepared statement) for debugging
        $sql = "INSERT INTO student_messages (student_id, student_name, email, subject, category, message, sent_at) 
                VALUES (0, '$name', '$email', '$subject', '$category', '$message_text', NOW())";
        
        error_log("SQL: $sql");
        
        if ($conn->query($sql)) {
            $message = "SUCCESS! Message inserted with ID: " . $conn->insert_id;
            error_log("Insert successful! ID: " . $conn->insert_id);
        } else {
            $error = "Database error: " . $conn->error;
            error_log("Insert failed: " . $conn->error);
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Contact Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        h1 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; }
        input:focus, select:focus, textarea:focus { border-color: #667eea; outline: none; }
        button { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; }
        button:hover { opacity: 0.9; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .debug { background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-envelope"></i> Contact Form</h1>
            
            <?php if ($message): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert error">
                    <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Your Name:</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Category:</label>
                    <select name="category" required>
                        <option value="">Select category</option>
                        <option value="enrollment" <?php echo ($_POST['category'] ?? '') == 'enrollment' ? 'selected' : ''; ?>>Enrollment</option>
                        <option value="requirements" <?php echo ($_POST['category'] ?? '') == 'requirements' ? 'selected' : ''; ?>>Requirements</option>
                        <option value="other" <?php echo ($_POST['category'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="send_message">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>
</body>
</html>

