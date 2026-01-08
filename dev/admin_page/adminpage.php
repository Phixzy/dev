<?php
session_start();
require_once '../config/dbcon.php';

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_username']) && !empty($_SESSION['admin_username']);
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Admin Authentication Check
if (!isAdminLoggedIn()) {
    // Redirect to login page if not logged in
    header('Location: ../login.php');
    exit();
}

// Message handling functions
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

function displayMessages() {
    if (isset($_SESSION['error_message'])) {
        echo '<div class="global-message error-message">';
        echo '<i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['error_message']);
        echo '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        echo '<div class="global-message message">';
        echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['success_message']);
        echo '</div>';
        unset($_SESSION['success_message']);
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setErrorMessage("Invalid form submission. Please try again.");
        // Regenerate token on error
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: adminpage.php');
        exit();
    }
    
    // Regenerate token after use to prevent replay attacks
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Handle appointment form
    if (isset($_POST['appointment_date']) && isset($_POST['start_time'])) {
        $appointment_date = $_POST['appointment_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $total_slots = $_POST['total_slots'];
        $available_slots = $total_slots; // Initially, available slots equal total slots
        
        // Basic validation
        if (empty($appointment_date) || empty($start_time) || empty($end_time) || empty($total_slots)) {
            setErrorMessage("All fields are required!");
            header('Location: adminpage.php');
            exit();
        }
        
        // Validate time range
        if (strtotime($start_time) >= strtotime($end_time)) {
            setErrorMessage("End time must be after start time!");
            header('Location: adminpage.php');
            exit();
        }
        
        // Insert into appointments table
        $sql = "INSERT INTO appointments (appointment_date, start_time, end_time, total_slots, available_slots) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $appointment_date, $start_time, $end_time, $total_slots, $available_slots);
        
        if ($stmt->execute()) {
            setSuccessMessage("Appointment schedule added successfully!");
        } else {
            setErrorMessage("Error: " . $conn->error);
        }
        
        $stmt->close();
        header('Location: adminpage.php');
        exit();
    }
    
    // Handle subject form
    if (isset($_POST['subject_name']) || isset($_POST['subject_code'])) {
        $subject_name = $_POST['subject_name'];
        $subject_code = $_POST['subject_code'];
        $course = $_POST['course'];
        $year_level = $_POST['year_level'];
        $hour = $_POST['hour'];
        $instructor_name = $_POST['instructor_name'];
        
        // Validate input
        if (empty($subject_name) || empty($subject_code) || empty($course) || 
            empty($year_level) || empty($hour) || empty($instructor_name)) {
            setErrorMessage("All fields are required!");
            header('Location: adminpage.php');
            exit();
        }
        
        // Check if subject code already exists
        $check_sql = "SELECT id FROM subjects WHERE subject_code = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $subject_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            setErrorMessage("Subject code already exists! Please choose a different code.");
            header('Location: adminpage.php');
            exit();
        }
        
        // Insert into subjects table
        $sql = "INSERT INTO subjects (subject_name, subject_code, course, year_level, hour, instructor_name) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssis", $subject_name, $subject_code, $course, $year_level, $hour, $instructor_name);
        
        if ($stmt->execute()) {
            setSuccessMessage("Subject and instructor added successfully!");
        } else {
            setErrorMessage("Error: " . $conn->error);
        }
        
        $stmt->close();
        header('Location: adminpage.php');
        exit();
    }
}

// Debug: Check database connection
if (!$conn) {
    die("Database connection failed: " . $conn->connect_error);
}

// Test database connection
if ($conn->ping()) {
    // Database connection is working
} else {
    setErrorMessage("Database connection lost: " . $conn->error);
}

// Debug logging for troubleshooting
error_log("Database connection status: " . ($conn ? "Connected" : "Failed"));
error_log("MySQL server info: " . $conn->server_info);

// Fetch appointments data with error handling
$appointments = [];
$sql = "SELECT * FROM appointments ORDER BY appointment_date DESC, start_time DESC";
$result = $conn->query($sql);

if (!$result) {
    setErrorMessage("Error fetching appointments: " . $conn->error);
} else {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    } else {
        // No appointments found - this is normal
    }
}

