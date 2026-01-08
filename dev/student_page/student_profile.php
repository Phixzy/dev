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

// Get logged-in student info
$student_id = $_SESSION['userid'];
$student_sql = "SELECT * FROM students WHERE id = ?";
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

$stmt->close();
$conn->close();

// Get display username (remove @student suffix if present)
$display_username = $current_student['username'];
if (strpos($display_username, '@student') !== false) {
    $display_username = str_replace('@student', '', $display_username);
}

// Get profile image path
$profile_image = '';
if (!empty($current_student['image'])) {
    $profile_image = '../' . $current_student['image'];
    // Check if file exists
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/dev/' . $current_student['image'])) {
        $profile_image = '';
    }
}

// Status badge color
$status_class = strtolower($current_student['status'] ?? 'pending');
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
    
    <title>Student Profile</title>
</head>
<body>
    <!-- Navigation Sidebar -->
    <nav>
        <div class="logo-name">
            <div class="logo-image">
                <?php
                if (!empty($profile_image)) {
                    echo '<img src="' . htmlspecialchars($profile_image) . '" alt="Profile" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #667eea;">';
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
                <li><a href="contact.php" >
                    <i class="fas fa-phone"></i>
                    <span class="link-name">Contact</span>
                </a></li>
                <li><a href="student_profile.php" class="active">
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
            <!-- Profile Header -->
            <div class="profile-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div class="profile-header-content" style="flex: 1; min-width: 0;">
                    <div class="profile-avatar">
                        <?php if (!empty($profile_image)): ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-graduate"></i>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($current_student['first_name'] . ' ' . $current_student['last_name']); ?></h1>
                        <div class="course-info">
                            <span class="course-badge">
                                <i class="fas fa-graduation-cap" style="margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($current_student['college_course'] ?? 'Not assigned'); ?>
                            </span>
                            
                        </div>
                    </div>
                </div>
                <a href="student_editProfile.php" class="edit-profile-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
            
            <!-- Profile Content Grid -->
            <div class="profile-content">
                <!-- Personal Information Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-user icon-blue"></i>
                        <h3>Personal Information</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>First Name</label>
                            <span><?php echo htmlspecialchars($current_student['first_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Last Name</label>
                            <span><?php echo htmlspecialchars($current_student['last_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Date of Birth</label>
                            <span><?php echo !empty($current_student['date_of_birth']) ? date('F d, Y', strtotime($current_student['date_of_birth'])) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <label>Gender</label>
                            <span><?php echo htmlspecialchars($current_student['gender'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item full-width">
                            <label>Address</label>
                            <span><?php echo htmlspecialchars($current_student['address'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-phone icon-green"></i>
                        <h3>Contact Information</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <label>Email</label>
                            <span><?php echo htmlspecialchars($current_student['email'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Student Phone</label>
                            <span><?php echo htmlspecialchars($current_student['student_phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Username</label>
                            <span><?php echo htmlspecialchars($display_username); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Guardian Information Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-users icon-orange"></i>
                        <h3>Guardian Information</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <label>Guardian Name</label>
                            <span><?php echo htmlspecialchars($current_student['guardian_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Guardian Phone</label>
                            <span><?php echo htmlspecialchars($current_student['guardian_phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item full-width">
                            <label>Guardian Address</label>
                            <span><?php echo htmlspecialchars($current_student['guardian_address'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Academic Information Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <i class="fas fa-graduation-cap icon-purple"></i>
                        <h3>Academic Information</h3>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>College Course</label>
                            <span><?php echo htmlspecialchars($current_student['college_course'] ?? 'Not assigned'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Year Level</label>
                            <span><?php echo htmlspecialchars($current_student['college_year'] ?? 'Not assigned'); ?></span>
                        </div>
                        <div class="info-item full-width">
                            <label>Appointment Details</label>
                            <span><?php echo htmlspecialchars($current_student['appointment_details'] ?? 'No appointment scheduled'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Educational Background -->
            <div class="education-card">
                <div class="education-card-header">
                    <i class="fas fa-book-open"></i>
                    <h3>Educational Background</h3>
                </div>
                <div class="education-timeline">
                    <!-- Elementary -->
                    <div class="education-item">
                        <h4><i class="fas fa-school"></i> Elementary</h4>
                        <p class="school-name"><?php echo htmlspecialchars($current_student['elem_name'] ?? 'N/A'); ?></p>
                        <span class="year"><?php echo htmlspecialchars($current_student['elem_year'] ?? ''); ?></span>
                    </div>
                    
                    <!-- Junior High -->
                    <div class="education-item">
                        <h4><i class="fas fa-building"></i> Junior High</h4>
                        <p class="school-name"><?php echo htmlspecialchars($current_student['junior_name'] ?? 'N/A'); ?></p>
                        <span class="year"><?php echo htmlspecialchars($current_student['junior_year'] ?? ''); ?></span>
                    </div>
                    
                    <!-- Senior High -->
                    <div class="education-item">
                        <h4><i class="fas fa-university"></i> Senior High</h4>
                        <p class="school-name"><?php echo htmlspecialchars($current_student['senior_name'] ?? 'N/A'); ?></p>
                        <span class="year"><?php echo htmlspecialchars($current_student['senior_year'] ?? ''); ?></span>
                    </div>
                    
                    <!-- Strand -->
                    <div class="education-item strand-item">
                        <h4><i class="fas fa-certificate"></i> Strand</h4>
                        <p class="school-name"><?php echo htmlspecialchars($current_student['strand'] ?? 'N/A'); ?></p>
                        <span class="year"><?php echo !empty($current_student['senior_year']) ? 'Graduated: ' . htmlspecialchars($current_student['senior_year']) : ''; ?></span>
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
                if (sidebar.classList.contains("close")) {
                    localStorage.setItem("status", "close");
                } else {
                    localStorage.setItem("status", "open");
                }
            });
        }

        // Auto-hide messages after 3 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.alert-message');
            messages.forEach(message => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 500);
            });
        }, 3000);

        // Add smooth hover effects to cards
        document.querySelectorAll('.info-card, .education-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>

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
            transition: color 0.3s ease;
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
            transition: color 0.3s ease;
        }

        .dashboard .dash-content {
            padding-top: 10px;
        }

        /* Title Section */
        .title {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e5ec;
        }

        .title i {
            font-size: 28px;
            color: #667eea;
            margin-right: 12px;
        }

        .title .text {
            font-size: 22px;
            font-weight: 600;
            color: #2d3748;
        }

        /* Profile Header Card */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%;
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar i {
            font-size: 50px;
            color: rgba(255, 255, 255, 0.9);
        }

        .profile-info h1 {
            font-size: 28px;
            color: #fff;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-info .student-id {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
        }

        .profile-info .course-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-info .course-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 20px;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(5px);
        }

        .profile-info .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending {
            background: rgba(255, 193, 7, 0.8);
        }

        .status-badge.approved {
            background: rgba(40, 167, 69, 0.8);
        }

        .status-badge.rejected {
            background: rgba(220, 53, 69, 0.8);
        }

        /* Profile Content Grid */
        .profile-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f3f7;
        }

        .info-card-header i {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }

        .info-card-header .icon-blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .info-card-header .icon-green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: #fff;
        }

        .info-card-header .icon-orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
        }

        .info-card-header .icon-purple {
            background: linear-gradient(135deg, #5f72bd 0%, #9b23ea 100%);
            color: #fff;
        }

        .info-card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item.full-width {
            grid-column: span 2;
        }

        .info-item label {
            font-size: 12px;
            font-weight: 500;
            color: #718096;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
            padding: 8px 12px;
            background: #f5f7fa;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        /* Educational Background Card */
        .education-card {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .education-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .education-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f3f7;
        }

        .education-card-header i {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #fff;
        }

        .education-card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
        }

        .education-timeline {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .education-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .education-item::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0 0 0 100%;
        }

        .education-item h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .education-item h4 i {
            font-size: 18px;
        }

        .education-item .school-name {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .education-item .year {
            font-size: 13px;
            opacity: 0.8;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: inline-block;
            margin-top: 8px;
        }

        .education-item.strand-item {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            animation: slideIn 0.4s ease-out;
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

        /* Responsive Design */
        @media (max-width: 1024px) {
            .profile-content {
                grid-template-columns: 1fr;
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

            nav .menu-items {
                overflow: visible;
            }

            .dashboard {
                left: 73px;
                width: calc(100% - 73px);
                padding: 15px 20px;
            }

            .profile-header {
                padding: 25px;
            }

            .profile-header-content {
                flex-direction: column;
                text-align: center;
            }

            .profile-info .course-info {
                flex-direction: column;
                gap: 10px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-item.full-width {
                grid-column: span 1;
            }

            .education-timeline {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .profile-avatar {
                width: 100px;
                height: 100px;
            }

            .profile-info h1 {
                font-size: 22px;
            }

            .info-card {
                padding: 20px;
            }

            .education-card {
                padding: 20px;
            }
        }

        /* Animations */
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

        .info-card,
        .education-card {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .info-card:nth-child(1) { animation-delay: 0.1s; }
        .info-card:nth-child(2) { animation-delay: 0.2s; }
        .info-card:nth-child(3) { animation-delay: 0.3s; }
        .info-card:nth-child(4) { animation-delay: 0.4s; }

        .education-card {
            animation-delay: 0.5s;
        }

        /* Edit Profile Button */
        .edit-profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            backdrop-filter: blur(5px);
            position: relative;
            z-index: 2;
            margin-top: 10px;
        }

        .edit-profile-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .edit-profile-btn i {
            font-size: 16px;
        }
    </style>