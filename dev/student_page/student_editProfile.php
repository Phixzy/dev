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
    
    <title>Edit Profile - <?php echo htmlspecialchars($display_username); ?></title>
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

        /* Form Styles */
        .edit-form {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .edit-form:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f3f7;
        }

        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .form-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-section h3 i {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #fff;
        }

        .form-section h3 .icon-blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .form-section h3 .icon-green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .form-section h3 .icon-orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .form-section h3 .icon-purple {
            background: linear-gradient(135deg, #5f72bd 0%, #9b23ea 100%);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: #718096;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:disabled,
        .form-group select:disabled,
        .form-group textarea:disabled {
            background: #edf2f7;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f3f7;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
            background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: #fff;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: #fff;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
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

            .edit-form {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .dashboard {
                padding: 10px 15px;
            }

            .edit-form {
                padding: 15px;
            }

            .title .text {
                font-size: 18px;
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

        .edit-form {
            animation: fadeInUp 0.6s ease-out forwards;
        }
    </style>
</head>
<body>
    <!-- Navigation Sidebar -->
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
           
                <li><a href="student_emails.php">
                    <i class="fas fa-comments"></i>
                    <span class="link-name">Emails</span>
                </a></li>
                <li><a href="student_profile.php">
                    <i class="fas fa-user"></i>
                    <span class="link-name">Profile</span>
                </a></li>
                <li><a href="student_editProfile.php" class="active">
                    <i class="fas fa-edit"></i>
                    <span class="link-name">Edit Profile</span>
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
            <div class="title">
                <i class="fas fa-edit"></i>
                <span class="text">Edit Profile</span>
            </div>
            
            <form method="POST" class="edit-form" id="editProfileForm" action="student_updateProfile.php">
                <input type="hidden" name="student_id" value="<?php echo $current_student['id']; ?>">
                
                <!-- Personal Information -->
                <div class="form-section">
                    <h3><i class="fas fa-user icon-blue"></i> Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($current_student['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($current_student['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($current_student['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($current_student['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($current_student['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($current_student['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="student_phone">Phone Number</label>
                            <input type="text" id="student_phone" name="student_phone" value="<?php echo htmlspecialchars($current_student['student_phone'] ?? ''); ?>" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($current_student['address'] ?? ''); ?>" placeholder="Enter address">
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="form-section">
                    <h3><i class="fas fa-envelope icon-green"></i> Contact Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_student['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($display_username); ?>" minlength="3" maxlength="50" placeholder="Enter username">
                        </div>
                    </div>
                </div>
                
                <!-- Account Security -->
                <div class="form-section">
                    <h3><i class="fas fa-lock icon-orange"></i> Account Security</h3>
                    <p style="color: #718096; font-size: 13px; margin-bottom: 15px;">Leave password fields empty if you don't want to change your password.</p>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="old_password">Current Password *</label>
                            <input type="password" id="old_password" name="old_password" placeholder="Enter current password">
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>
                
                <!-- Guardian Information -->
                <div class="form-section">
                    <h3><i class="fas fa-users icon-orange"></i> Guardian Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="guardian_name">Guardian Name</label>
                            <input type="text" id="guardian_name" name="guardian_name" value="<?php echo htmlspecialchars($current_student['guardian_name'] ?? ''); ?>" placeholder="Enter guardian name">
                        </div>
                        <div class="form-group">
                            <label for="guardian_phone">Guardian Phone</label>
                            <input type="text" id="guardian_phone" name="guardian_phone" value="<?php echo htmlspecialchars($current_student['guardian_phone'] ?? ''); ?>" placeholder="Enter guardian phone">
                        </div>
                        <div class="form-group full-width">
                            <label for="guardian_address">Guardian Address</label>
                            <textarea id="guardian_address" name="guardian_address" placeholder="Enter guardian address"><?php echo htmlspecialchars($current_student['guardian_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Educational Background -->
                <div class="form-section">
                    <h3><i class="fas fa-graduation-cap icon-purple"></i> Educational Background</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="elem_name">Elementary School</label>
                            <input type="text" id="elem_name" name="elem_name" value="<?php echo htmlspecialchars($current_student['elem_name'] ?? ''); ?>" placeholder="Elementary school name">
                        </div>
                        <div class="form-group">
                            <label for="elem_year">Elementary Year</label>
                            <input type="text" id="elem_year" name="elem_year" value="<?php echo htmlspecialchars($current_student['elem_year'] ?? ''); ?>" placeholder="e.g., 2010-2016">
                        </div>
                        <div class="form-group">
                            <label for="junior_name">Junior High School</label>
                            <input type="text" id="junior_name" name="junior_name" value="<?php echo htmlspecialchars($current_student['junior_name'] ?? ''); ?>" placeholder="Junior high school name">
                        </div>
                        <div class="form-group">
                            <label for="junior_year">Junior High Year</label>
                            <input type="text" id="junior_year" name="junior_year" value="<?php echo htmlspecialchars($current_student['junior_year'] ?? ''); ?>" placeholder="e.g., 2016-2018">
                        </div>
                        <div class="form-group">
                            <label for="senior_name">Senior High School</label>
                            <input type="text" id="senior_name" name="senior_name" value="<?php echo htmlspecialchars($current_student['senior_name'] ?? ''); ?>" placeholder="Senior high school name">
                        </div>
                        <div class="form-group">
                            <label for="senior_year">Senior High Year</label>
                            <input type="text" id="senior_year" name="senior_year" value="<?php echo htmlspecialchars($current_student['senior_year'] ?? ''); ?>" placeholder="e.g., 2018-2020">
                        </div>
                        <div class="form-group">
                            <label for="strand">Strand/Track</label>
                            <input type="text" id="strand" name="strand" value="<?php echo htmlspecialchars($current_student['strand'] ?? ''); ?>" placeholder="e.g., STEM, ABM, GAS">
                        </div>
                    </div>
                </div>
                
                <!-- Academic Information (Read-only) -->
                <div class="form-section">
                    <h3><i class="fas fa-university" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></i> Academic Information (Contact Admin to change)</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="college_course">College Course</label>
                            <input type="text" id="college_course" value="<?php echo htmlspecialchars($current_student['college_course'] ?? 'Not assigned'); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="college_year">Year Level</label>
                            <input type="text" id="college_year" value="<?php echo htmlspecialchars($current_student['college_year'] ?? 'Not assigned'); ?>" disabled>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" name="update_profile" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="student_profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
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

        // Form validation and submission
        const editForm = document.getElementById('editProfileForm');
        const submitBtn = document.getElementById('submitBtn');
        const oldPassword = document.getElementById('old_password');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const usernameInput = document.getElementById('username');
        
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                // Clear previous error styles
                const allFields = this.querySelectorAll('input[required], select[required]');
                let isValid = true;
                let emptyFields = [];
                
                allFields.forEach(field => {
                    field.style.borderColor = '';
                    if (!field.value.trim()) {
                        field.style.borderColor = '#f44336';
                        isValid = false;
                        emptyFields.push(field.labels[0].textContent.replace(' *', ''));
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields:\n' + emptyFields.join('\n'));
                    return false;
                }
                
                // Validate username format
                if (usernameInput && usernameInput.value.trim()) {
                    const username = usernameInput.value.trim();
                    if (username.length < 3) {
                        e.preventDefault();
                        usernameInput.style.borderColor = '#f44336';
                        alert('Username must be at least 3 characters long.');
                        return false;
                    }
                    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                        e.preventDefault();
                        usernameInput.style.borderColor = '#f44336';
                        alert('Username can only contain letters, numbers, and underscores.');
                        return false;
                    }
                }
                
                // Validate password change
                if (oldPassword && oldPassword.value) {
                    if (!newPassword.value) {
                        e.preventDefault();
                        newPassword.style.borderColor = '#f44336';
                        alert('Please enter a new password.');
                        return false;
                    }
                    if (newPassword.value !== confirmPassword.value) {
                        e.preventDefault();
                        confirmPassword.style.borderColor = '#f44336';
                        alert('New password and confirm password do not match.');
                        return false;
                    }
                }
                
                // If new password is entered but old password is not provided
                if ((newPassword.value || confirmPassword.value) && !oldPassword.value) {
                    e.preventDefault();
                    oldPassword.style.borderColor = '#f44336';
                    alert('Please enter your current password to change your password.');
                    return false;
                }
                
                // Show loading state - form will submit normally
                // Don't disable the button as it won't be included in POST data
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.style.opacity = '0.7';
                submitBtn.style.cursor = 'wait';
            });
        }
    </script>
</body>
</html>

