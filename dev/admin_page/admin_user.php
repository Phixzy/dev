<?php
session_start();

ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../config/dbcon.php';

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
        echo '<span class="icon-wrapper"><i class="fas fa-times-circle"></i></span>';
        echo '<span class="message-text">' . htmlspecialchars($_SESSION['error_message']) . '</span>';
        echo '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        echo '<div class="global-message message">';
        echo '<span class="icon-wrapper"><i class="fas fa-check-circle"></i></span>';
        echo '<span class="message-text">' . htmlspecialchars($_SESSION['success_message']) . '</span>';
        echo '</div>';
        unset($_SESSION['success_message']);
    }
}

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

// Check if admin_users table exists, create if not
$table_check_sql = "SHOW TABLES LIKE 'admin_users'";
$result = $conn->query($table_check_sql);
if (!$result || $result->num_rows == 0) {
    // Create the admin_users table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if (!$conn->query($create_table_sql)) {
        setErrorMessage("Error creating admin_users table: " . $conn->error);
    }
    
    // Insert default admin user if table was just created
    $check_users_sql = "SELECT COUNT(*) as count FROM admin_users";
    $result = $conn->query($check_users_sql);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $default_username = 'admin@admin';
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ss", $default_username, $default_password);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle form submission for adding new admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setErrorMessage("Invalid form submission. Please try again.");
        // Regenerate token on error
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Regenerate token after use to prevent replay attacks
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        $new_username = trim($_POST['username']);
        // Automatically append @admin suffix if not already present
        if (strpos($new_username, '@admin') === false) {
            $new_username .= '@admin';
        }
        $new_password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate username
        if (empty($new_username)) {
            setErrorMessage("Username is required!");
        } elseif (strlen($new_username) < 5) {
            setErrorMessage("Username must be at least 5 characters long (excluding @admin)!");
        } else {
            // Validate password
            if (empty($new_password)) {
                setErrorMessage("Password is required!");
            } elseif (strlen($new_password) < 6) {
                setErrorMessage("Password must be at least 6 characters long!");
            } elseif ($new_password !== $confirm_password) {
                setErrorMessage("Passwords do not match!");
            } else {
                // Check if username already exists
                $check_sql = "SELECT id FROM admin_users WHERE username = ?";
                $stmt = $conn->prepare($check_sql);
                $stmt->bind_param("s", $new_username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    setErrorMessage("Username '$new_username' already exists!");
                } else {
                    // Hash password and insert
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $insert_sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param("ss", $new_username, $hashed_password);
                    
                    if ($stmt->execute()) {
                        setSuccessMessage("Admin user '$new_username' added successfully!");
                    } else {
                        setErrorMessage("Error adding admin user: " . $conn->error);
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Handle delete admin user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Prevent deleting yourself
    $current_username = $_SESSION['admin_username'];
    $check_sql = "SELECT username FROM admin_users WHERE id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if ($admin['username'] === $current_username) {
            setErrorMessage("You cannot delete your own account!");
        } else {
            // Delete the admin user
            $delete_sql = "DELETE FROM admin_users WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $delete_id);
            
            if ($stmt->execute()) {
                setSuccessMessage("Admin user deleted successfully!");
            } else {
                setErrorMessage("Error deleting admin user: " . $conn->error);
            }
        }
    } else {
        setErrorMessage("Admin user not found!");
    }
    
    // Redirect to prevent resubmission and remove delete param from URL
    // Build absolute URL to ensure proper redirect
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $request_uri = $_SERVER['REQUEST_URI'];
    // Remove the delete parameter from the URL
    $clean_uri = preg_replace('/\?.*$/', '', $request_uri);
    $redirect_url = $protocol . '://' . $host . $clean_uri;
    
    header('Location: ' . $redirect_url);
    exit();
}

// Fetch all admin users
$admin_users = [];
$sql = "SELECT id, username, password, created_at FROM admin_users ORDER BY id ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admin_users[] = $row;
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
    
    <title>Admin User Management</title>
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
                <li><a href="admin_user.php" class="active">
                    <i class="fas fa-user-cog"></i>
                    <span class="link-name">Admin User Management</span>
                </a></li>
            </ul>
            
            <ul class="logout-mode">
                <li><a href="../student_page/logout.php">
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
        <div class="current-user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
        </div>
      </div>
      
        <div class="dash-content">
            <br>
            <!-- Display enhanced styled messages -->
            <div id="messagesContainer">
                <?php displayMessages(); ?>
            </div>
            
            <div class="activity">
                <div class="title">
                    <i class="fas fa-user-cog"></i>
                    <span class="text">Admin User Management</span>
                </div>
                
                <!-- Add New Admin Form -->
                <div class="form-container">
                    <div class="form-header">
                        <h3><i class="fas fa-user-plus"></i> Add New Admin User</h3>
                        <p class="form-hint">Enter a username </p>
                    </div>
                    <form method="post" action="admin_user.php" class="admin-form">
                        <input type="hidden" name="add_admin" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username"><i class="fas fa-user"></i> Username</label>
                                <div class="username-input-wrapper">
                                    <input type="text" id="username" name="username" 
                                           placeholder="Enter username" 
                                           pattern="[a-zA-Z0-9._]+"
                                           title="Enter a username without @admin (e.g., john)"
                                           required
                                           oninput="checkAdminUsernameAvailability()"
                                           onblur="checkAdminUsernameAvailability()">
                                    <span class="username-suffix">@admin</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password"><i class="fas fa-lock"></i> Password</label>
                                <input type="password" id="password" name="password" 
                                       placeholder="Enter password" 
                                       minlength="6"
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm password" 
                                       minlength="6"
                                       required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-plus"></i> Add Admin
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Admin Users Table -->
                <div class="table-container">
                    <table class="compact-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-list-ol"></i> #</th>
                                <th><i class="fas fa-user-tie"></i> Admin Username</th>
                                <th><i class="fas fa-key"></i> Password</th>
                                <th><i class="fas fa-sliders-h"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admin_users)): ?>
                                <tr>
                                    <td colspan="4" class="no-data">
                                        <i class="fas fa-users" style="font-size: 24px; margin-bottom: 10px; display: block; color: #6c757d;"></i>
                                        <strong>No admin users found</strong><br>
                                        <small>Use the form above to add your first admin user</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admin_users as $index => $admin): ?>
                                    <tr>
                                        <td class="cell-text">
                                            <?php echo $index + 1; ?>
                                        </td>
                                        <td class="cell-text">
                                            <i class="fas fa-id-badge" style="color: #667eea; margin-right: 5px;"></i>
                                            <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                            <?php if ($admin['username'] === $_SESSION['admin_username']): ?>
                                                <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 8px;">
                                                    <i class="fas fa-check" style="margin-right: 2px;"></i> You
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-text">
                                            <i class="fas fa-key" style="color: #fd7e14; margin-right: 5px;"></i>
                                            <span style="font-family: monospace; font-weight: 600; letter-spacing: 2px;">******</span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($admin['username'] !== $_SESSION['admin_username']): ?>
                                                    <a href="admin_user.php?delete=<?php echo $admin['id']; ?>" 
                                                       class="btn-reject" 
                                                       onclick="return confirm('Are you sure you want to delete admin user: <?php echo htmlspecialchars($admin['username']); ?>? This action cannot be undone.')"
                                                       title="Delete Admin User"
                                                       style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none; min-width: 36px; height: 36px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; padding: 8px 10px;">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: #6c757d; font-size: 0.8rem;">
                                                        <i class="fas fa-user-clock"></i> Current User
                                                    </span>
                                                <?php endif; ?>
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
</body>
</html>

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

