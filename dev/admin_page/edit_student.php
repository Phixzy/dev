<?php
session_start();
include '../config/dbcon.php';

// Check if database connection exists
if (!$conn) {
    $_SESSION['error'] = 'Database connection failed.';
    header('Location: student_status.php');
    exit();
}

// Check if student ID is provided (only for GET requests)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'No student ID provided.';
    header('Location: student_status.php');
    exit();
}

$student_id = intval($_GET['id']);

// Fetch student details (only for GET requests)
$sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Student not found.';
    $stmt->close();
    $conn->close();
    header('Location: student_status.php');
    exit();
}

$student = $result->fetch_assoc();
$stmt->close();

// Format full name
$full_name = trim($student['first_name'] . ' ' . $student['last_name']);
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
    <title>Edit Student - <?php echo htmlspecialchars($full_name); ?></title>
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
            
            <div class="title">
                <i class="fas fa-user-edit"></i>
                <span class="text">Edit Student Information</span>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons-container">
                <a href="view_student.php?id=<?php echo $student_id; ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
                <a href="student_status.php" class="back-button">
                    <i class="fas fa-list"></i> Back to Student Status
                </a>
            </div>
            
            <form method="POST" class="edit-form" id="studentEditForm" action="update_student.php">
                <input type="hidden" name="id" value="<?php echo $student_id; ?>">
                
                <div class="form-container">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                        
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $student['date_of_birth']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="Male" <?php echo ($student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="student_phone">Phone</label>
                                <input type="text" id="student_phone" name="student_phone" value="<?php echo htmlspecialchars($student['student_phone']); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="2"><?php echo htmlspecialchars($student['address']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-id-card"></i> Account Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($student['password']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" required>
                                    <option value="PENDING" <?php echo ($student['status'] == 'PENDING') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="APPROVED" <?php echo ($student['status'] == 'APPROVED') ? 'selected' : ''; ?>>Approved</option>
                                    <option value="REJECTED" <?php echo ($student['status'] == 'REJECTED') ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Guardian Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-users"></i> Guardian Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="guardian_name">Guardian Name</label>
                                <input type="text" id="guardian_name" name="guardian_name" value="<?php echo htmlspecialchars($student['guardian_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="guardian_phone">Guardian Phone</label>
                                <input type="text" id="guardian_phone" name="guardian_phone" value="<?php echo htmlspecialchars($student['guardian_phone']); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label for="guardian_address">Guardian Address</label>
                                <textarea id="guardian_address" name="guardian_address" rows="2"><?php echo htmlspecialchars($student['guardian_address']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Elementary Education -->
                    <div class="form-section">
                        <h3><i class="fas fa-school"></i> Elementary Education</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="elem_name">Elementary School Name</label>
                                <input type="text" id="elem_name" name="elem_name" value="<?php echo htmlspecialchars($student['elem_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="elem_year">Elementary Year</label>
                                <input type="text" id="elem_year" name="elem_year" value="<?php echo htmlspecialchars($student['elem_year']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- High School Education -->
                    <div class="form-section">
                        <h3><i class="fas fa-graduation-cap"></i> High School Education</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="junior_name">Junior High School Name</label>
                                <input type="text" id="junior_name" name="junior_name" value="<?php echo htmlspecialchars($student['junior_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="junior_year">Junior High Year</label>
                                <input type="text" id="junior_year" name="junior_year" value="<?php echo htmlspecialchars($student['junior_year']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="senior_name">Senior High School Name</label>
                                <input type="text" id="senior_name" name="senior_name" value="<?php echo htmlspecialchars($student['senior_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="senior_year">Senior High Year</label>
                                <input type="text" id="senior_year" name="senior_year" value="<?php echo htmlspecialchars($student['senior_year']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="strand">Strand</label>
                                <input type="text" id="strand" name="strand" value="<?php echo htmlspecialchars($student['strand']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- College Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-university"></i> College Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="college_course">College Course</label>
                                <select id="college_course" name="college_course" required>
                                    <option value="BS Computer Science" <?php echo ($student['college_course'] == 'BS Computer Science') ? 'selected' : ''; ?>>BS Computer Science</option>
                                    <option value="BS Information Technology" <?php echo ($student['college_course'] == 'BS Information Technology') ? 'selected' : ''; ?>>BS Information Technology</option>
                                    <option value="BS Computer Engineering" <?php echo ($student['college_course'] == 'BS Computer Engineering') ? 'selected' : ''; ?>>BS Computer Engineering</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="college_year">College Year Level</label>
                                <select id="college_year" name="college_year" required>
                                    <option value="1st Year" <?php echo ($student['college_year'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd Year" <?php echo ($student['college_year'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3rd Year" <?php echo ($student['college_year'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th Year" <?php echo ($student['college_year'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="form-section">
                        <div class="submit-container">
                            <button type="submit" name="update_student" class="submit-btn">
                                <i class="fas fa-save"></i> Update Student Information
                            </button>
                        </div>
                    </div>
                </div>
            </form>
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
        sidebar.classList.toggle("close");
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

    // Form validation and submission
    const editForm = document.getElementById('studentEditForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            // Clear previous error styles
            const allFields = this.querySelectorAll('input, select, textarea');
            allFields.forEach(field => {
                field.style.borderColor = '';
            });
            
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            let emptyFields = [];
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#f44336';
                    isValid = false;
                    emptyFields.push(field.labels[0].textContent);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields:\n' + emptyFields.join('\n'));
                return false;
            }
            
            // Show loading state with enhanced feedback
            const submitBtn = this.querySelector('.submit-btn');
            const form = this;
            
            if (submitBtn) {
                // Add loading class and update content
                submitBtn.classList.add('loading');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Student Information...';
                submitBtn.disabled = true;
                
                // Add subtle form animation during submission
                form.style.opacity = '0.8';
                form.style.transform = 'scale(0.98)';
                
                // Show loading overlay
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    backdrop-filter: blur(2px);
                `;
                
                const spinner = document.createElement('div');
                spinner.style.cssText = `
                    width: 50px;
                    height: 50px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #28a745;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                `;
                
                overlay.appendChild(spinner);
                document.body.appendChild(overlay);
                
                // Store overlay reference for cleanup
                form._loadingOverlay = overlay;
            }
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
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
    *{
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
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

    /* Form Styles */
    .edit-form {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .form-container {
        padding: 30px;
    }

    .form-section {
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 2px solid #e9ecef;
    }

    .form-section:last-child {
        margin-bottom: 0;
        border-bottom: none;
        padding-bottom: 0;
    }

    .form-section h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section h3 i {
        color: #667eea;
        font-size: 1.1rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        color: #495057;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 60px;
    }

    .submit-container {
        text-align: center;
        margin-top: 20px;
    }

    .submit-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 15px 40px;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        position: relative;
        overflow: hidden;
    }

    .submit-btn:hover {
        background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
    }

    .submit-btn:disabled {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        cursor: not-allowed;
        transform: none;
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }

    .submit-btn .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Enhanced Success and Error Message Styling */
    .message {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 1rem 1.5rem !important;
        border-radius: 12px;
        margin-bottom: 1.5rem !important;
        text-align: center;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        position: relative;
        overflow: hidden;
        animation: slideInDown 0.5s ease-out;
    }

    .message::before {
        content: "✓";
        background: #28a745;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        flex-shrink: 0;
    }

    .message:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.25);
        transition: all 0.3s ease;
    }

    .error-message {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 1rem 1.5rem !important;
        border-radius: 12px;
        margin-bottom: 1.5rem !important;
        text-align: center;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(244, 67, 54, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        position: relative;
        overflow: hidden;
        animation: slideInDown 0.5s ease-out;
    }

    .error-message::before {
        content: "✕";
        background: #dc3545;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        flex-shrink: 0;
    }

    .error-message:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(244, 67, 54, 0.25);
        transition: all 0.3s ease;
    }

    @keyframes slideInDown {
        0% {
            opacity: 0;
            transform: translateY(-30px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Enhanced Loading State */
    .submit-btn.loading {
        position: relative;
        color: transparent !important;
    }

    .submit-btn.loading::after {
        content: "";
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }

    /* Success Pulse Animation */
    .message.success-pulse {
        animation: slideInDown 0.5s ease-out, pulse 2s ease-in-out 0.5s;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
        }
        50% {
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        100% {
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
        }
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
        }

        .form-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .action-buttons-container {
            flex-direction: column;
        }

        .message,
        .error-message {
            padding: 0.875rem 1.25rem !important;
            font-size: 0.9rem;
            margin-bottom: 1rem !important;
        }
    }

    @media (max-width: 480px) {
        .edit-form {
            margin: 0 10px;
        }

        .form-container {
            padding: 15px;
        }

        .message,
        .error-message {
            padding: 0.75rem 1rem !important;
            font-size: 0.85rem;
        }
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

