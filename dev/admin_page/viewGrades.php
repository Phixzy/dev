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

// Get student username from URL
$student_username = isset($_GET['username']) ? trim($_GET['username']) : '';

// Validate and fetch student info
$student_info = null;
if (!empty($student_username)) {
    $student_sql = "SELECT id, username, first_name, last_name, email, college_course, college_year 
                    FROM students WHERE username = ?";
    $stmt = $conn->prepare($student_sql);
    $stmt->bind_param("s", $student_username);
    $stmt->execute();
    $student_result = $stmt->get_result();
    if ($student_result->num_rows > 0) {
        $student_info = $student_result->fetch_assoc();
    }
    $stmt->close();
}

// If student not found, redirect back
if (!$student_info) {
    $_SESSION['error'] = "Student not found.";
    header("Location: grades.php");
    exit();
}

// Fetch all grades for this student with correct subject name from subjects table
$grades = [];
$grades_sql = "SELECT g.*, COALESCE(sub.subject_name, g.subject_name) as subject_name
               FROM grades g 
               LEFT JOIN subjects sub ON g.subject_code = sub.subject_code
               WHERE g.student_username = ? 
               ORDER BY g.created_at DESC";
$stmt = $conn->prepare($grades_sql);
$stmt->bind_param("s", $student_username);
$stmt->execute();
$grades_result = $stmt->get_result();
if ($grades_result->num_rows > 0) {
    while ($row = $grades_result->fetch_assoc()) {
        $grades[] = $row;
    }
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title>View Grades - <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></title>
</head>
<body>
    <nav>
        <div class="logo-name">
            <span class="logo_name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="adminpage.php"><i class="fas fa-calendar-alt"></i><span class="link-name">Appointments</span></a></li>
                <li><a href="student_status.php"><i class="fas fa-user-check"></i><span class="link-name">Student Status</span></a></li>
                <li><a href="grades.php" class="active"><i class="fas fa-chart-bar"></i><span class="link-name">Grades</span></a></li>
                <li><a href="master_list.php"><i class="fas fa-list-alt"></i><span class="link-name">Master List</span></a></li>
                <li><a href="emails.php"><i class="fas fa-envelope"></i><span class="link-name">Emails</span></a></li>
                <li><a href="edit_homepage.php"><i class="fas fa-edit"></i><span class="link-name">Edit Homepage</span></a></li>
                <li><a href="admin_user.php"><i class="fas fa-user-shield"></i><span class="link-name">Admin User Management</span></a></li>
            </ul>
            <ul class="logout-mode">
                <li><a href="#"><i class="fas fa-sign-out-alt"></i><span class="link-name">Logout</span></a></li>
                <li class="mode"></li>
            </ul>
        </div>
    </nav>
    <section class="dashboard">
      <div class="top">
        <span class="sidebar-toggle"><i class="fas fa-bars"></i></span>
      </div>
        <div class="dash-content">
            <div class="messages-container" style="position: relative; z-index: 100; margin-top: 80px; margin-bottom: 20px;">
              <?php if (isset($_SESSION['message'])) { echo '<div class="global-message message" style="padding: 16px 24px; margin: 0 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; border-left: 5px solid #28a745;"><i class="fas fa-check-circle" style="color: #28a745; font-size: 24px;"></i><span style="color: #155724; font-weight: 500;">' . htmlspecialchars($_SESSION['message']) . '</span></div>'; unset($_SESSION['message']); } ?>
              <?php if (isset($_SESSION['error'])) { echo '<div class="global-message error-message" style="padding: 16px 24px; margin: 0 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; border-left: 5px solid #dc3545;"><i class="fas fa-times-circle" style="color: #dc3545; font-size: 24px;"></i><span style="color: #721c24; font-weight: 500;">' . htmlspecialchars($_SESSION['error']) . '</span></div>'; unset($_SESSION['error']); } ?>
            </div>
            <div class="activity">
                <div class="title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center;">
                        <i class="fas fa-arrow-left" style="cursor: pointer; margin-right: 10px;" onclick="window.location.href='grades.php'"></i>
                        <i class="fas fa-chart-bar"></i>
                        <span class="text">Grades: <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></span>
                    </div>
                    <a href="addGrade.php?username=<?php echo urlencode($student_username); ?>" style="text-decoration: none; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; font-weight: 500;"><i class="fas fa-plus"></i> Add Grade</a>
                </div>
                <div class="student-info-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px; margin-bottom: 20px; color: white;">
                    <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                        <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class="fas fa-user"></i></div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.3rem;"><?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 0.9rem;"><?php echo htmlspecialchars($student_username); ?></p>
                        </div>
                        <div style="margin-left: auto; display: flex; gap: 30px; flex-wrap: wrap;">
                            <div><p style="margin: 0; font-size: 0.8rem; opacity: 0.8;">Course</p><p style="margin: 3px 0 0 0; font-weight: 600;"><?php echo htmlspecialchars($student_info['college_course'] ?? '-'); ?></p></div>
                            <div><p style="margin: 0; font-size: 0.8rem; opacity: 0.8;">Year Level</p><p style="margin: 3px 0 0 0; font-weight: 600;"><?php echo htmlspecialchars($student_info['college_year'] ?? '-'); ?></p></div>
                            <div><p style="margin: 0; font-size: 0.8rem; opacity: 0.8;">Email</p><p style="margin: 3px 0 0 0; font-weight: 600;"><?php echo htmlspecialchars($student_info['email'] ?? '-'); ?></p></div>
                        </div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-book"></i> Subject</th>
                                <th><i class="fas fa-code"></i> Code</th>
                                <th><i class="fas fa-calendar"></i> Semester</th>
                                <th><i class="fas fa-edit"></i> Prelim</th>
                                <th><i class="fas fa-edit"></i> Midterm</th>
                                <th><i class="fas fa-edit"></i> Final</th>
                                <th><i class="fas fa-calculator"></i> Average</th>
                                <th><i class="fas fa-check-circle"></i> Remarks</th>
                                <th><i class="fas fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($grades)): ?>
                                <tr><td colspan="9" class="no-data"><i class="fas fa-clipboard-list" style="font-size: 2.5rem; color: #dee2e6; margin-bottom: 10px;"></i><p>No grades found for this student.</p><p style="font-size: 0.85rem; color: #6c757d;">Click "Add Grade" to add grades for this student.</p></td></tr>
                            <?php else: ?>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['subject_code']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                                        <td><?php echo $grade['prelim_grade'] > 0 ? number_format($grade['prelim_grade'], 2) : '-'; ?></td>
                                        <td><?php echo $grade['midterm_grade'] > 0 ? number_format($grade['midterm_grade'], 2) : '-'; ?></td>
                                        <td><?php echo $grade['final_grade'] > 0 ? number_format($grade['final_grade'], 2) : '-'; ?></td>
                                        <td><strong><?php echo $grade['average'] > 0 ? number_format($grade['average'], 2) : '-'; ?></strong></td>
                                        <td><span class="status-badge <?php $s=$grade['status']; echo $s=='Passed'?'status-approved':($s=='Failed'?'status-rejected':($s=='In Progress'?'status-pending':'status-incomplete')); ?>"><?php echo htmlspecialchars($grade['status']); ?></span></td>
                                        <td><div style="display: flex; gap: 5px; justify-content: center;"><a href="editGrade.php?id=<?php echo $grade['id']; ?>" style="text-decoration: none; padding: 6px 10px; border-radius: 4px; background: #28a745; color: white; font-size: 0.8rem;"><i class="fas fa-edit"></i></a><a href="deleteGrade.php?id=<?php echo $grade['id']; ?>" style="text-decoration: none; padding: 6px 10px; border-radius: 4px; background: #dc3545; color: white; font-size: 0.8rem;" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a></div></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($grades)): ?>
                <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h4 style="margin: 0 0 15px 0; color: #333;"><i class="fas fa-chart-pie"></i> Summary</h4>
                    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                        <?php $tot=count($grades); $pass=0; $fail=0; $prog=0; foreach($grades as $g){ if($g['status']=='Passed')$pass++; elseif($g['status']=='Failed')$fail++; else $prog++; } ?>
                        <div><p style="margin:0;font-size:0.8rem;color:#6c757d;">Total</p><p style="margin:3px 0 0 0;font-size:1.5rem;font-weight:600;color:#333;"><?php echo $tot; ?></p></div>
                        <div><p style="margin:0;font-size:0.8rem;color:#28a745;">Passed</p><p style="margin:3px 0 0 0;font-size:1.5rem;font-weight:600;color:#28a745;"><?php echo $pass; ?></p></div>
                        <div><p style="margin:0;font-size:0.8rem;color:#dc3545;">Failed</p><p style="margin:3px 0 0 0;font-size:1.5rem;font-weight:600;color:#dc3545;"><?php echo $fail; ?></p></div>
                        <div><p style="margin:0;font-size:0.8rem;color:#ffc107;">In Progress</p><p style="margin:3px 0 0 0;font-size:1.5rem;font-weight:600;color:#ffc107;"><?php echo $prog; ?></p></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<script>
