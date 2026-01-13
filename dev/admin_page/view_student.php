<?php
session_start();
include '../config/dbcon.php';

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'No student ID provided.';
    header('Location: student_status.php');
    exit();
}

$student_id = intval($_GET['id']);

// Fetch student details - using 'students' table
$sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Student not found.';
    header('Location: student_status.php');
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();
// Don't close connection yet - we may need it

// Format full name
$full_name = trim($student['first_name'] . ' ' . $student['last_name']);

// Convert course codes to full names
$course_name = '';
switch($student['college_course']) {
    case 'BS Computer Science':
        $course_name = 'BS Computer Science';
        break;
    case 'BS Information Technology':
        $course_name = 'BS Information Technology';
        break;
    case 'BS Computer Engineering':
        $course_name = 'BS Computer Engineering';
        break;
    default:
        $course_name = $student['college_course'];
}

// Status styling
$status_class = '';
$status_icon = '';
switch(strtoupper($student['status'])) {
    case 'APPROVED':
        $status_class = 'style="background: #d4edda; color: #155724; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;"';
        $status_icon = '<i class="fas fa-check"></i>';
        break;
    case 'PENDING':
        $status_class = 'style="background: #fff3cd; color: #856404; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;"';
        $status_icon = '<i class="fas fa-clock"></i>';
        break;
    case 'REJECTED':
        $status_class = 'style="background: #f8d7da; color: #721c24; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;"';
        $status_icon = '<i class="fas fa-times"></i>';
        break;
    default:
        $status_class = 'style="background: #e2e3e5; color: #6c757d; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;"';
        $status_icon = '<i class="fas fa-question"></i>';
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
    <title>Student Profile - <?php echo htmlspecialchars($full_name); ?></title>
</head>
<body>
    <!-- Navigation Sidebar -->
    <nav>
        <div class="logo-name">
                      <span class="logo_name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>

        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="link-name">Appointments</span>
                </a></li>   
                <li><a href="student_status.php" class="active">
                    <i class="fas fa-user-check"></i>
                    <span class="link-name">Student Status</span>
                </a></li>
                <li><a href="grades.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-name">Grades</span>
                </a></li>
                <li><a href="master_list.php">
                    <i class="fas fa-list-alt"></i>
                    <span class="link-name">Master List</span>
                </a></li>
                <li><a href="emails.php">
                    <i class="fas fa-envelope"></i>
                    <span class="link-name">Emails</span>
                </a></li>
                <li><a href="admin_user.php">
                    <i class="fas fa-user-shield"></i>
                    <span class="link-name">Admin User Management</span>
                </a></li>
               
            </ul>
            
            <ul class="logout-mode">
                <li><a href="#">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-name">Logout</span>
                </a></li>
                <li class="mode">
                    
               
            </li>
            </ul>
        </div>
    </nav>
    <section class="dashboard">
        <div class="top">
            <span class="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </span>
        </div>
        <div class="dash-content">
            <!-- Display messages -->
            <div class="messages-container">
            <?php
            if (isset($_SESSION['message'])) {
                echo '<div class="message" style="background: rgba(76, 175, 80, 0.2); border: 1px solid #4CAF50; color: #4CAF50; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-weight: 500;">';
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                echo '</div>';
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message" style="background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-weight: 500;">';
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                echo '</div>';
            }
            ?>
            </div>
            
            <div class="title">
                <i class="fas fa-user-graduate"></i>
                <span class="text">Student Profile</span>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons-container">
                <a href="student_status.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Student Status
                </a>
                <a href="edit_student.php?id=<?php echo $student_id; ?>" class="edit-button">
                    <i class="fas fa-edit"></i> Edit Student Information
                </a>
            </div>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-image">
                            <?php if (!empty($student['image']) && file_exists('../' . $student['image'])): ?>
                            <img src="../<?php echo htmlspecialchars($student['image']); ?>" alt="Student Image" onerror="this.src='https://via.placeholder.com/150/667eea/ffffff?text=No+Image'">
                        <?php else: ?>
                            <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="font-size: 50px; color: white;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($full_name); ?></h1>
                        <div class="status-badge">
                            <span <?php echo $status_class; ?>><?php echo $status_icon; ?> <?php echo htmlspecialchars($student['status']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-section">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>First Name</label>
                                <span><?php echo htmlspecialchars($student['first_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Last Name</label>
                                <span><?php echo htmlspecialchars($student['last_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Date of Birth</label>
                                <span><?php echo $student['date_of_birth'] ? date('M j, Y', strtotime($student['date_of_birth'])) : 'Not provided'; ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Gender</label>
                                <span><?php echo htmlspecialchars($student['gender'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Phone</label>
                                <span><?php echo htmlspecialchars($student['student_phone'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Address</label>
                                <span><?php echo htmlspecialchars($student['address'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-id-card"></i> Account Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Email</label>
                                <span><?php echo htmlspecialchars($student['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Username</label>
                                <span><?php echo htmlspecialchars($student['username']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Status</label>
                                <span><?php echo htmlspecialchars($student['status']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Registration Date</label>
                                <span><?php echo date('M j, Y g:i A', strtotime($student['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-users"></i> Guardian Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Guardian Name</label>
                                <span><?php echo htmlspecialchars($student['guardian_name'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Guardian Phone</label>
                                <span><?php echo htmlspecialchars($student['guardian_phone'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Guardian Address</label>
                                <span><?php echo htmlspecialchars($student['guardian_address'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-school"></i> Elementary Education</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Elementary School Name</label>
                                <span><?php echo htmlspecialchars($student['elem_name'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Elementary Year</label>
                                <span><?php echo htmlspecialchars($student['elem_year'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3><i class="fas fa-graduation-cap"></i> High School Education</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Junior High School Name</label>
                                <span><?php echo htmlspecialchars($student['junior_name'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Junior High Year</label>
                                <span><?php echo htmlspecialchars($student['junior_year'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Senior High School Name</label>
                                <span><?php echo htmlspecialchars($student['senior_name'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Senior High Year</label>
                                <span><?php echo htmlspecialchars($student['senior_year'] ?: 'Not provided'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Strand</label>
                                <span><?php echo htmlspecialchars($student['strand'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-university"></i> College Information</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>College Course</label>
                                <span><?php echo htmlspecialchars($course_name); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Year Level</label>
                                <span><?php echo htmlspecialchars($student['college_year'] ?: 'Not provided'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($student['appointment_details'])): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-calendar-check"></i> Appointment Information</h3>
                        <div class="detail-grid">
                           
                            <div class="detail-item">
                                <label>Appointment Details</label>
                                <span><?php echo htmlspecialchars($student['appointment_details']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

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

    // Password toggle functionality
    function togglePassword(icon) {
        const passwordSpan = icon.previousElementSibling;
        if (passwordSpan.textContent === '••••••••') {
            // Show actual password from database
            passwordSpan.textContent = '<?php echo htmlspecialchars($student["password"]); ?>';
            icon.className = 'fas fa-eye password-toggle';
        } else {
            passwordSpan.textContent = '••••••••';
            icon.className = 'fas fa-eye-slash password-toggle';
        }
    }



    // Enhanced button functionality
    document.addEventListener('DOMContentLoaded', function() {
        const backButton = document.querySelector('.back-button');
        if (backButton) {
            backButton.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 16px rgba(102, 126, 234, 0.4)';
            });
            
            backButton.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 8px rgba(102, 126, 234, 0.3)';
            });
        }

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message, .error-message');
            messages.forEach(message => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 500);
            });
        }, 5000);
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
    *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    /* Messages Container - Clear fixed top navbar */
    .messages-container {
        margin-top: 80px;
        position: relative;
        z-index: 5;
    }

    /* Action Buttons Container */
    .action-buttons-container {
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        border: none;
        cursor: pointer;
    }

    .back-button:hover {
        background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(108, 117, 125, 0.4);
    }

    .edit-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        border: none;
        cursor: pointer;
    }

    .edit-button:hover {
        background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
    }

    .edit-button i {
        font-size: 0.9rem;
    }

    /* Profile Container */
    .profile-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    /* Profile Header */
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 30px;
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .profile-image {
        flex-shrink: 0;
    }

    .profile-image img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }

    .profile-info h1 {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 8px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .student-id {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 15px;
    }

    .status-badge {
        display: inline-block;
    }

    /* Profile Details */
    .profile-details {
        padding: 30px;
    }

    .detail-section {
        margin-bottom: 40px;
    }

    .detail-section:last-child {
        margin-bottom: 0;
    }

    .detail-section h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .detail-section h3 i {
        color: #667eea;
        font-size: 1.1rem;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .detail-item label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-item span {
        font-size: 1rem;
        font-weight: 500;
        color: #495057;
        padding: 12px 16px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    /* Password field styling */
    .password-field {
        position: relative;
        display: inline-block;
        width: fit-content;
    }

    .password-toggle {
        position: absolute;
        right: -30px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        font-size: 0.9rem;
        padding: 4px;
        transition: color 0.3s ease;
    }

    .password-toggle:hover {
        color: #495057;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
        }

        .profile-info h1 {
            font-size: 1.5rem;
        }

        .profile-details {
            padding: 20px;
        }

        .detail-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .profile-image img {
            width: 100px;
            height: 100px;
        }
    }

    @media (max-width: 480px) {
        .profile-container {
            margin: 0 10px;
        }

        .profile-header {
            padding: 20px 15px;
        }

        .profile-details {
            padding: 15px;
        }
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

    /* Navigation Sidebar - Student Profile Style */
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

    .dashboard .title {
        display: flex;
        align-items: center;
        margin: 80px 0 30px 0;
    }

    .dashboard .title i {
        position: relative;
        height: 40px;
        width: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .dashboard .title .text {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin-left: 12px;
    }

    /* Responsive adjustments */
    @media (max-width: 1000px) {
        nav {
            width: 73px;
        }
        nav.close {
            width: 250px;
        }
        nav.close .logo_name,
        nav.close li a .link-name {
            opacity: 1;
            pointer-events: auto;
        }
        nav.close ~ .dashboard,
        nav.close ~ .dashboard .top {
            left: 250px;
            width: calc(100% - 250px);
        }
    }
    
    @media (max-width: 780px) {
        .dashboard .title{
            margin: 80px 0 20px 0;
        }
        .dashboard .title .text{
            font-size: 20px;
        }
    }
    
    @media (max-width: 560px) {
        .dashboard .title{
            margin: 80px 0 15px 0;
        }
        .dashboard .title .text{
            font-size: 18px;
        }
    }
</style>