.current-user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    padding: 8px 15px;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.current-user-info i {
    font-size: 20px;
    color: #667eea;
}

.current-user-info span {
    font-weight: 500;
    color: #495057;
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

.form-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.form-header h3 {
    color: #495057;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-header h3 i {
    color: #667eea;
}

.form-hint {
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 8px;
    margin-left: 28px;
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

/* Enhanced Global Message Styles */
@keyframes slideDown {
    from {
        transform: translate(-50%, -100%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, 0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translate(-50%, 0);
    }
    to {
        opacity: 0;
        transform: translate(-50%, -100%);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
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

@keyframes iconBounce {
    0%, 100% {
        transform: scale(1);
    }
    25% {
        transform: scale(1.2);
    }
    50% {
        transform: scale(0.9);
    }
    75% {
        transform: scale(1.1);
    }
}

.global-message {
    animation: slideDown 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10000;
    min-width: 350px;
    max-width: 450px;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.global-message.message {
    border-left: none;
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 18px 24px;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.global-message.message::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ffffff, rgba(255,255,255,0.5), #ffffff);
    background-size: 200% 100%;
    animation: shimmer 2s linear infinite;
}

.global-message.message::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
    animation: slide 3s ease-in-out infinite;
}

.global-message.message .icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    margin-right: 12px;
    animation: iconBounce 0.6s ease-out;
}

.global-message.message i {
    font-size: 18px;
    color: white;
    animation: iconBounce 0.6s ease-out;
}

.global-message.message .message-text {
    font-weight: 500;
    font-size: 15px;
    display: inline;
}

.global-message.error-message {
    border-left: none;
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    color: white;
    padding: 18px 24px;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.global-message.error-message::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ffffff, rgba(255,255,255,0.5), #ffffff);
    background-size: 200% 100%;
    animation: shimmer 2s linear infinite;
}

.global-message.error-message::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
    animation: slide 3s ease-in-out infinite;
}

.global-message.error-message .icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    margin-right: 12px;
    animation: iconBounce 0.6s ease-out;
}

.global-message.error-message i {
    font-size: 18px;
    color: white;
    animation: iconBounce 0.6s ease-out;
}

.global-message.error-message .message-text {
    font-weight: 500;
    font-size: 15px;
    display: inline;
}

/* Toast notification container */
.messages-container {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 15px;
    pointer-events: none;
}

/* Responsive message positioning */
@media (max-width: 768px) {
    .global-message {
        min-width: calc(100vw - 60px);
        max-width: calc(100vw - 60px);
        left: 50%;
        transform: translateX(-50%);
        top: 10px;
    }
    
    .messages-container {
        left: 50%;
        transform: translateX(-50%);
        top: 10px;
        align-items: center;
    }
    
    .current-user-info {
        display: none;
    }
}

/* Username Feedback Styles */
.username-feedback {
    margin-top: 6px;
    min-height: 20px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.username-feedback.available {
    color: #28a745;
}

.username-feedback.taken {
    color: #dc3545;
}

.username-feedback.checking {
    color: #6c757d;
}

.username-feedback i {
    font-size: 1rem;
}

.username-feedback.available i {
    color: #28a745;
}

.username-feedback.taken i {
    color: #dc3545;
}

.username-feedback.checking i {
    color: #ffc107;
}

.form-group input.taken {
    border-color: #dc3545 !important;
}

.form-group input.available {
    border-color: #28a745 !important;
}

/* Username input wrapper styles */
.username-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.username-input-wrapper input {
    padding-right: 75px;
    width: 100%;
}

.username-suffix {
    position: absolute;
    right: 12px;
    color: #6c757d;
    font-size: 0.9rem;
    pointer-events: none;
    font-weight: 500;
}

.form-group input:focus + .username-suffix,
.username-input-wrapper input:focus {
    border-color: #667eea;
}
</style>

<script>
// Enhanced message handling with animations
document.addEventListener('DOMContentLoaded', function() {
    const messageDivs = document.querySelectorAll('.global-message');
    
    messageDivs.forEach(messageDiv => {
        const isError = messageDiv.classList.contains('error-message');
        const timeout = isError ? 7000 : 5000; // Keep errors longer
        
        // Add entrance animation with delay
        setTimeout(() => {
            messageDiv.style.transition = 'all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        }, 100);
        
        setTimeout(() => {
            messageDiv.style.animation = 'fadeOut 0.5s ease-in forwards';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 500);
        }, timeout);
    });
    
    // Password confirmation validation
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    
    if (passwordInput && confirmInput) {
        const validatePassword = () => {
            if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                confirmInput.setCustomValidity('Passwords do not match');
                confirmInput.style.borderColor = '#dc3545';
            } else {
                confirmInput.setCustomValidity('');
                confirmInput.style.borderColor = '#e9ecef';
            }
        };
        
        passwordInput.addEventListener('input', validatePassword);
        confirmInput.addEventListener('input', validatePassword);
    }
    
    // Sidebar functionality
    const body = document.querySelector("body");
    const sidebar = body.querySelector("nav");
    const sidebarToggle = body.querySelector(".sidebar-toggle");
    
    let getStatus = localStorage.getItem("status");
    if (getStatus === "close") {
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
    
    // Add hover effects to buttons
    const buttons = document.querySelectorAll('.btn-submit, .action-buttons button');
    buttons.forEach(button => {
        if (!button.classList.contains('btn-reject')) {
            button.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 20px rgba(0,0,0,0.2)';
                }
            });
            
            button.addEventListener('mouseleave', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                }
            });
        }
    });
});