document.addEventListener('DOMContentLoaded',function(){var b=document.body,s=b.querySelector("nav"),st=b.querySelector(".sidebar-toggle");localStorage.getItem("mode")==="dark"&&b.classList.toggle("dark");localStorage.getItem("status")==="close"&&s.classList.add("close");st&&st.addEventListener("click",function(){s.classList.toggle("close");localStorage.setItem("status",s.classList.contains("close")?"close":"open")});document.querySelectorAll('.global-message').forEach(function(m){setTimeout(function(){m.style.animation='fadeOut 0.5s';setTimeout(function(){m.parentNode&&m.parentNode.removeChild(m)},500)},5000)})});
</script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
nav .nav-links li a.active{background:rgba(255,255,255,0.2);box-shadow:0 4px 10px rgba(0,0,0,0.1)}
nav .nav-links li a.active i,nav .nav-links li a.active .link-name{color:white}
.table-container{overflow-x:auto;margin-bottom:30px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.compact-table{width:100%;border-collapse:collapse;font-size:0.9rem;background:white;border-radius:8px;overflow:hidden}
.compact-table th{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:12px 8px;font-weight:600;text-align:center;font-size:0.85rem;border:none}
.compact-table td{padding:10px 8px;border-bottom:1px solid #eee;vertical-align:middle;font-size:0.85rem}
.compact-table tr:hover{background-color:#f8f9fa}
.no-data{text-align:center;color:#6c757d;font-style:italic;padding:30px 20px;background:#f8f9fa}
.status-badge{padding:6px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;text-transform:uppercase}
.status-approved{background:rgba(40,167,69,0.1);color:#28a745;border:1px solid rgba(40,167,69,0.3)}
.status-rejected{background:rgba(220,53,69,0.1);color:#dc3545;border:1px solid rgba(220,53,69,0.3)}
.status-pending{background:rgba(255,193,7,0.1);color:#ffc107;border:1px solid rgba(255,193,7,0.3)}
.status-incomplete{background:rgba(23,162,184,0.1);color:#17a2b8;border:1px solid rgba(23,162,184,0.3)}
body{min-height:100vh;background-color:#0E4BF1}
nav{position:fixed;top:0;left:0;height:100%;width:250px;padding:10px 14px;background:linear-gradient(180deg,#667eea 0%,#764ba2 100%);box-shadow:4px 0 15px rgba(102,126,234,0.3);z-index:100}
nav.close{width:73px}
nav .logo-name{display:flex;align-items:center;padding:15px 10px;border-bottom:1px solid rgba(255,255,255,0.2);margin-bottom:15px}
nav .logo-name .logo_name{font-size:18px;font-weight:600;color:#fff;margin-left:12px;white-space:nowrap}
nav .menu-items{height:calc(100% - 70px);display:flex;flex-direction:column;justify-content:space-between;overflow-y:auto}
.menu-items li{list-style:none}
.nav-links li{position:relative;margin:5px 0}
.menu-items li a{display:flex;align-items:center;padding:12px 15px;text-decoration:none;position:relative;border-radius:10px;transition:all 0.3s ease}
.menu-items li a i{font-size:24px;min-width:45px;height:100%;display:flex;align-items:center;justify-content:center;color:#ffffff}
.menu-items li a .link-name{font-size:18px;font-weight:400;color:#ffffff}
.menu-items .logout-mode{border-top:1px solid rgba(255,255,255,0.2);padding-top:15px}
.dashboard{position:relative;left:250px;background-color:#FFF;min-height:100vh;width:calc(100% - 250px);padding:10px 14px;transition:all 0.5s ease}
nav.close~.dashboard{left:73px;width:calc(100% - 73px)}
.dashboard .top{position:fixed;top:0;left:250px;display:flex;width:calc(100% - 250px);justify-content:space-between;align-items:center;padding:10px 14px;background-color:#FFF;z-index:10}
nav.close~.dashboard .top{left:73px;width:calc(100% - 73px)}
.dashboard .top .sidebar-toggle{font-size:26px;color:#000;cursor:pointer}
.dash-content .title{display:flex;align-items:center;margin:30px 0 20px 0}
.dash-content .title i{position:relative;height:30px;width:30px;background-color:#0E4BF1;border-radius:6px;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.dash-content .title .text{font-size:18px;font-weight:600;color:#000;margin-left:8px}
@keyframes fadeOut{from{opacity:1}to{opacity:0;transform:translateY(-100%)}}
.global-message{position:relative;z-indexmargin-top:80px;margin-bottom:1000;:20px;box-shadow:0 4px 12px rgba(0,0,0,0.15);border-left:4px solid}
.global-message.message{border-left-color:#4CAF50;background:rgba(76,175,80,0.1)}
.global-message.error-message{border-left-color:#f44336;background:rgba(244,67,54,0.1)}
@media(max-width:1000px){nav{width:73px}nav.close{width:250px}nav~.dashboard{left:73px;width:calc(100% - 73px)}nav.close~.dashboard{left:250px;width:calc(100% - 250px)}nav~.dashboard .top{left:73px;width:calc(100% - 73px)}nav.close~.dashboard .top{left:250px;width:calc(100% - 250px)}}
</style>
</body>
</html>

