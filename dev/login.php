<?php
session_start();
require_once 'config/dbcon.php';

// Function to get setting value
function getSetting($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM homepage_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    return $default;
}

$message = '';
$error = '';
if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if(isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch hero background image setting
$hero_background_image = getSetting('hero_background_image');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <h1>University</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="student_page/enroll.php">Enroll Now</a></li>
            <li><a href="public_contact.php">Contact</a></li>
      
        </ul>
    </nav>
    <section class="hero">
        <div class="hero-content">
            <div class="left">
                <h1>Welcome to University</h1>
                <p>Excellence in Education</p>
            </div>
            <div class="right">
                <form action="loginAuth.php" method="post" class="login-form">
                    <h2>Login</h2>
                    <?php if($message): ?>
                        <div class="success-message"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" placeholder="Enter your username" required>
                    </div>
                    
                    <div class="password-wrapper">
                        <label for="loginPassword" style="display:none;">Password</label>
                        <input type="password" name="password" id="loginPassword" placeholder="Enter your password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                        <i class="fas fa-eye toggle-icon" id="togglePassword" onclick="togglePassword('loginPassword', 'togglePassword')" title="Toggle password visibility"></i>
                    </div>
                    
                    <div class="captcha-group">
                        <label for="captcha">Captcha Verification</label>
                        <div class="captcha-wrapper">
                            <img src="captcha.php" alt="Captcha" title="Click to refresh captcha">
                            <input type="text" name="captcha" id="captcha" placeholder="Enter captcha" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="login" class="btn login-btn">Login</button>
                    
                    <div class="form-footer">
                        <p>Don't have an Account? <a href="student_page/enroll.php">Enroll Now!</a></p>
                        <p class="contact-link"><a href="public_contact.php">Contact Admin</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

</body>
</html>


 <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f4f8;
            color: #333;
            min-height: 100vh;
        }
        header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 1rem 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        nav {
            background: linear-gradient(135deg, #005577 0%, #006699 100%);
            padding: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        nav li {
            margin: 0;
        }
        nav a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        nav a:hover {
            background-color: rgba(255,255,255,0.15);
            color: #ffcc00;
        }
        .btn {
            display: inline-block;
            background-color: #005577;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
        }
        .btn:hover {
            background-color: #004466;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,85,119,0.3);
        }
        .btn:active {
            transform: translateY(0);
        }
        .login-btn {
            background: linear-gradient(135deg, #005577 0%, #006699 100%);
            width: 100%;
            margin-top: 0.5rem;
        }
        .hero {
            background: <?php echo $hero_background_image ? 'url(uploads/' . htmlspecialchars($hero_background_image) . ') center/cover no-repeat' : 'linear-gradient(135deg, #003366 0%, #004080 60%, #005577 100%)'; ?>;
            background-size: 100% 100%;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: <?php echo $hero_background_image ? 'rgba(0,0,0,0.3)' : 'none'; ?>;
            opacity: 0.15;
        }
        .hero-content {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            position: relative;
            z-index: 1;
            gap: 3rem;
        }
        .left {
            flex: 1;
            text-align: left;
            color: white;
            display: none;
        }
        .left h1 {
            font-size: 3rem;
            margin: 0 0 1rem 0;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .left p {
            font-size: 1.5rem;
            margin: 0;
            opacity: 0.95;
        }
        .right {
            flex: 1;
            display: flex;
            justify-content: center;
            max-width: 450px;
            width: 100%;
        }
        .login-form {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .login-form h2 {
            margin: 0 0 1.5rem 0;
            color: #003366;
            font-size: 1.75rem;
            text-align: center;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #444;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: #333;
        }
        .login-form input[type="text"]:focus,
        .login-form input[type="password"]:focus {
            outline: none;
            border-color: #005577;
            background: white;
            box-shadow: 0 0 0 3px rgba(0,85,119,0.1);
        }
        .login-form input::placeholder {
            color: #999;
        }
        .password-wrapper {
            position: relative;
            margin-bottom: 1.25rem;
        }
        .password-wrapper input {
            padding-right: 45px;
        }
        .password-wrapper .toggle-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            background: none;
            border: none;
            padding: 0;
        }
        .password-wrapper .toggle-icon:hover {
            color: #003366;
        }
        .captcha-group {
            margin-bottom: 1.5rem;
        }
        .captcha-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #444;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .captcha-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .captcha-wrapper img {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        .captcha-wrapper input {
            flex: 1;
        }
        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            padding: 1rem;
            border: 1px solid #b1dfbb;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            padding: 1rem;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        .form-footer p {
            margin: 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        .form-footer a {
            color: #005577;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .form-footer a:hover {
            color: #003366;
            text-decoration: underline;
        }
        .contact-link {
            font-size: 0.85rem;
        }
        
        /* Responsive Styles */
        @media (min-width: 992px) {
            .left {
                display: block;
            }
            .hero-content {
                justify-content: space-between;
            }
        }
        
        @media (max-width: 768px) {
            nav ul {
                justify-content: center;
            }
            nav a {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            .hero {
                padding: 1rem;
                min-height: auto;
                padding: 2rem 1rem;
            }
            .login-form {
                padding: 2rem;
            }
            .left h1 {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 480px) {
            header h1 {
                font-size: 1.5rem;
            }
            nav a {
                padding: 0.6rem 0.8rem;
                font-size: 0.85rem;
            }
            .login-form {
                padding: 1.5rem;
            }
            .captcha-wrapper {
                flex-direction: column;
            }
            .captcha-wrapper img {
                width: 100%;
                max-width: 200px;
            }
        }
    </style>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Add smooth focus transitions
        document.querySelectorAll('.login-form input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