// Admin username availability check with debounce
let adminUsernameCheckTimeout = null;

function checkAdminUsernameAvailability() {
    const usernameInput = document.getElementById('username');
    const feedbackDiv = document.getElementById('admin-username-feedback');
    const previewSpan = document.getElementById('admin-username-preview');
    
    const username = usernameInput.value.trim();
    
    // Update preview
    if (username) {
        previewSpan.textContent = username + '@admin';
    } else {
        previewSpan.textContent = 'john@admin';
    }
    
    // Clear previous timeout
    if (adminUsernameCheckTimeout) {
        clearTimeout(adminUsernameCheckTimeout);
    }
    
    // If username is empty or too short, just update preview
    if (username.length < 5) {
        feedbackDiv.className = 'username-feedback';
        feedbackDiv.innerHTML = '';
        usernameInput.classList.remove('taken', 'available');
        usernameInput.style.borderColor = '';
        return;
    }
    
    // Debounce: wait 500ms after user stops typing
    adminUsernameCheckTimeout = setTimeout(() => {
        // Show checking state
        feedbackDiv.className = 'username-feedback checking';
        feedbackDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
        
        // Make AJAX request
        const formData = new FormData();
        formData.append('username', username);
        
        fetch('check_username.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                feedbackDiv.className = 'username-feedback available';
                feedbackDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                usernameInput.classList.remove('taken');
                usernameInput.classList.add('available');
                usernameInput.style.borderColor = '#28a745';
            } else {
                feedbackDiv.className = 'username-feedback taken';
                feedbackDiv.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
                usernameInput.classList.remove('available');
                usernameInput.classList.add('taken');
                usernameInput.style.borderColor = '#dc3545';
            }
        })
        .catch(error => {
            console.error('Error checking username:', error);
            feedbackDiv.className = 'username-feedback';
            feedbackDiv.innerHTML = '';
            usernameInput.classList.remove('taken', 'available');
            usernameInput.style.borderColor = '';
        });
    }, 500);
}
</script>

