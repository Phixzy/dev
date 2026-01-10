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
$student_sql = "SELECT id, username, first_name, last_name, email, image, college_course, college_year, status 
                FROM students WHERE id = ?";
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

$student_username = $_SESSION['username'];

// Fetch grades for this student
$student_grades = [];
$grades_sql = "SELECT * FROM grades WHERE student_username = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($grades_sql);
$stmt->bind_param("s", $student_username);
$stmt->execute();
$grades_result = $stmt->get_result();

if ($grades_result && $grades_result->num_rows > 0) {
    while ($row = $grades_result->fetch_assoc()) {
        $student_grades[] = $row;
    }
}
$stmt->close();

// Calculate overall statistics
$total_subjects = count($student_grades);
$passed_count = 0;
$failed_count = 0;
$total_average = 0;

foreach ($student_grades as $grade) {
    if ($grade['status'] === 'Passed') {
        $passed_count++;
    } elseif ($grade['status'] === 'Failed') {
        $failed_count++;
    }
    $total_average += floatval($grade['average']);
}

$overall_average = $total_subjects > 0 ? round($total_average / $total_subjects, 2) : 0;

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
    <title>Student Page</title>
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
                
                <li><a href="student_grades.php" class="active">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-name">Grades</span>
                </a></li>
           
                <li><a href="student_emails.php">
                    <i class="fas fa-comments"></i>
                    <span class="link-name">Emails</span>
                </a></li>
                <li><a href="contact.php">
                    <i class="fas fa-phone"></i>
                    <span class="link-name">Contact</span>
                </a></li>
                <li><a href="student_profile.php">
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
        
        <!-- Success/Error Messages - Centered -->
        <?php
        if (isset($_SESSION['message'])) {
            echo '<div class="alert-message success" style="margin: 90px auto 20px auto; max-width: 600px; padding: 16px 24px; background-color: #d4edda; border: 1px solid #c3e6cb; border-left: 5px solid #28a745; border-radius: 8px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2); animation: slideIn 0.4s ease-out; position: relative; z-index: 5;">';
            echo '<i class="fas fa-check-circle" style="color: #28a745; font-size: 24px; flex-shrink: 0;"></i>';
            echo '<span style="color: #155724; font-weight: 500; font-size: 15px;">' . htmlspecialchars($_SESSION['message']) . '</span>';
            unset($_SESSION['message']);
            echo '</div>';
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert-message error" style="margin: 90px auto 20px auto; max-width: 600px; padding: 16px 24px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-left: 5px solid #dc3545; border-radius: 8px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); animation: slideIn 0.4s ease-out; position: relative; z-index: 5;">';
            echo '<i class="fas fa-times-circle" style="color: #dc3545; font-size: 24px; flex-shrink: 0;"></i>';
            echo '<span style="color: #721c24; font-weight: 500; font-size: 15px;">' . htmlspecialchars($_SESSION['error']) . '</span>';
            unset($_SESSION['error']);
            echo '</div>';
        }
        ?>
        
        <div class="activity">
            <div class="title">
                <i class="fas fa-user-check"></i>
                <span class="text">Academic Standing</span>
            </div>
               
                <div class="table-container">
                    <table class="student-table" id="studentTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i>Subject Name</th>
                                <th><i class="fas fa-at"></i> Subject Code</th>
                                <th><i class="fas fa-graduation-cap"></i> Semester</th>
                                <th><i class="fas fa-check-circle"></i> Prelim Grade</th>
                                <th><i class="fas fa-check-circle"></i> Midterm Grade</th>
                                <th><i class="fas fa-check-circle"></i> Final Grade</th>
                                <th><i class="fas fa-check-circle"></i> Average</th>
                                <th><i class="fas fa-check-circle"></i> Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($student_grades)): ?>
                                <tr>
                                    <td colspan="9" class="no-data" style="text-align: center; color: #6c757d; font-style: italic; padding: 50px 20px;">
                                        <i class="fas fa-book" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                                        <p>No grades have been posted yet.</p>
                                        <p style="font-size: 0.85rem;">Please check back later.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($student_grades as $grade): ?>
                                    <tr>
                                        <td style="text-align: center;">
                                            <div style="font-weight: 600; color: #333;">
                                                <?php echo htmlspecialchars($grade['subject_code']); ?>
                                            </div>
                                        </td>
                                        <td style="font-family: monospace; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($grade['subject_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($grade['semester']); ?>
                                        </td>
                                        <td style="font-weight: 600;">
                                            <?php echo number_format($grade['prelim_grade'], 2); ?>
                                        </td>
                                        <td style="font-weight: 600;">
                                            <?php echo number_format($grade['midterm_grade'], 2); ?>
                                        </td>
                                        <td style="font-weight: 600;">
                                            <?php echo number_format($grade['final_grade'], 2); ?>
                                        </td>
                                        <td style="font-weight: 700; color: #667eea; font-size: 1.1rem;">
                                            <?php echo number_format($grade['average'], 2); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($grade['status']); ?>">
                                                <?php echo htmlspecialchars($grade['status']); ?>
                                            </span>
                                        </td>
                                        
                                        
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

    // Student management functions - using form submission for reliable redirect
    function approveStudent(id) {
        if (confirm('Are you sure you want to approve this student?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'studentstatus.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'approve';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function rejectStudent(id) {
        if (confirm('Are you sure you want to reject this student?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'studentstatus.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'reject';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteStudent(id) {
        if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'studentstatus.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function viewStudentDetails(id) {
        // Direct redirect to view_student.php
        window.location.href = 'view_student.php?id=' + id;
    }

    // Filter functionality
    function filterTable() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const courseFilter = document.getElementById('courseFilter').value;
        const yearFilter = document.getElementById('yearFilter').value;
        const table = document.getElementById('studentTable');
        const rows = table.querySelectorAll('tbody tr');
        const filterStats = document.getElementById('filterStats');
        
        let visibleCount = 0;
        let totalCount = 0;
        
        rows.forEach((row) => {
            // Skip if it's the "no data" row
            if (row.querySelector('.no-data')) {
                return;
            }
            
            totalCount++;
            
            const name = row.getAttribute('data-name') || '';
            const username = row.getAttribute('data-username') || '';
            const course = row.getAttribute('data-course') || '';
            const year = row.getAttribute('data-year') || '';
            
            let shouldShow = true;
            
            // Search filter
            if (searchInput) {
                shouldShow = shouldShow && (name.includes(searchInput) || username.includes(searchInput));
            }
            
            // Course filter
            if (courseFilter) {
                shouldShow = shouldShow && (course === courseFilter);
            }
            
            // Year filter
            if (yearFilter) {
                shouldShow = shouldShow && (year === yearFilter);
            }
            
            if (shouldShow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update filter stats
        let statsText = '';
        const hasFilters = searchInput || courseFilter || yearFilter;
        
        if (!hasFilters) {
            statsText = `<i class="fas fa-info-circle" style="margin-right: 5px;"></i>Showing all student records (${visibleCount} total)`;
        } else {
            statsText = `<i class="fas fa-filter" style="margin-right: 5px;"></i>Showing ${visibleCount} of ${totalCount} students`;
            if (searchInput) statsText += ` matching "${searchInput}"`;
            if (courseFilter) statsText += ` in ${courseFilter}`;
            if (yearFilter) statsText += ` for ${yearFilter}`;
        }
        
        if (filterStats) {
            filterStats.innerHTML = statsText;
        }
    }
    
    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('courseFilter').value = '';
        document.getElementById('yearFilter').value = '';
        filterTable();
    }

    // Enhanced button functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced button effects
        const buttons = document.querySelectorAll('button');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px)';
                }
            });
            
            button.addEventListener('mouseleave', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0)';
                }
            });
            
            button.addEventListener('mousedown', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });

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

    /* Table Styles */
    .table-container {
        overflow-x: auto;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        background: white;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        background: white;
        border-radius: 12px;
        overflow: hidden;
    }

    .student-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 12px;
        font-weight: 600;
        text-align: center;
        font-size: 0.9rem;
        border: none;
    }

    .student-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .student-table tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        transition: all 0.3s ease;
    }

    .student-table tr:last-child td {
        border-bottom: none;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }

    .action-buttons button {
        border: none;
        cursor: pointer;
        padding: 10px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        position: relative;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        font-weight: 600;
    }

    .action-buttons button i {
        font-size: 16px;
        margin: 0;
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
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    .btn-reject {
        background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
        color: white;
        border: 1px solid #fd7e14;
    }

    .btn-reject:hover {
        background: linear-gradient(135deg, #e67700 0%, #d63384 100%);
        border-color: #e67700;
        color: white;
        box-shadow: 0 4px 12px rgba(253, 126, 20, 0.4);
    }

    .btn-reject:disabled {
        background: #6c757d;
        border-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
        color: white;
        border: 1px solid #dc3545;
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #c82333 0%, #c0392b 100%);
        border-color: #c82333;
        color: white;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
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
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-approved, .status-passed {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-rejected, .status-failed {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }
    
    .status-incomplete {
        background: rgba(23, 162, 184, 0.1);
        color: #17a2b8;
        border: 1px solid rgba(23, 162, 184, 0.3);
    }
    
    .status-dropped {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
        border: 1px solid rgba(108, 117, 125, 0.3);
    }
    
    /* Responsive design */
    .filter-container input:focus,
    .filter-container select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .filter-container input:hover,
    .filter-container select:hover {
        border-color: #ced4da;
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

    /* Responsive design */
    @media (max-width: 1200px) {
        .student-table {
            font-size: 0.8rem;
        }
        
        .student-table th,
        .student-table td {
            padding: 10px 8px;
        }
    }

    @media (max-width: 768px) {
        .student-table {
            font-size: 0.75rem;
        }
        
        .student-table th,
        .student-table td {
            padding: 8px 6px;
        }
        
        .action-buttons button {
            padding: 6px 8px;
            min-width: 32px;
            height: 32px;
            font-size: 0.7rem;
        }
        
        .filter-container div {
            flex-direction: column;
            align-items: stretch !important;
        }
        
        .filter-container input,
        .filter-container select {
            width: 100% !important;
            margin: 5px 0 !important;
        }
        
        #clearFilter {
            margin: 15px 0 0 0 !important;
            justify-content: center !important;
        }
    }

    /* Original admin dashboard styles */
    :root{
        --primary-color: #0E4BF1;
        --panel-color: #FFF;
        --text-color: #000;
        --black-light-color: #707070;
        --border-color: #e6e5e5;
        --toggle-color: #DDD;
        --box1-color: #4DA3FF;
        --box2-color: #FFE6AC;
        --box3-color: #E7D1FC;
        --title-icon-color: #fff;
        
        --tran-05: all 0.5s ease;
        --tran-03: all 0.3s ease;
    }
    
    body{
        min-height: 100vh;
        background-color: var(--panel-color); /* Changed from blue to white */
    }
    
    body.dark{
        --primary-color: #3A3B3C;
        --panel-color: #242526;
        --text-color: #CCC;
        --black-light-color: #CCC;
        --border-color: #4D4C4C;
        --toggle-color: #FFF;
        --box1-color: #3A3B3C;
        --box2-color: #3A3B3C;
        --box3-color: #3A3B3C;
        --title-icon-color: #CCC;
    }
    /* === Custom Scroll Bar CSS === */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    ::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #0b3cc1;
    }
    body.dark::-webkit-scrollbar-thumb:hover,
    body.dark .activity-data::-webkit-scrollbar-thumb:hover{
        background: #3A3B3C;
    }
    nav{
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 250px;
        padding: 10px 14px;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        border-right: none;
        transition: var(--tran-05);
        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    }
    nav.close{
        width: 73px;
    }
    nav .logo-name{
        display: flex;
        align-items: center;
        padding: 15px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        margin-bottom: 15px;
    }
    nav .logo-image{
        display: flex;
        justify-content: center;
        align-items: center;
        min-width: 45px;
        border-radius: 12px;
    }
    nav .logo-image i{
        font-size: 28px;
        color: #fff;
    }
    nav .logo-image img{
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    nav .logo-name .logo_name{
        font-size: 18px;
        font-weight: 600;
        color: #fff;
        margin-left: 12px;
        white-space: nowrap;
        transition: opacity 0.3s ease;
    }
    nav.close .logo_name{
        opacity: 0;
        pointer-events: none;
    }
    nav .menu-items{
        margin-top: 0;
        height: calc(100% - 70px);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow-y: auto;
    }
    nav .menu-items::-webkit-scrollbar {
        display: none;
    }
    .menu-items li{
        list-style: none;
        margin: 5px 0;
    }
    .menu-items li a{
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-radius: 10px;
        text-decoration: none;
        position: relative;
        transition: all 0.3s ease;
    }
    .nav-links li a:hover{
        background: rgba(255, 255, 255, 0.15);
        transform: translateX(5px);
    }
    body.dark li a:hover:before{
        background-color: var(--text-color);
    }
    .menu-items li a i{
        font-size: 20px;
        min-width: 25px;
        color: rgba(255, 255, 255, 0.8);
        transition: color 0.3s ease;
    }
    .menu-items li a .link-name{
        font-size: 15px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        white-space: nowrap;
        transition: opacity 0.3s ease;
    }
    nav.close li a .link-name{
        opacity: 0;
        pointer-events: none;
    }
    .nav-links li a:hover i,
    .nav-links li a:hover .link-name{
        color: #fff;
    }
    body.dark .nav-links li a:hover i,
    body.dark .nav-links li a:hover .link-name{
        color: var(--text-color);
    }
    /* Active state for nav links */
    nav .nav-links li a.active{
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    nav .nav-links li a.active i,
    nav .nav-links li a.active .link-name{
        color: #fff;
    }
    .menu-items .logout-mode{
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }
    .menu-items .mode{
        display: flex;
        align-items: center;
        white-space: nowrap;
    }
    .menu-items .mode-toggle{
        position: absolute;
        right: 14px;
        height: 50px;
        min-width: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .mode-toggle .switch{
        position: relative;
        display: inline-block;
        height: 22px;
        width: 40px;
        border-radius: 25px;
        background-color: var(--toggle-color);
    }
    .switch:before{
        content: "";
        position: absolute;
        left: 5px;
        top: 50%;
        transform: translateY(-50%);
        height: 15px;
        width: 15px;
        background-color: var(--panel-color);
        border-radius: 50%;
        transition: var(--tran-03);
    }
    body.dark .switch:before{
        left: 20px;
    }
    .dashboard{
        position: relative;
        left: 250px;
        background-color: var(--panel-color);
        min-height: 100vh;
        width: calc(100% - 250px);
        padding: 10px 14px;
        transition: var(--tran-05);
    }
    nav.close ~ .dashboard{
        left: 73px;
        width: calc(100% - 73px);
    }
    .dashboard .top{
        position: fixed;
        top: 0;
        left: 250px;
        display: flex;
        width: calc(100% - 250px);
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background-color: var(--panel-color);
        transition: var(--tran-05);
        z-index: 10;
    }
    nav.close ~ .dashboard .top{
        left: 73px;
        width: calc(100% - 73px);
    }
    .dashboard .top .sidebar-toggle{
        font-size: 26px;
        color: var(--text-color);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    .dashboard .top .sidebar-toggle:hover{
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .dashboard .top .sidebar-toggle:hover i{
        color: #fff;
    }
    .dashboard .title{
        display: flex;
        align-items: center;
        margin: 80px 0 30px 0;
    }
    .dashboard .title i{
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
    .dashboard .title .text{
        font-size: 24px;
        font-weight: 600;
        color: var(--text-color);
        margin-left: 12px;
    }

    /* Responsive adjustments */
    @media (max-width: 1000px) {
        nav{
            width: 73px;
        }
        nav.close{
            width: 250px;
        }
        nav .logo_name{
            opacity: 0;
            pointer-events: none;
        }
        nav.close .logo_name{
            opacity: 1;
            pointer-events: auto;
        }
        nav li a .link-name{
            opacity: 0;
            pointer-events: none;
        }
        nav.close li a .link-name{
            opacity: 1;
            pointer-events: auto;
        }
        nav ~ .dashboard{
            left: 73px;
            width: calc(100% - 73px);
        }
        nav.close ~ .dashboard{
            left: 250px;
            width: calc(100% - 250px);
        }
        nav ~ .dashboard .top{
            left: 73px;
            width: calc(100% - 73px);
        }
        nav.close ~ .dashboard .top{
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

    /* Alert Message Styles - Fixed to ensure green background for success */
    .alert-message.success {
        background-color: #d4edda !important;
        border-color: #c3e6cb !important;
        border-left-color: #28a745 !important;
    }
    
    .alert-message.success span,
    .alert-message.success i {
        color: #155724 !important;
    }
    
    /* Alert Message Animations */
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

</style>