// Fetch subjects data with error handling
$subjects = [];
$sql = "SELECT * FROM subjects ORDER BY course, year_level, subject_name";
$result = $conn->query($sql);

if (!$result) {
    setErrorMessage("Error fetching subjects: " . $conn->error);
} else {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    } else {
        // No subjects found - this is normal
    }
}

// Debug: Check if tables exist
$table_check_sql = "SHOW TABLES LIKE 'appointments'";
$result = $conn->query($table_check_sql);
$appointments_table_exists = ($result && $result->num_rows > 0);

$table_check_sql = "SHOW TABLES LIKE 'subjects'";
$result = $conn->query($table_check_sql);
$subjects_table_exists = ($result && $result->num_rows > 0);

// If tables don't exist, show error
if (!$appointments_table_exists || !$subjects_table_exists) {
    setErrorMessage("Database tables missing. Please ensure 'appointments' and 'subjects' tables exist in your database.");
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
    <!-- Enhanced Message Assets -->
    
    <title>Admin Dashboard</title>
</head>
<body>
    <!-- Navigation Sidebar -->
    <nav>
        <div class="logo-name">
                        <span class="logo_name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>

        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php" class="active">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="link-name">Appointments</span>
                </a></li>
                <li><a href="student_status.php">
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
                <li><a href="edit_homepage.php">
                    <i class="fas fa-edit"></i>
                    <span class="link-name">Edit Homepage</span>
                </a></li>
                <li><a href="admin_user.php">
                    <i class="fas fa-user"></i>
                    <span class="link-name">Admin User Mangement</span>
                </a></li>
            </ul>
            
            <ul class="logout-mode">
                <li><a href="../student_page/logout.php" onclick="return confirm('Are you sure you want to logout?');">
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
            <br>
            <!-- Display enhanced styled messages -->
            <div id="messagesContainer">
                <?php displayMessages(); ?>
            </div>
            <div class="activity">
                <div class="title">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="text">Scheduled Appointments</span>
                </div>
                <div class="table-container">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar"></i> Date</th>
                                <th><i class="fas fa-clock"></i> Start Time</th>
                                <th><i class="fas fa-clock"></i> End Time</th>
                                <th><i class="fas fa-users"></i> Total Slots</th>
                                <th><i class="fas fa-check-circle"></i> Available</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="6" class="no-data">
                                        <i class="fas fa-calendar-times" style="font-size: 24px; margin-bottom: 10px; display: block; color: #6c757d;"></i>
                                        <strong>No appointments scheduled yet</strong><br>
                                        <small>Use the form below to add your first appointment</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td class="cell-text">
                                            <i class="fas fa-calendar" style="color: #667eea; margin-right: 5px;"></i>
                                            <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                        </td>
                                        <td class="cell-text">
                                            <i class="fas fa-clock" style="color: #17a2b8; margin-right: 5px;"></i>
                                            <?php echo date('h:i A', strtotime($appointment['start_time'])); ?>
                                        </td>
                                        <td class="cell-text">
                                            <i class="fas fa-clock" style="color: #17a2b8; margin-right: 5px;"></i>
                                            <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                                        </td>
                                        <td class="cell-text">
                                            <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                                <i class="fas fa-users" style="margin-right: 3px;"></i>
                                                <?php echo $appointment['total_slots']; ?>
                                            </span>
                                        </td>
                                        <td class="cell-text">
                                            <?php 
                                            $available = $appointment['available_slots'];
                                            $bg_color = '';
                                            $text_color = 'white';
                                            
                                            if ($available == 0) {
                                                $bg_color = '#dc3545'; // Red for no slots
                                            } elseif ($available <= 3) {
                                                $bg_color = '#fd7e14'; // Orange for low slots (1-3)
                                                $text_color = 'white';
                                            } else {
                                                $bg_color = '#28a745'; // Green for good availability
                                            }
                                            ?>
                                            <span style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                <i class="fas fa-check-circle" style="margin-right: 3px;"></i>
                                                <?php echo $available; ?>
                                                <?php if ($available <= 3 && $available > 0): ?>
                                                    <i class="fas fa-exclamation-triangle" style="margin-left: 3px; font-size: 0.7rem;"></i>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-edit" onclick="openEditAppointmentModal(<?php echo $appointment['id']; ?>, '<?php echo $appointment['appointment_date']; ?>', '<?php echo $appointment['start_time']; ?>', '<?php echo $appointment['end_time']; ?>', <?php echo $appointment['total_slots']; ?>, <?php echo $appointment['available_slots']; ?>)" title="Edit Appointment">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="deleteAppointment.php?id=<?php echo $appointment['id']; ?>" class="btn-reject" onclick="return confirm('Are you sure you want to delete this appointment?')" title="Delete Appointment" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none; min-width: 36px; height: 36px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 8px 10px;">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

    <!-- Add New Appointment Form -->
    <div class="title">
        <i class="fas fa-plus-circle"></i>
        <span class="text">Schedule New Appointment</span>
    </div>
    
    <div class="form-container">
        <form method="post" action="adminpage.php" class="appointment-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="appointment_date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="appointment_date" name="appointment_date" required>
                </div>
                <div class="form-group">
                    <label for="start_time"><i class="fas fa-clock"></i> Start Time</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label for="end_time"><i class="fas fa-clock"></i> End Time</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label for="total_slots"><i class="fas fa-users"></i> Total Slots</label>
                    <input type="number" id="total_slots" name="total_slots" min="1" max="50" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(102, 126, 234, 0.3)'">
                        <i class="fas fa-plus" style="font-size: 16px;"></i> Add Appointment
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Subject & Instructor Management -->
    <div class="title">
        <i class="fas fa-book"></i>
        <span class="text">Subject & Instructor Management</span>
    </div>
    
    <!-- Add New Subject Form -->
    <div class="form-container">
        <form method="post" action="adminpage.php" class="subject-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="subject_name"><i class="fas fa-book"></i> Subject Name</label>
                    <input type="text" id="subject_name" name="subject_name" required placeholder="Data Structures and Algorithms">
                </div>
                <div class="form-group">
                    <label for="subject_code"><i class="fas fa-code"></i> Subject Code</label>
                    <input type="text" id="subject_code" name="subject_code" required placeholder="CS201">
                </div>
                <div class="form-group">
                    <label for="course"><i class="fas fa-graduation-cap"></i> Course</label>
                    <select id="course" name="course" required>
                        <option value="">Select Course</option>
                        <option value="BS Computer Science">BS Computer Science</option>
                        <option value="BS Information Technology">BS Information Technology</option>
                        <option value="BS Computer Engineering">BS Computer Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year_level"><i class="fas fa-users"></i> Year Level</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">Select Year Level</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hour"><i class="fas fa-clock"></i>Subject Hours</label>
                    <input type="number" id="hour" name="hour" min="1" max="10" required placeholder="Hours per subject">
                </div>
                <div class="form-group">
                    <label for="instructor_name"><i class="fas fa-user"></i> Instructor Name</label>
                    <input type="text" id="instructor_name" name="instructor_name" required placeholder="Prof. John Smith">
                </div>
               
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(40, 167, 69, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(40, 167, 69, 0.3)'">
                        <i class="fas fa-plus" style="font-size: 16px;"></i> Add Subject & Instructor
                    </button>
                </div>
             
            </div>
        </form>
    </div>

    <!-- Subject & Instructor List -->
    <div class="table-container">
        <table class="compact-table">
            <thead>
                <tr>
                    <th><i class="fas fa-book"></i> Subject Name</th>
                    <th><i class="fas fa-code"></i> Subject Code</th>
                    <th><i class="fas fa-graduation-cap"></i> Course</th>
                    <th><i class="fas fa-users"></i> Year Level</th>
                    <th><i class="fas fa-user"></i> Instructor</th>
                    <th><i class="fas fa-clock"></i> Hours</th>
                    <th><i class="fas fa-cog"></i> Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="7" class="no-data">
                            <i class="fas fa-book" style="font-size: 24px; margin-bottom: 10px; display: block; color: #6c757d;"></i>
                            <strong>No subjects added yet</strong><br>
                            <small>Use the form above to add your first subject</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td class="cell-text">
                                <i class="fas fa-book" style="color: #667eea; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </td>
                            <td class="cell-text">
                                <i class="fas fa-code" style="color: #17a2b8; margin-right: 5px;"></i>
                                <strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong>
                            </td>
                            <td class="cell-text">
                                <i class="fas fa-graduation-cap" style="color: #28a745; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($subject['course']); ?>
                            </td>
                            <td class="cell-text">
                                <i class="fas fa-users" style="color: #ffc107; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($subject['year_level']); ?>
                            </td>
                            <td class="cell-text">
                                <i class="fas fa-user" style="color: #dc3545; margin-right: 5px;"></i>
                                <?php echo htmlspecialchars($subject['instructor_name']); ?>
                            </td>
                            <td class="cell-text">
                                <span style="background: #6c757d; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fas fa-clock" style="margin-right: 3px;"></i>
                                    <?php echo $subject['hour']; ?>h
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-edit" onclick="openEditSubjectModal(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['subject_name']); ?>', '<?php echo htmlspecialchars($subject['subject_code']); ?>', '<?php echo $subject['course']; ?>', '<?php echo htmlspecialchars($subject['year_level']); ?>', '<?php echo htmlspecialchars($subject['instructor_name']); ?>', <?php echo $subject['hour']; ?>)" title="Edit Subject">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="deleteSubject.php?id=<?php echo $subject['id']; ?>" class="btn-reject" onclick="return confirm('Are you sure you want to delete this subject? This action cannot be undone.')" title="Delete Subject" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none; min-width: 36px; height: 36px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 8px 10px;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Subject Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Subject & Instructor</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="post" action="updateSubject.php" class="modal-form" id="editForm">
                <input type="hidden" name="subject_id" id="editSubjectId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="editSubjectName"><i class="fas fa-book"></i> Subject Name</label>
                        <input type="text" id="editSubjectName" name="subject_name" required>
                    </div>
                    <div class="form-group">
                        <label for="editSubjectCode"><i class="fas fa-code"></i> Subject Code</label>
                        <input type="text" id="editSubjectCode" name="subject_code" required>
                    </div>
                    <div class="form-group">
                        <label for="editCourse"><i class="fas fa-graduation-cap"></i> Course</label>
                        <select id="editCourse" name="course" required>
                            <option value="">Select Course</option>
                            <option value="BS Computer Science">BS Computer Science</option>
                            <option value="BS Information Technology">BS Information Technology</option>
                            <option value="BS Computer Engineering">BS Computer Engineering</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editYearLevel"><i class="fas fa-users"></i> Year Level</label>
                        <select id="editYearLevel" name="year_level" required>
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editInstructorName"><i class="fas fa-user"></i> Instructor Name</label>
                        <input type="text" id="editInstructorName" name="instructor_name" required>
                    </div>
                    <div class="form-group">
                        <label for="editHours"><i class="fas fa-clock"></i> Subject Hours</label>
                        <input type="number" id="editHours" name="hour" min="1" max="10" required>
                    </div>
                    
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeEditModal()" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save"></i> Update Subject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Appointment Modal -->
    <div id="editAppointmentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Appointment</h2>
                <span class="close" onclick="closeEditAppointmentModal()">&times;</span>
            </div>
            <form method="post" action="update_appointment.php?action=update" class="modal-form" id="editAppointmentForm">
                <input type="hidden" name="appointment_id" id="editAppointmentId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="editAppointmentDate"><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" id="editAppointmentDate" name="appointment_date" required>
                    </div>
                    <div class="form-group">
                        <label for="editStartTime"><i class="fas fa-clock"></i> Start Time</label>
                        <input type="time" id="editStartTime" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="editEndTime"><i class="fas fa-clock"></i> End Time</label>
                        <input type="time" id="editEndTime" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="editTotalSlots"><i class="fas fa-users"></i> Total Slots</label>
                        <input type="number" id="editTotalSlots" name="total_slots" min="1" max="50" required>
                    </div>
                    <div class="form-group">
                        <label for="editAvailableSlots"><i class="fas fa-check-circle"></i> Available Slots</label>
                        <input type="number" id="editAvailableSlots" name="available_slots" min="0" max="50" required>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeEditAppointmentModal()" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-update">
                        <i class="fas fa-save"></i> Update Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    </div>
        </div>
    </section>
</body>
</html>

<script>
    // Status filter functionality - defined globally
    function filterStudents() {
        try {
            const filterSelect = document.getElementById('statusFilter');
            if (!filterSelect) {
                return;
            }
            
            const filterValue = filterSelect.value;
            const table = document.querySelector('.compact-table tbody');
            if (!table) {
                return;
            }
            
            const rows = table.querySelectorAll('tr');
            const filterStats = document.getElementById('filterStats');
            
            let visibleCount = 0;
            let totalCount = 0;
            
            rows.forEach((row) => {
                // Skip if it's the "no data" row
                if (row.querySelector('.no-data')) {
                    return;
                }
                
                totalCount++;
                
                // Get the status from data attribute
                const statusValue = row.getAttribute('data-status');
                
                if (statusValue) {
                    let shouldShow = false;
                    if (filterValue === 'ALL') {
                        shouldShow = true;
                    } else {
                        // Use exact match for data attribute
                        shouldShow = statusValue === filterValue;
                    }
                    
                    if (shouldShow) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            // Update filter stats
            let statsText = '';
            if (filterValue === 'ALL') {
                statsText = `<i class="fas fa-info-circle" style="margin-right: 5px;"></i>Showing all student enrollment requests (${visibleCount} total)`;
            } else {
                statsText = `<i class="fas fa-filter" style="margin-right: 5px;"></i>Showing ${filterValue.toLowerCase()} students only (${visibleCount} of ${totalCount} total)`;
            }
            
            if (filterStats) {
                filterStats.innerHTML = statsText;
            }
        } catch (error) {
            console.error('Error in filterStudents:', error);
        }
    }
    
    function clearFilter() {
        try {
            const filterSelect = document.getElementById('statusFilter');
            if (filterSelect) {
                filterSelect.value = 'ALL';
                filterStudents();
            }
        } catch (error) {
            console.error('Error in clearFilter:', error);
        }
    }

    // Wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced button functionality
        const buttons = document.querySelectorAll('button');
        buttons.forEach(button => {
            // Add hover effects
            button.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 20px rgba(0,0,0,0.2)';
                }
            });
            
            button.addEventListener('mouseleave', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                }
            });
            
            button.addEventListener('mousedown', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0)';
                }
            });
            
            // Form submission loading state (excluding delete forms)
            const form = button.closest('form');
            if (form && !form.hasAttribute('data-delete-form')) {
                form.addEventListener('submit', function(e) {
                    // Don't interfere with delete forms
                    if (button.name === 'delete' || button.name === 'delete_subject') {
                        return;
                    }
                    
                    button.disabled = true;
                    button.style.opacity = '0.7';
                    button.style.cursor = 'wait';
                    
                    // Re-enable after 2 seconds if form doesn't redirect
                    setTimeout(() => {
                        button.disabled = false;
                        button.style.opacity = '1';
                        button.style.cursor = 'pointer';
                    }, 2000);
                });
            }
        });
        
        // Icon fallback system
        const checkIcons = () => {
            const icons = document.querySelectorAll('i[class*="uil"]');
            let fallbackCount = 0;
            
            icons.forEach(icon => {
                // Check if icon is properly loaded
                const computedStyle = window.getComputedStyle(icon);
                if (computedStyle.fontFamily.includes('serif') || computedStyle.fontFamily.includes('Times')) {
                    // Iconscout failed to load, try Font Awesome fallback
                    const originalClass = icon.className;
                    
                    // Simple icon replacements
                    const iconMap = {
                        'uil-calendar': 'fa-calendar',
                        'uil-clock': 'fa-clock',
                        'uil-clock-eight': 'fa-clock',
                        'uil-layer-group': 'fa-users',
                        'uil-check-circle': 'fa-check-circle',
                        'uil-trash': 'fa-trash',
                        'uil-user': 'fa-user',
                        'uil-calendar-alt': 'fa-calendar-alt',
                        'uil-files-landscapes': 'fa-file-alt',
                        'uil-chart': 'fa-chart-bar',
                        'uil-comments': 'fa-comments',
                        'uil-signout': 'fa-sign-out-alt',
                        'uil-plus-circle': 'fa-plus-circle',
                        'uil-plus': 'fa-plus',
                        'uil-user-check': 'fa-user-check',
                        'uil-at': 'fa-at',
                        'uil-graduation-cap': 'fa-graduation-cap',
                        'uil-cog': 'fa-cog',
                        'uil-calendar-times': 'fa-calendar-times',
                        'uil-user-times': 'fa-user-times',
                        'uil-check': 'fa-check',
                        'uil-times': 'fa-times',
                        'uil-eye': 'fa-eye',
                        'uil-question': 'fa-question',
                        'uil-estate': 'fa-home'
                    };
                    
                    // Replace icon class
                    for (const [uilIcon, faIcon] of Object.entries(iconMap)) {
                        if (originalClass.includes(uilIcon)) {
                            icon.className = originalClass.replace(uilIcon, faIcon);
                            icon.style.fontFamily = 'Font Awesome 6 Free';
                            icon.style.fontWeight = '900';
                            fallbackCount++;
                            break;
                        }
                    }
                }
            });
            
            console.log(`Applied ${fallbackCount} icon fallbacks`);
        };
        
        // Check icons after page load
        setTimeout(checkIcons, 1000);
        setTimeout(checkIcons, 3000); // Double check after CDN loads
        
        // Original sidebar functionality
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
        
        // Force style application for all action buttons
        const actionButtons = document.querySelectorAll('.btn-approve, .btn-reject, .btn-view');
        actionButtons.forEach(button => {
            // Ensure inline styles are applied
            const computedStyle = window.getComputedStyle(button);
            if (computedStyle.backgroundColor === 'rgba(0, 0, 0, 0)' || computedStyle.backgroundColor === 'transparent') {
                // Re-apply styles if not showing
                if (button.classList.contains('btn-approve')) {
                    button.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                    button.style.color = 'white';
                    button.style.border = 'none';
                } else if (button.classList.contains('btn-reject')) {
                    button.style.background = 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)';
                    button.style.color = 'white';
                    button.style.border = 'none';
                } else if (button.classList.contains('btn-view')) {
                    button.style.background = 'linear-gradient(135deg, #17a2b8 0%, #00bcd4 100%)';
                    button.style.color = 'white';
                    button.style.border = 'none';
                }
            }
        });

        // Special handling for delete buttons
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                console.log('Delete button clicked:', this.name, this.value);
                
                // Add visual feedback
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Special handling for delete forms
        const deleteForms = document.querySelectorAll('form[data-delete-form]');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                console.log('Delete form submitted:', this.getAttribute('data-delete-form'));
                
                // Don't prevent default, let the form submit normally
                // But add some debugging
                const formData = new FormData(this);
                console.log('Form data:', Object.fromEntries(formData));
            });
        });
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

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
}

