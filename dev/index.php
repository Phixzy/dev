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

// Fetch all homepage settings
$hero_title = getSetting('hero_title', 'Welcome to University');
$hero_subtitle = getSetting('hero_subtitle', 'Excellence in Education - Empowering Future Leaders');
$footer_copyright = getSetting('footer_copyright', '© 2025 University Name. All rights reserved.');
$logo_url = getSetting('logo_url');
$background_color = getSetting('background_color', '#667eea');
$hero_background = getSetting('hero_background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
$hero_background_image = getSetting('hero_background_image');
$navbar_background = getSetting('navbar_background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
$navbar_text_color = getSetting('navbar_text_color', '#ffffff');

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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Homepage</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f7fa;
            color: #333;
            overflow-x: hidden;
        }

        /* Header */
        header {
            background: <?php echo $navbar_background; ?>;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header h1 {
            color: <?php echo $navbar_text_color; ?>;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        header h1 i {
            font-size: 1.8rem;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }

        nav a {
            color: <?php echo $navbar_text_color; ?>;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-2px);
        }

        nav a i {
            font-size: 1.1rem;
        }

        /* Success/Error Messages */
        .success-message, .error-message {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin: 1rem auto;
            max-width: 1200px;
            text-align: center;
            font-weight: 500;
            animation: slideIn 0.4s ease-out;
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Hero Section */
        .hero {
            background: <?php echo $hero_background_image ? 'url(uploads/' . htmlspecialchars($hero_background_image) . ') center/cover no-repeat' : htmlspecialchars($hero_background); ?>;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
            padding: 2rem;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease-out;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-signup {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        }

        .btn-signup:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4);
        }

        .btn-login {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
            transform: translateY(-3px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Content Sections */
        section {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .section-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        .section-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 1rem auto 0;
        }

        /* About Section */
        .about {
            background: white;
            margin: 2rem;
            max-width: calc(1200px - 4rem);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .about-text h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .about-text p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .about-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            transform: translateX(5px);
            background: #f0f3f7;
        }

        .feature-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon i {
            color: white;
            font-size: 1.2rem;
        }

        .feature-item span {
            font-weight: 500;
            color: #333;
        }

        /* Programs Section */
        .programs {
            margin: 2rem;
            max-width: calc(1200px - 4rem);
        }

        .programs ul {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .programs li {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .programs li::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .programs li:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.2);
        }

        .program-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .program-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .programs li h3 {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 0.75rem;
        }

        .programs li p {
            color: #666;
            line-height: 1.7;
        }

        /* News Section */
        .news {
            background: white;
            margin: 2rem;
            max-width: calc(1200px - 4rem);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .news-item {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .news-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.02);
        }

        .news-date {
            font-size: 0.85rem;
            color: #667eea;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .news-item:hover .news-date {
            color: rgba(255, 255, 255, 0.8);
        }

        .news-item h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .news-item p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 4rem 2rem;
            margin: 2rem 0;
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            color: white;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Footer */
        footer {
            background: #1a1a2e;
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .footer-logo i {
            color: #667eea;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #667eea;
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .about-content {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            nav ul {
                gap: 0.5rem;
            }

            nav a {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            nav a i {
                display: none;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .section-header h2 {
                font-size: 1.8rem;
            }

            .about-features {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>
            <?php if($logo_url): ?>
                <img src="uploads/<?php echo htmlspecialchars($logo_url); ?>" alt="University Logo" style="height: 40px; width: auto; margin-right: 10px;">
            <?php else: ?>
                <i class="uil uil-graduation-cap"></i>
            <?php endif; ?>
            University
        </h1>
        <nav>
            <ul>
                <li><a href="#"><i class="uil uil-estate"></i>Home</a></li>
                <li><a href="student_page/enroll.php"><i class="uil uil-user-plus"></i>Enroll Now</a></li>
                <li><a href="public_contact.php"><i class="uil uil-phone"></i>Contact Admin</a></li>
            </ul>
        </nav>
    </header>

    <?php if($message): ?>
        <div class="success-message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <section class="hero">
        <div class="hero-content">
            <h1><?php echo htmlspecialchars($hero_title); ?></h1>
            <p><?php echo htmlspecialchars($hero_subtitle); ?></p>
            <div class="hero-buttons">
                <a href="student_page/enroll.php" class="btn btn-signup">
                    <i class="uil uil-user-plus"></i> Enroll Now
                </a>
                <a href="login.php" class="btn btn-login">
                    <i class="uil uil-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <i class="uil uil-graduation-cap"></i>
                <span>University</span>
            </div>
            <div class="footer-links">
                <a href="#">Home</a>
                <a href="student_page/enroll.php">Enroll Now</a>
                <a href="public_contact.php">Contact Admin</a>
                <a href="login.php">Login</a>
            </div>
            <div class="footer-bottom">
                <p><?php echo htmlspecialchars($footer_copyright); ?></p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add scroll animation to sections
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('section, .about, .programs, .news').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.success-message, .error-message').forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.style.display = 'none', 500);
            });
        }, 5000);
    </script>
</body>
</html>

