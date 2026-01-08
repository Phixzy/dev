<?php
session_start();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
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
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                        <?php if($message): ?>
                            <div class="success-message"><?php echo $message; ?></div>
                            <?php endif; ?>
                            <?php if($error): ?>
                            <div class="error-message"><?php echo $error; ?></div>
                            <?php endif; ?>
                    <input type="text" name="captcha" placeholder="Enter the captcha text" required>
                    <p style="color: white; margin: 1rem 0;">Captcha:</p>
                    <img src="captcha.php" alt="Captcha" style="margin-bottom: 1rem;">
                    <button type="submit" name="login" class="btn login-btn">Login</button>
                    <p>Don't have an Account? <a href="student_page/enroll.php">Enroll Now!</a></p>
                    <p style="margin-top: 10px;"><a href="public_contact.php" style="font-size: 0.85rem;">Contact Admin</a></p>
                </form>
            </div>
        </div>
    </section>

</body>
</html>


 <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        header {
            background-color: #003366;
            color: white;
            padding: 1rem;
            text-align: center;
            animation: slideDown 1s ease-out;
        }
        nav {
            background-color: #005577;
            padding: 0.5rem;
            animation: fadeIn 2s ease-in;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
        }
        nav li {
            margin: 0 1rem;
        }
        nav a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }
        nav a:hover {
            color: #ffcc00;
        }
        .btn {
            background-color: #005577;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1rem;
        }
        .btn:hover {
            background-color: #004466;
        }
        .login-btn {
            background-color: #005577;
        }
        .signup-btn {
            background-color: #003366;
        }
        .signup-btn:hover {
            background-color: #002244;
        }
        .hero {
            background-image: url('img/ab.jpg');
            background-size: cover;
            background-position: center;
            height: 510px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            animation: zoomIn 1.5s ease-out;
        }
        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            padding: 0 2rem;
        }
        .left {
            flex: 1;
            text-align: left;
        }
        .left h1 {
            font-size: 4rem;
            margin: 0;
        }
        .left p {
            font-size: 2rem;
        }
        .right {
            flex: 1;
            display: flex;
            justify-content: center;
        }
        .login-form {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 2rem;
            width: 320px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .login-form h2 {
            margin-top: 0;
            color: white;
        }
        .login-form input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1rem;
        }
        .login-form input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .login-form button {
            width: 100%;
        }
        .login-form p {
            margin: 1rem 0 0 0;
            text-align: center;
            color: white;
            font-size: 0.9rem;
        }
        .login-form a {
            color: #ffcc00;
            text-decoration: none;
        }
        .login-form a:hover {
            text-decoration: underline;
        }
        section {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeInUp 1s ease-out;
        }
        .about, .programs, .news {
            margin-bottom: 2rem;
        }
        .programs ul {
            display: flex;
            flex-wrap: wrap;
            list-style: none;
            padding: 0;
        }
        .programs li {
            background-color: white;
            margin: 1rem;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex: 1 1 300px;
            transition: transform 0.3s;
        }
        .programs li:hover {
            transform: translateY(-10px);
        }
        footer {
            background-color: #003366;
            color: white;
            text-align: center;
            padding: 1rem;
            animation: slideUp 1s ease-out;
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes zoomIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 1rem auto;
            max-width: 1200px;
            text-align: center;
            font-weight: bold;
            animation: fadeIn 1s ease-out;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 1rem auto;
            max-width: 1200px;
            text-align: center;
            font-weight: bold;
            animation: fadeIn 1s ease-out;
        }
    </style>