th {
    background-color: #f2f2f2;
}

/* Compact Table Styles */
.table-container {
    overflow-x: auto;
    margin-bottom: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.compact-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.compact-table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 8px;
    font-weight: 600;
    text-align: center;
    font-size: 0.85rem;
    border: none;
}

.compact-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
    font-size: 0.85rem;
}

.compact-table tr:hover {
    background-color: #f8f9fa;
}

.compact-table tr:last-child td {
    border-bottom: none;
}

.cell-text {
    font-weight: 500;
    color: #333;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 6px;
    justify-content: center;
    align-items: center;
}

.action-buttons button {
    border: none;
    cursor: pointer;
    padding: 8px 10px;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    position: relative;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    font-weight: 600;
}

.action-buttons button i {
    font-size: 16px;
    margin: 0;
}

.action-buttons button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}



.btn-approve {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: 1px solid #28a745;
}

.btn-approve:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    border-color: #1e7e34;
    color: white;
}


.btn-reject {
    background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
    color: white;
    border: 1px solid #dc3545;
}

.btn-reject:hover {
    background: linear-gradient(135deg, #c82333 0%, #c0392b 100%);
    border-color: #c82333;
    color: white;
}


.btn-view {
    background: linear-gradient(135deg, #17a2b8 0%, #00bcd4 100%);
    color: white;
    border: 1px solid #17a2b8;
}

.btn-view:hover {
    background: linear-gradient(135deg, #138496 0%, #0097a7 100%);
    border-color: #138496;
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

/* Edit Button */
.btn-edit {
    background: linear-gradient(135deg, #17a2b8 0%, #00bcd4 100%);
    color: white;
    border: 1px solid #17a2b8;
}

.btn-edit:hover {
    background: linear-gradient(135deg, #138496 0%, #0097a7 100%);
    border-color: #138496;
    color: white;
}

.btn-edit:active {
    background: linear-gradient(135deg, #117a8b 0%, #00838f 100%);
    transform: translateY(1px);
}

/* Button loading state */
.action-buttons button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* No Data State */
.no-data {
    text-align: center !important;
    color: #6c757d !important;
    font-style: italic;
    padding: 30px 20px !important;
    background: #f8f9fa;
}

/* Form Styles */
.form-container {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.appointment-form {
    width: 100%;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-group label i {
    color: #6c757d;
    font-size: 0.85rem;
}

.form-group input {
    padding: 10px 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input:hover {
    border-color: #ced4da;
}

.form-actions {
    display: flex;
    align-items: end;
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-submit:active {
    transform: translateY(0);
}

/* Form responsiveness */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-container {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .form-group input {
        padding: 8px 10px;
    }
    
    .btn-submit {
        width: 100%;
        justify-content: center;
        padding: 14px 24px;
    }
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .compact-table {
        font-size: 0.8rem;
    }
    
    .compact-table th,
    .compact-table td {
        padding: 8px 6px;
    }
}

@media (max-width: 768px) {
    .compact-table {
        font-size: 0.75rem;
    }
    
    .compact-table th,
    .compact-table td {
        padding: 6px 4px;
    }
    
    .action-buttons button {
        padding: 4px 6px;
        min-width: 28px;
        height: 28px;
        font-size: 0.7rem;
    }
}
.data-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px; /* Adjust spacing as needed */
}

.data {
    flex: 1; /* Allows each data item to take equal space */
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Aligns titles and lists to the start */
}

.data-title {
    font-weight: bold; /* Makes the title stand out */
}

.data-list {
    margin-top: 5px; /* Adds space between title and list */
}

/* Responsive Design */
@media (max-width: 1000px) {
    nav.close {
        width: 250px;
    }
    
    nav.close .logo_name {
        opacity: 1;
        pointer-events: auto;
    }
    
    nav.close li a .link-name {
        opacity: 1;
        pointer-events: auto;
    }
    
    nav.close ~ .dashboard {
        left: 250px;
        width: calc(100% - 250px);
    }
    
    nav.close ~ .dashboard .top {
        left: 250px;
        width: calc(100% - 250px);
    }
}

@media (max-width: 780px) {
    .dash-content .boxes .box {
        width: calc(100% / 2 - 15px);
        margin-top: 15px;
    }
}

@media (max-width: 560px) {
    .dash-content .boxes .box {
        width: 100%;
    }
}

@media (max-width: 400px) {
    nav.close {
        width: 73px;
    }
    
    nav.close ~ .dashboard {
        left: 73px;
        width: calc(100% - 73px);
    }
    
    nav.close ~ .dashboard .top {
        left: 73px;
        width: calc(100% - 73px);
    }
}

/* Global Message Styles */
@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-100%);
    }
}

.global-message {
    animation: slideDown 0.8s ease-out;
    position: relative;
    z-index: 1000;
    margin-top: 80px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-left: 4px solid;
}

.global-message.message {
    border-left-color: #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
    color: #155724;
    padding: 15px 20px;
    border-radius: 8px;
    border: 1px solid rgba(40, 167, 69, 0.2);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
    position: relative;
    overflow: hidden;
}

.global-message.message::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #28a745, #20c997, #28a745);
    background-size: 200% 100%;
    animation: shimmer 2s ease-in-out infinite;
}

.global-message.message::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 60px;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: slide 3s ease-in-out infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

@keyframes slide {
    0% { transform: translateX(-100%); }
    50% { transform: translateX(100%); }
    100% { transform: translateX(100%); }
}

.global-message.error-message {
    border-left-color: #f44336;
    background: rgba(244, 67, 54, 0.1);
}

.messages-container {
    position: relative;
    z-index: 1000;
}

/* Responsive message positioning */
@media (max-width: 768px) {
    .global-message {
        margin-top: 70px;
        margin-left: 10px;
        margin-right: 10px;
    }
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    transform: scale(0.8);
    opacity: 0;
    transition: all 0.3s ease;
    overflow: hidden;
}

.modal-content.show {
    transform: scale(1);
    opacity: 1;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
}

.modal-header h2 i {
    margin-right: 10px;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: white;
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.close:hover {
    opacity: 1;
}

.modal-form {
    padding: 25px;
}

.modal-form .form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-update {
    background: linear-gradient(135deg, #17a2b8 0%, #00bcd4 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
}

.btn-update:hover {
    background: linear-gradient(135deg, #138496 0%, #0097a7 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(23, 162, 184, 0.4);
}

.btn-update:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Responsive Modal */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 2% auto;
    }
    
    .modal-form {
        padding: 20px;
    }
    
    .modal-form .form-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .modal-actions {
        flex-direction: column-reverse;
    }
    
    .btn-cancel, .btn-update {
        width: 100%;
        justify-content: center;
    }
}

</style>

<script>
// Auto-hide global messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messageDivs = document.querySelectorAll('.global-message');
    
    messageDivs.forEach(messageDiv => {
        const isError = messageDiv.classList.contains('error-message');
        const timeout = isError ? 7000 : 5000; // Keep errors longer
        
        setTimeout(() => {
            messageDiv.style.animation = 'fadeOut 0.5s ease-in';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 500);
        }, timeout);
    });
});

// Edit Subject Modal Functions
function openEditSubjectModal(id, name, code, course, year, instructor, hours) {
    document.getElementById('editSubjectId').value = id;
    document.getElementById('editSubjectName').value = name;
    document.getElementById('editSubjectCode').value = code;
    document.getElementById('editCourse').value = course;
    document.getElementById('editYearLevel').value = year;
    document.getElementById('editInstructorName').value = instructor;
    document.getElementById('editHours').value = hours;
    
    const modal = document.getElementById('editModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Add animation
    setTimeout(() => {
        modalContent.classList.add('show');
    }, 10);
}

function openEditModal(id, name, code, course, year, instructor, hours) {
    // Alias for backward compatibility
    openEditSubjectModal(id, name, code, course, year, instructor, hours);
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }, 300);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeEditModal();
    }
}

// Enhanced form validation
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-update');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds if form doesn't submit
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Subject';
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }
});

