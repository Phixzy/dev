<?php
session_start();
require_once 'config/dbcon.php';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Append domain if not already has domain
    if (strpos($username, "@") === false) {
        if ($username == "admin") {
            $username = "admin@admin";
        } else {
            $username .= "@student";
        }
    }

    // Captcha verification
    if (!isset($_POST['captcha']) || $_POST['captcha'] != $_SESSION['captcha']) {
        $_SESSION['error'] = "Captcha failed";
        header('Location: login.php');
        exit();
    }
    unset($_SESSION['captcha']);

    // Check if admin_users table exists
    $table_check_sql = "SHOW TABLES LIKE 'admin_users'";
    $table_result = $conn->query($table_check_sql);
    
    // Admin login check (if admin_users table exists and username contains @admin)
    if ($table_result && $table_result->num_rows > 0 && strpos($username, "@admin") !== false) {
        // Check if admin_users table has data, if not create default admin
        $check_admins_sql = "SELECT COUNT(*) as count FROM admin_users";
        $admin_count_result = $conn->query($check_admins_sql);
        $admin_count = $admin_count_result->fetch_assoc()['count'];
        
        if ($admin_count == 0) {
            // Create default admin user
            $default_username = 'admin@admin';
            $default_password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ss", $default_username, $default_password);
            $stmt->execute();
            $stmt->close();
        }
        
        // Verify admin credentials against admin_users table
        $admin_sql = "SELECT * FROM admin_users WHERE username = ?";
        $stmt = $conn->prepare($admin_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin_result = $stmt->get_result();
        
        if ($admin_result->num_rows > 0) {
            $admin_row = $admin_result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $admin_row['password'])) {
                // Admin login successful
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_id'] = $admin_row['id'];
                header("Location: admin_page/adminpage.php");
                exit();
            } else {
                $_SESSION['error'] = "Incorrect username or password";
                header('Location: login.php');
                exit();
            }
        } else {
            $_SESSION['error'] = "Admin user not found. Please check your username.";
            header('Location: login.php');
            exit();
        }
        $stmt->close();
    }

    // Student login check (original logic)
    $sql = "SELECT * FROM students WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify password (stored as hash)
        if (password_verify($password, $row['password'])) {              
            if (strpos($username, "@student") !== false) {
                $stat = $row['status'];
                if ($stat == "PENDING") {
                    $_SESSION['error'] = "Your account is waiting for approval";
                    header("Location: login.php");
                } elseif ($stat == "REJECTED") {
                    $_SESSION['error'] = "Your account has been rejected. Please contact administration.";
                    header("Location: login.php");
                } else {
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['userid'] = $row['id'];
                    header("Location: student_page/studentpage.php");
                }
            } else {
                $_SESSION['error'] = "Invalid username format";
                header("Location: login.php");
            }
        } else {
            $_SESSION['error'] = "Incorrect username or password";
            header('Location: login.php');
        }
    } else {
        $_SESSION['error'] = "Incorrect username or password";
        header('Location: login.php');
    }
    $stmt->close();

} else {
    $_SESSION['error'] = "Invalid Request";
    header('Location: login.php');
}

$conn->close();
?>
