<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all students (excluding admin accounts)
$students = [];
$sql = "SELECT id, username, first_name, last_name, email, image, college_course, college_year, status, created_at 
        FROM students 
        WHERE username LIKE '%@student' 
        ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
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
    <title>Student Status Management</title>
</head>
<body>
    <!-- Navigation Sidebar -->
    <nav>
        <div class="logo-name">
                      <span class="logo_name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>

        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="link-name">Appointments</span>
                </a></li>   
                <li><a href="student_status.php" >
                    <i class="fas fa-user-check"></i>
                    <span class="link-name">Student Status</span>
                </a></li>
                <li><a href="grades.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-name">Grades</span>
                </a></li>
                <li><a href="master_list.php" class="active">
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
                <span class="text">Student Status Management</span>
            </div>
                <!-- Search and Filter Section -->
                <div class="filter-container" style="margin-bottom: 20px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #e9ecef;">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <label for="searchInput" style="font-weight: 600; color: #495057; margin-right: 10px;">
                            <i class="fas fa-search" style="margin-right: 5px;"></i>Search Students:
                        </label>
                        <input type="text" id="searchInput" placeholder="Search by name or username..." style="padding: 10px 15px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; width: 250px; transition: all 0.3s ease;" onkeyup="filterTable()">
                        
                        <label for="courseFilter" style="font-weight: 600; color: #495057; margin-left: 15px;">
                            <i class="fas fa-graduation-cap" style="margin-right: 5px;"></i>Course:
                        </label>
                        <select id="courseFilter" style="padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; background: white; cursor: pointer; transition: all 0.3s ease;" onchange="filterTable()">
                            <option value="">All Courses</option>
                            <option value="BS Computer Science">BS Computer Science</option>
                            <option value="BS Information Technology">BS Information Technology</option>
                            <option value="BS Computer Engineering">BS Computer Engineering</option>
                        </select>
                        <label for="yearFilter" style="font-weight: 600; color: #495057; margin-left: 15px;">
                            <i class="fas fa-users" style="margin-right: 5px;"></i>Year:
                        </label>
                        <select id="yearFilter" style="padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; background: white; cursor: pointer; transition: all 0.3s ease;" onchange="filterTable()">
                            <option value="">All Years</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                        
                        <button id="clearFilter" onclick="clearFilters()" style="padding: 10px 20px; background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; margin-left: auto;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(108, 117, 125, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(108, 117, 125, 0.3)'">
                            <i class="fas fa-times" style="font-size: 14px;"></i> Clear Filters
                        </button>
                    </div>
                    <div id="filterStats" style="margin-top: 15px; font-size: 0.85rem; color: #6c757d;">
                        <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
                        Showing all student records
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="student-table" id="studentTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-image"></i> Image</th>
                                <th><i class="fas fa-user"></i> Name</th>
                                <th><i class="fas fa-at"></i> Username</th>
                                <th><i class="fas fa-graduation-cap"></i> Course</th>
                                <th><i class="fas fa-users"></i> Year Level</th>
                                <th><i class="fas fa-check-circle"></i> Status</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="no-data" style="text-align: center; color: #6c757d; font-style: italic; padding: 50px 20px;">
                                        <i class="fas fa-users" style="font-size: 3rem; color: #dee2e6; margin-bottom: 1rem;"></i>
                                        <p>No student records found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr data-name="<?php echo strtolower($student['first_name'] . ' ' . $student['last_name']); ?>" 
                                        data-username="<?php echo strtolower($student['username']); ?>"
                                        data-course="<?php echo htmlspecialchars($student['college_course']); ?>"
                                        data-year="<?php echo htmlspecialchars($student['college_year']); ?>">
                                        <td style="text-align: center;">
                                            <?php if (!empty($student['image']) && file_exists('../' . $student['image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($student['image']); ?>" 
                                                     alt="Profile" 
                                                     style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #667eea;">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                    <i class="fas fa-user" style="color: white; font-size: 20px;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #333;">
                                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #6c757d;">
                                                <?php echo htmlspecialchars($student['email']); ?>
                                            </div>
                                        </td>
                                        <td style="font-family: monospace; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($student['username']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($student['college_course']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($student['college_year']); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="status-badge status-<?php echo strtolower($student['status']); ?>">
                                                <?php echo htmlspecialchars($student['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-view" title="View Details" onclick="window.location.href='view_student.php?id=<?php echo $student['id']; ?>'">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                            
                                                <button class="btn-delete" title="Delete" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

    .status-approved {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
        border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-rejected {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }

    /* Enhanced filter container styles */
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

    /* Navigation styles - Updated to match student_status.php */
    nav {
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%) !important;
        border-right: none !important;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
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
        color: #fff !important;
    }

    nav .logo-name .logo_name {
        font-size: 18px;
        font-weight: 600;
        color: #fff !important;
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

    nav .menu-items::-webkit-scrollbar {
        display: none;
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
        color: rgba(255, 255, 255, 0.8) !important;
        transition: color 0.3s ease;
    }

    .nav-links li a:hover i,
    .nav-links li a.active i {
        color: #fff !important;
    }

    .nav-links li a .link-name {
        font-size: 15px;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9) !important;
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

    /* Override original admin dashboard styles */
    .nav-links li a .link-name {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    .nav-links li a i {
        color: rgba(255, 255, 255, 0.8) !important;
    }
    
    nav .logo-image i {
        color: #fff !important;
    }
    
    nav .logo-name .logo_name {
        color: #fff !important;
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
    
    /* Force white sidebar colors - Override all conflicting styles */
    nav,
    nav.close {
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%) !important;
        background-color: transparent !important;
    }
    
    nav .logo-image i,
    nav.close .logo-image i {
        color: #fff !important;
    }
    
    nav .logo-name .logo_name,
    nav.close .logo-name .logo_name {
        color: #fff !important;
    }
    
    .menu-items li a i,
    nav .menu-items li a i,
    .nav-links li a i {
        color: rgba(255, 255, 255, 0.8) !important;
    }
    
    .menu-items li a .link-name,
    nav .menu-items li a .link-name,
    .nav-links li a .link-name {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    .menu-items li a:hover i,
    .nav-links li a:hover i {
        color: #fff !important;
    }
    
    .menu-items li a:hover .link-name,
    .nav-links li a:hover .link-name {
        color: #fff !important;
    }
    nav{
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 250px;
        padding: 10px 14px;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%) !important;
        background-color: transparent !important;
        border-right: none !important;
        transition: all 0.4s ease;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
    }
    nav.close{
        width: 73px;
    }
    nav .logo-name{
        display: flex;
        align-items: center;
    }
    nav .logo-image{
        display: flex;
        justify-content: center;
        min-width: 45px;
    }
    nav .logo-image i{
        font-size: 40px;
        color: var(--text-color);
    }
nav .logo-name .logo_name{
        font-size: 18px;
        font-weight: 600;
        color: var(--text-color);
        margin-left: 14px;
        transition: var(--tran-05);
    }
    nav.close .logo_name{
        opacity: 0;
        pointer-events: none;
    }
    nav .menu-items{
        margin-top: 40px;
        height: calc(100% - 90px);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .menu-items li{
        list-style: none;
    }
    .menu-items li a{
        display: flex;
        align-items: center;
        height: 50px;
        text-decoration: none;
        position: relative;
    }
    .nav-links li a:hover:before{
        content: "";
        position: absolute;
        left: -7px;
        height: 5px;
        width: 5px;
        border-radius: 50%;
        background-color: var(--primary-color);
    }
    body.dark li a:hover:before{
        background-color: var(--text-color);
    }
.menu-items li a i{
        font-size: 20px;
        min-width: 45px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--black-light-color);
    }
.menu-items li a .link-name{
        font-size: 15px;
        font-weight: 400;
        color: var(--black-light-color);    
        transition: var(--tran-05);
    }
    nav.close li a .link-name{
        opacity: 0;
        pointer-events: none;
    }
    .nav-links li a:hover i,
    .nav-links li a:hover .link-name{
        color: var(--primary-color);
    }
    body.dark .nav-links li a:hover i,
    body.dark .nav-links li a:hover .link-name{
        color: var(--text-color);
    }
    .menu-items .logout-mode{
        padding-top: 10px;
        border-top: 1px solid var(--border-color);
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

    /* Sidebar Toggle Button */
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
        position: fixed;
        top: 0;
        left: 250px;
        display: flex;
        width: calc(100% - 250px);
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: #f5f7fa;
        transition: all 0.4s ease;
        z-index: 10;
    }

    nav.close ~ .dashboard .top {
        left: 73px;
        width: calc(100% - 73px);
    }

    /* Title Section */
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
        color: #2d3748;
        margin-left: 12px;
    }

    /* Body Background */
    body {
        background: #f5f7fa;
        min-height: 100vh;
    }

</style>