// Appointment Modal Functions
function openEditAppointmentModal(id, date, startTime, endTime, totalSlots, availableSlots) {
    // Populate the modal fields
    document.getElementById('editAppointmentId').value = id;
    document.getElementById('editAppointmentDate').value = date;
    document.getElementById('editStartTime').value = startTime;
    document.getElementById('editEndTime').value = endTime;
    document.getElementById('editTotalSlots').value = totalSlots;
    document.getElementById('editAvailableSlots').value = availableSlots;
    
    // Update available slots max based on total slots
    document.getElementById('editAvailableSlots').max = totalSlots;
    
    const modal = document.getElementById('editAppointmentModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Add animation
    setTimeout(() => {
        modalContent.classList.add('show');
    }, 10);
}

function closeEditAppointmentModal() {
    const modal = document.getElementById('editAppointmentModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }, 300);
}

// Close appointment modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editAppointmentModal');
    const subjectModal = document.getElementById('editModal');
    
    if (event.target == modal) {
        closeEditAppointmentModal();
    }
    if (event.target == subjectModal) {
        closeEditModal();
    }
}

// Enhanced form validation for appointment form
document.addEventListener('DOMContentLoaded', function() {
    const appointmentForm = document.getElementById('editAppointmentForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-update');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds if form doesn't submit
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Appointment';
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
        
        // Update available slots max when total slots changes
        const totalSlotsInput = document.getElementById('editTotalSlots');
        const availableSlotsInput = document.getElementById('editAvailableSlots');
        
        if (totalSlotsInput && availableSlotsInput) {
            totalSlotsInput.addEventListener('change', function() {
                availableSlotsInput.max = this.value;
                if (parseInt(availableSlotsInput.value) > parseInt(this.value)) {
                    availableSlotsInput.value = this.value;
                }
            });
        }
    }
});
</script>
