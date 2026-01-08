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



// Fetch all grades for display
$all_grades = [];
$grades_sql = "SELECT g.*, s.first_name, s.last_name, s.email 
               FROM grades g 
               LEFT JOIN students s ON g.student_username = s.username 
               ORDER BY g.created_at DESC";
$grades_result = $conn->query($grades_sql);

if ($grades_result && $grades_result->num_rows > 0) {
    while ($row = $grades_result->fetch_assoc()) {
        $all_grades[] = $row;
    }
}

// Fetch subjects for autocomplete
$subjects = [];
$subjects_sql = "SELECT subject_code, subject_name, course, year_level FROM subjects ORDER BY subject_name";
$subjects_result = $conn->query($subjects_sql);
if ($subjects_result && $subjects_result->num_rows > 0) {
    while ($row = $subjects_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Fetch students for autocomplete
$students = [];
$students_sql = "SELECT username, first_name, last_name FROM students WHERE username LIKE '%@student' ORDER BY username";
$students_result = $conn->query($students_sql);
if ($students_result && $students_result->num_rows > 0) {
    while ($row = $students_result->fetch_assoc()) {
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
    <title>Admin Dashboard - Grades</title>
</head>
<body>
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
                <li><a href="student_status.php">
                    <i class="fas fa-user-check"></i>
                    <span class="link-name">Student Status</span>
                </a></li>
                <li><a href="grades.php" class="active">
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
            <!-- Display messages with proper positioning -->
            <div class="messages-container" style="position: relative; z-index: 100; margin-top: 80px; margin-bottom: 20px;">
              <?php
              if (isset($_SESSION['message'])) {
                  echo '<div class="global-message message" style="padding: 16px 24px; margin: 0 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; border-left: 5px solid #28a745;">';
                  echo '<i class="fas fa-check-circle" style="color: #28a745; font-size: 24px;"></i>';
                  echo '<span style="color: #155724; font-weight: 500;">' . htmlspecialchars($_SESSION['message']) . '</span>';
                  echo '</div>';
                  unset($_SESSION['message']);
              }
              if (isset($_SESSION['error'])) {
                  echo '<div class="global-message error-message" style="padding: 16px 24px; margin: 0 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; border-left: 5px solid #dc3545;">';
                  echo '<i class="fas fa-times-circle" style="color: #dc3545; font-size: 24px;"></i>';
                  echo '<span style="color: #721c24; font-weight: 500;">' . htmlspecialchars($_SESSION['error']) . '</span>';
                  echo '</div>';
                  unset($_SESSION['error']);
              }
              ?>
            </div>
         
            
            <div class="activity">
                <div class="title">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="text">Student Grade Management</span>
                </div>
                <div class="table-container">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Student</th>
                                <th><i class="fas fa-book"></i> Subject</th>
                                <th><i class="fas fa-layer-group"></i> Year Level</th>
                                <th><i class="fas fa-clock"></i> Semester</th>    
                                <th><i class="fas fa-star"></i> Prelim</th>
                                <th><i class="fas fa-star"></i> Midterm</th>
                                <th><i class="fas fa-star"></i> Final</th>
                                <th><i class="fas fa-graduation-cap"></i> Avg</th>
                                <th><i class="fas fa-check-circle"></i> Status</th>
                                <th><i class="fas fa-comment"></i> Remarks</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_grades)): ?>
                                <tr>
                                    <td colspan="11" class="no-data">
                                        <i class="fas fa-clipboard-list" style="font-size: 2.5rem; color: #dee2e6; margin-bottom: 10px;"></i>
                                        <p>No grades have been added yet.</p>
                                        <p style="font-size: 0.85rem; color: #6c757d;">Use the form below to add student grades.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_grades as $grade): ?>
                                    <tr data-id="<?php echo $grade['id']; ?>">
                                        <td>
                                            <div style="font-weight: 600;">
                                                <?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6c757d; font-family: monospace;">
                                                <?php echo htmlspecialchars($grade['student_username']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($grade['subject_code']); ?></div>
                                            <div style="font-size: 0.75rem; color: #6c757d;"><?php echo htmlspecialchars($grade['subject_name']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade['year_level']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                                        <td><?php echo number_format($grade['prelim_grade'], 2); ?></td>
                                        <td><?php echo number_format($grade['midterm_grade'], 2); ?></td>
                                        <td><?php echo number_format($grade['final_grade'], 2); ?></td>
                                        <td style="font-weight: 600; color: #667eea;"><?php echo number_format($grade['average'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($grade['status']); ?>">
                                                <?php echo htmlspecialchars($grade['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade['remarks'] ?? '-'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-edit" title="Edit Grade" onclick="editGrade(<?php echo $grade['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-delete" title="Delete Grade" onclick="deleteGrade(<?php echo $grade['id']; ?>, '<?php echo htmlspecialchars($grade['student_username']); ?>', '<?php echo htmlspecialchars($grade['subject_code']); ?>')">
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

    <!-- Add New Grade Form -->
    <div class="title">
        <i class="fas fa-plus-circle"></i>
        <span class="text">Add New Grade</span>
    </div>
    
    <div class="form-container">
        <form method="post" action="addGrade.php" class="grade-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="student_username"><i class="fas fa-user"></i> Student Username</label>
                    <input type="text" id="student_username" name="student_username" required>
                </div>
                <div class="form-group">
                    <label for="subject_code"><i class="fas fa-book"></i> Subject Code</label>
                    <input type="text" id="subject_code" name="subject_code" required>
                </div>
                <div class="form-group">
                    <label for="semester"><i class="fas fa-clock"></i> Semester</label>
                    <select id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="prelim_grade"><i class="fas fa-star"></i> Prelim Grade</label>
                    <input type="number" id="prelim_grade" name="prelim_grade" min="0" max="100" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="midterm_grade"><i class="fas fa-star"></i> Midterm Grade</label>
                    <input type="number" id="midterm_grade" name="midterm_grade" min="0" max="100" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="final_grade"><i class="fas fa-star"></i> Final Grade</label>
                    <input type="number" id="final_grade" name="final_grade" min="0" max="100" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="average"><i class="fas fa-graduation-cap"></i> Average</label>
                    <input type="number" id="average" name="average" min="0" max="100" step="0.01" required readonly style="background-color: #e9ecef; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label for="status"><i class="fas fa-check-circle"></i> Status</label>
                    <input type="text" id="status" name="status" readonly style="background-color: #e9ecef; cursor: not-allowed; font-weight: 600; color: #495057;">
                </div>
                <div class="form-group">
                    <label for="remarks"><i class="fas fa-comment"></i> Remarks</label>
                    <input type="text" id="remarks" name="remarks" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(102, 126, 234, 0.3)'">
                        <i class="fas fa-plus" style="font-size: 16px;"></i> Add Grade
                    </button>
                </div>
            </div>
        </form>
    </div>

    </div>
        </div>
    </section>

    <!-- Edit Grade Modal -->
    <div id="editGradeModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #eee;">
                <h2 style="margin: 0; color: #333; display: flex; align-items: center; gap: 10px;"><i class="fas fa-edit" style="color: #667eea;"></i> Edit Grade</h2>
                <span onclick="closeEditModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            
            <div id="editGradeContent">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #667eea;"></i>
                    <p style="margin-top: 20px; color: #666;">Loading grade data...</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<script>
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
    /* Navigation styles */
    nav .nav-links li a.active {
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    nav .nav-links li a.active i,
    nav .nav-links li a.active .link-name {
        color: white;
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
    font-size: 0.9rem;
    border: none;
}

.compact-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
    font-size: 0.9rem;
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

.action-buttons button:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
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

.btn-approve:active {
    background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
    transform: translateY(1px);
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

.btn-reject:active {
    background: linear-gradient(135deg, #bd2130 0%, #a93226 100%);
    transform: translateY(1px);
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

.btn-view:active {
    background: linear-gradient(135deg, #117a8b 0%, #00838f 100%);
    transform: translateY(1px);
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

/* Status Badge Styles */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.status-passed {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-badge.status-failed {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.status-badge.status-incomplete {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.3);
}

.status-badge.status-dropped {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
    border: 1px solid rgba(108, 117, 125, 0.3);
}

.status-badge.status-in-progress {
    background: rgba(255, 193, 7, 0.1);
    color: #d39e00;
    border: 1px solid rgba(255, 193, 7, 0.3);
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

:root{
    /* ===== Colors ===== */
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
    
    /* ====== Transition ====== */
    --tran-05: all 0.5s ease;
    --tran-03: all 0.3s ease;
    --tran-03: all 0.2s ease;
}
body{
    min-height: 100vh;
    background-color: var(--primary-color);
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
    box-shadow: 4px 0 15px rgba(102, 126, 234, 0.3);
    transition: var(--tran-05);
    z-index: 100;
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
nav .logo-name .logo_name{
    font-size: 18px;
    font-weight: 600;
    color: #fff;
    margin-left: 12px;
    white-space: nowrap;
    transition: opacity 0.3s ease;
}

nav .menu-items li a .link-name{
    font-size: 15px;
    font-weight: 400;
    color: #ffffff;    
    transition: var(--tran-05);
}
    font-weight: 600;
    color: #ffffff;
    margin-left: 14px;
    transition: var(--tran-05);
}
nav.close .logo_name{
    opacity: 0;
    pointer-events: none;
}
nav .menu-items{
    height: calc(100% - 70px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow-y: auto;
}

nav .menu-items::-webkit-scrollbar{
    display: none;
}
.menu-items li{
    list-style: none;
}
.nav-links li{
    position: relative;
    margin: 5px 0;
}
.menu-items li a{
    display: flex;
    align-items: center;
    padding: 12px 15px;
    text-decoration: none;
    position: relative;
    border-radius: 10px;
    transition: all 0.3s ease;
}
.nav-links li a:hover:before{
    content: "";
    position: absolute;
    left: -7px;
    height: 5px;
    width: 5px;
    border-radius: 50%;
    background-color: #ffffff;
}
body.dark li a:hover:before{
    background-color: var(--text-color);
}
.menu-items li a i{
    font-size: 24px;
    min-width: 45px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
}
.menu-items li a .link-name{
    font-size: 18px;
    font-weight: 400;
    color: #ffffff;    
    transition: var(--tran-05);
}
nav.close li a .link-name{
    opacity: 0;
    pointer-events: none;
}
.nav-links li a:hover i,
.nav-links li a:hover .link-name{
    color: #ffffff;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}
.nav-links li a:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(5px);
}
body.dark .nav-links li a:hover i,
body.dark .nav-links li a:hover .link-name{
    color: #ffffff;
}
.menu-items .logout-mode{
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding-top: 15px;
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
.dashboard .top .search-box{
    position: relative;
    height: 45px;
    max-width: 600px;
    width: 100%;
    margin: 0 30px;
}
.top .search-box input{
    position: absolute;
    border: 1px solid var(--border-color);
    background-color: var(--panel-color);
    padding: 0 25px 0 50px;
    border-radius: 5px;
    height: 100%;
    width: 100%;
    color: var(--text-color);
    font-size: 15px;
    font-weight: 400;
    outline: none;
}
.top .search-box i{
    position: absolute;
    left: 15px;
    font-size: 22px;
    z-index: 10;
    top: 50%;
    transform: translateY(-50%);
    color: var(--black-light-color);
}
.top img{
    width: 40px;
    border-radius: 50%;
}

.dash-content .title{
    display: flex;
    align-items: center;
    margin: 30px 0 20px 0;
}
.dash-content .title i{
    position: relative;
    height: 30px;
    width: 30px;
    background-color: var(--primary-color);
    border-radius: 6px;
    color: var(--title-icon-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.dash-content .title .text{
    font-size: 18px;
    font-weight: 600;
    color: var(--text-color);
    margin-left: 8px;
}
.dash-content .boxes{
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
}
.dash-content .boxes .box{
    display: flex;
    flex-direction: column;
    align-items: center;
    border-radius: 12px;
    width: calc(100% / 3 - 15px);
    padding: 15px 20px;
    background-color: var(--box1-color);
    transition: var(--tran-05);
}
.boxes .box i{
    font-size: 35px;
    color: var(--text-color);
}
.boxes .box .text{
    white-space: nowrap;
    font-size: 18px;
    font-weight: 500;
    color: var(--text-color);
}
.boxes .box .number{
    font-size: 40px;
    font-weight: 500;
    color: var(--text-color);
}
.boxes .box.box2{
    background-color: var(--box2-color);
}
.boxes .box.box3{
    background-color: var(--box3-color);
}
.dash-content .activity .activity-data{
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}
.activity .activity-data{
    display: flex;
}
.activity-data .data{
    display: flex;
    flex-direction: column;
    margin: 0 15px;
}
.activity-data .data-title{
    font-size: 20px;
    font-weight: 500;
    color: var(--text-color);
}
.activity-data .data .data-list{
    font-size: 18px;
    font-weight: 400;
    margin-top: 20px;
    white-space: nowrap;
    color: var(--text-color);
}
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
    .activity .activity-data{
        overflow-X: scroll;
    }
}
@media (max-width: 780px) {
    .dash-content .boxes .box{
        width: calc(100% / 2 - 15px);
        margin-top: 15px;
    }
}
@media (max-width: 560px) {
    .dash-content .boxes .box{
        width: 100% ;
    }
}
@media (max-width: 400px) {
    nav{
        width: 0px;
    }
    nav.close{
        width: 73px;
    }
    nav .logo_name{
        opacity: 0;
        pointer-events: none;
    }
    nav.close .logo_name{
        opacity: 0;
        pointer-events: none;
    }
    nav li a .link-name{
        opacity: 0;
        pointer-events: none;
    }
    nav.close li a .link-name{
        opacity: 0;
        pointer-events: none;
    }
    nav ~ .dashboard{
        left: 0;
        width: 100%;
    }
    nav.close ~ .dashboard{
        left: 73px;
        width: calc(100% - 73px);
    }
    nav ~ .dashboard .top{
        left: 0;
        width: 100%;
    }
    nav.close ~ .dashboard .top{
        left: 0;
        width: 100%;
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
    border-left-color: #4CAF50;
    background: rgba(76, 175, 80, 0.1);
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



</style>

<script>
// Edit Grade Modal Functions
function editGrade(id) {
    // Show loading state
    const modal = document.getElementById('editGradeModal');
    const modalContent = document.getElementById('editGradeContent');
    modalContent.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #667eea;"></i><p style="margin-top: 20px; color: #666;">Loading grade data...</p></div>';
    modal.style.display = 'flex';
    
    // Fetch grade data via AJAX
    fetch('getGradeData.php?grade_id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.grade);
            } else {
                modalContent.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-exclamation-circle" style="font-size: 48px; color: #dc3545;"></i><p style="margin-top: 20px; color: #666;">Error: ' + data.message + '</p><button onclick="closeEditModal()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button></div>';
            }
        })
        .catch(error => {
            console.error('Error fetching grade data:', error);
            modalContent.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ffc107;"></i><p style="margin-top: 20px; color: #666;">Error loading grade data. Please try again.</p><button onclick="closeEditModal()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button></div>';
        });
}

function populateEditForm(grade) {
    const modalContent = document.getElementById('editGradeContent');
    
    // Dynamically generate the form HTML
    const formHTML = `
        <form id="editGradeForm" method="post" action="editGrade.php">
            <input type="hidden" id="edit_grade_id" name="grade_id" value="${grade.id}">
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0 0 8px 0; font-size: 0.9rem;"><i class="fas fa-user" style="color: #667eea;"></i> <strong>${grade.first_name} ${grade.last_name}</strong> (${grade.student_username})</p>
                <p style="margin: 0; font-size: 0.9rem;"><i class="fas fa-book" style="color: #667eea;"></i> <strong>${grade.subject_code}</strong> - ${grade.subject_name || 'N/A'}</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="edit_semester"><i class="fas fa-clock"></i> Semester</label>
                    <select id="edit_semester" name="semester" required style="width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem;">
                        <option value="">Select Semester</option>
                        <option value="1st Semester" ${grade.semester === '1st Semester' ? 'selected' : ''}>1st Semester</option>
                        <option value="2nd Semester" ${grade.semester === '2nd Semester' ? 'selected' : ''}>2nd Semester</option>
                        <option value="Summer" ${grade.semester === 'Summer' ? 'selected' : ''}>Summer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_prelim_grade"><i class="fas fa-star"></i> Prelim Grade</label>
                    <input type="number" id="edit_prelim_grade" name="prelim_grade" min="0" max="100" step="0.01" required value="${parseFloat(grade.prelim_grade)}" style="width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem;">
                </div>
                <div class="form-group">
                    <label for="edit_midterm_grade"><i class="fas fa-star"></i> Midterm Grade</label>
                    <input type="number" id="edit_midterm_grade" name="midterm_grade" min="0" max="100" step="0.01" required value="${parseFloat(grade.midterm_grade)}" style="width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem;">
                </div>
                <div class="form-group">
                    <label for="edit_final_grade"><i class="fas fa-star"></i> Final Grade</label>
                    <input type="number" id="edit_final_grade" name="final_grade" min="0" max="100" step="0.01" required value="${parseFloat(grade.final_grade)}" style="width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem;">
                </div>
                <div class="form-group">
                    <label for="edit_average"><i class="fas fa-graduation-cap"></i> Average</label>
                    <input type="number" id="edit_average" name="average" min="0" max="100" step="0.01" readonly style="width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; background-color: #e9ecef; cursor: not-allowed;" value="${parseFloat(grade.average)}">
                </div>
                <div class="form-group">
                    <label for="edit_status"><i class="fas fa-check-circle"></i> Status</label>
                    <input type="text" id="edit_status" name="status" readonly style="width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; background-color: #e9ecef; cursor: not-allowed; font-weight: 600; color: #495057;" value="${grade.status}">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="edit_remarks"><i class="fas fa-comment"></i> Remarks</label>
                    <input type="text" id="edit_remarks" name="remarks" placeholder="Optional remarks" style="width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem;" value="${grade.remarks || ''}">
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeEditModal()" style="padding: 12px 24px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
                <button type="submit" id="editSubmitBtn" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;"><i class="fas fa-save"></i> Update Grade</button>
            </div>
        </form>
    `;
    
    modalContent.innerHTML = formHTML;
    
    // Attach event listeners for real-time calculation
    attachEditGradeListeners();
}

function attachEditGradeListeners() {
    const prelimGrade = document.getElementById('edit_prelim_grade');
    const midtermGrade = document.getElementById('edit_midterm_grade');
    const finalGrade = document.getElementById('edit_final_grade');
    const averageField = document.getElementById('edit_average');
    const statusField = document.getElementById('edit_status');
    const remarksField = document.getElementById('edit_remarks');
    
    function calculateEditStatusAndRemarks() {
        const prelim = parseFloat(prelimGrade?.value) || 0;
        const midterm = parseFloat(midtermGrade?.value) || 0;
        const final = parseFloat(finalGrade?.value) || 0;
        
        let status = '';
        let remarks = '';
        
        // Calculate average if at least one grade is entered
        if (prelim > 0 || midterm > 0 || final > 0) {
            const average = (prelim + midterm + final) / 3;
            if (averageField) {
                averageField.value = Math.round(average * 100) / 100;
                
                // Visual feedback
                averageField.style.backgroundColor = '#e8f5e8';
                setTimeout(() => {
                    averageField.style.backgroundColor = '#e9ecef';
                }, 500);
            }
        }
        
        // Determine status based on which grades are entered
        if (prelim > 0 && midterm === 0 && final === 0) {
            status = 'In Progress';
            remarks = 'Grading in progress - Prelim: ' + prelim.toFixed(2);
        } else if (prelim > 0 && midterm > 0 && final === 0) {
            status = 'In Progress';
            const currentAvg = (prelim + midterm) / 2;
            remarks = 'Grading in progress - Current Avg: ' + currentAvg.toFixed(2) + '% (Final pending)';
        } else if (prelim > 0 && midterm > 0 && final > 0) {
            const average = (prelim + midterm + final) / 3;
            
            if (average >= 75) {
                status = 'Passed';
                remarks = 'Passed with ' + average.toFixed(2) + '% average';
            } else {
                status = 'Failed';
                remarks = 'Failed with ' + average.toFixed(2) + '% average';
            }
        }
        
        if (statusField) {
            statusField.value = status;
            
            // Visual feedback
            statusField.style.backgroundColor = status === 'In Progress' ? '#fff3cd' : '#e8f5e8';
            setTimeout(() => {
                statusField.style.backgroundColor = '#e9ecef';
            }, 500);
        }
        
        if (remarksField) {
            remarksField.value = remarks;
            
            // Visual feedback
            remarksField.style.backgroundColor = '#e8f5e8';
            setTimeout(() => {
                remarksField.style.backgroundColor = '#fff';
            }, 500);
        }
    }
    
    if (prelimGrade) {
        prelimGrade.addEventListener('input', calculateEditStatusAndRemarks);
    }
    if (midtermGrade) {
        midtermGrade.addEventListener('input', calculateEditStatusAndRemarks);
    }
    if (finalGrade) {
        finalGrade.addEventListener('input', calculateEditStatusAndRemarks);
    }
    
    // Initial calculation when form is loaded
    setTimeout(() => {
        calculateEditStatusAndRemarks();
    }, 100);
}

function closeEditModal() {
    document.getElementById('editGradeModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editGradeModal');
    if (event.target === modal) {
        closeEditModal();
    }
}

// Remove the old submitEditForm function - form now submits normally via PHP redirect
function deleteGrade(id, student, subject) {
    if (confirm('Are you sure you want to delete the grade for student "' + student + '" in subject "' + subject + '"? This action cannot be undone.')) {
        // Create and submit a form to delete_grade.php
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'deleteGrade.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'grade_id';
        idInput.value = id;
        
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

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

// Enhanced form validation for grade form
document.addEventListener('DOMContentLoaded', function() {
    const gradeForm = document.querySelector('.grade-form');
    if (gradeForm) {
        gradeForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-submit');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Grade...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds if form doesn't submit
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-plus"></i> Add Grade';
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }
});

// Automatic Average Calculation and Status/Remarks Generation
document.addEventListener('DOMContentLoaded', function() {
    // Get input elements for add form
    const prelimGrade = document.getElementById('prelim_grade');
    const midtermGrade = document.getElementById('midterm_grade');
    const finalGrade = document.getElementById('final_grade');
    const averageField = document.getElementById('average');
    const statusField = document.getElementById('status');
    const remarksField = document.getElementById('remarks');
    
    // Function to calculate status based on grades entered
    function calculateStatusAndRemarks() {
        const prelim = parseFloat(prelimGrade?.value) || 0;
        const midterm = parseFloat(midtermGrade?.value) || 0;
        const final = parseFloat(finalGrade?.value) || 0;
        
        let status = '';
        let remarks = '';
        
        // Calculate average if at least one grade is entered
        if (prelim > 0 || midterm > 0 || final > 0) {
            const average = (prelim + midterm + final) / 3;
            averageField.value = Math.round(average * 100) / 100;
            
            // Add visual feedback for average calculation
            if (averageField) {
                averageField.style.backgroundColor = '#e8f5e8';
                setTimeout(() => {
                    averageField.style.backgroundColor = '#e9ecef';
                }, 1000);
            }
        }
        
        // Determine status based on which grades are entered
        if (prelim > 0 && midterm === 0 && final === 0) {
            // Only prelim entered
            status = 'In Progress';
            remarks = 'Grading in progress - Prelim: ' + prelim.toFixed(2);
        } else if (prelim > 0 && midterm > 0 && final === 0) {
            // Prelim and midterm entered
            status = 'In Progress';
            const currentAvg = (prelim + midterm) / 2;
            remarks = 'Grading in progress - Current Avg: ' + currentAvg.toFixed(2) + '% (Final pending)';
        } else if (prelim > 0 && midterm > 0 && final > 0) {
            // All grades entered - determine Passed/Failed
            const average = (prelim + midterm + final) / 3;
            
            if (average >= 75) {
                status = 'Passed';
                remarks = 'Passed with ' + average.toFixed(2) + '% average';
            } else {
                status = 'Failed';
                remarks = 'Failed with ' + average.toFixed(2) + '% average';
            }
        }
        
        // Update status and remarks fields
        if (statusField) {
            statusField.value = status;
            
            // Add visual feedback for status change
            statusField.style.backgroundColor = status === 'In Progress' ? '#fff3cd' : '#e8f5e8';
            setTimeout(() => {
                statusField.style.backgroundColor = '#e9ecef';
            }, 1000);
        }
        
        if (remarksField) {
            remarksField.value = remarks;
            
            // Add visual feedback for remarks change
            remarksField.style.backgroundColor = '#e8f5e8';
            setTimeout(() => {
                remarksField.style.backgroundColor = '#e9ecef';
            }, 1000);
        }
    }
    
    // Add event listeners for grade inputs
    if (prelimGrade) {
        prelimGrade.addEventListener('input', calculateStatusAndRemarks);
        prelimGrade.addEventListener('change', calculateStatusAndRemarks);
    }
    
    if (midtermGrade) {
        midtermGrade.addEventListener('input', calculateStatusAndRemarks);
        midtermGrade.addEventListener('change', calculateStatusAndRemarks);
    }
    
    if (finalGrade) {
        finalGrade.addEventListener('input', calculateStatusAndRemarks);
        finalGrade.addEventListener('change', calculateStatusAndRemarks);
    }
    
    // Initial calculation when page loads
    setTimeout(() => {
        calculateStatusAndRemarks();
    }, 500);
});
</script>
