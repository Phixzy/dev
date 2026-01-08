<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $_SESSION['error'] = 'Database connection failed.';
    header('Location: student_editProfile.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_profile'])) {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: student_editProfile.php');
    exit();
}

$student_id = intval($_POST['student_id']);

if ($student_id !== intval($_SESSION['userid'])) {
    $_SESSION['error'] = 'You can only update your own profile.';
    header('Location: student_editProfile.php');
    exit();
}

// Get current student data (including password)
$current_sql = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($current_sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$current_result = $stmt->get_result();

if ($current_result->num_rows === 0) {
    $_SESSION['error'] = 'Student not found.';
    header('Location: student_editProfile.php');
    exit();
}

$current_student = $current_result->fetch_assoc();
$stmt->close();

// Get form data
$first_name = $conn->real_escape_string($_POST['first_name'] ?? '');
$last_name = $conn->real_escape_string($_POST['last_name'] ?? '');
$email = $conn->real_escape_string($_POST['email'] ?? '');
$new_username = isset($_POST['username']) ? trim($_POST['username']) : '';
$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$date_of_birth = $conn->real_escape_string($_POST['date_of_birth'] ?? '');
$gender = $conn->real_escape_string($_POST['gender'] ?? '');
$student_phone = $conn->real_escape_string($_POST['student_phone'] ?? '');
$address = $conn->real_escape_string($_POST['address'] ?? '');
$guardian_name = $conn->real_escape_string($_POST['guardian_name'] ?? '');
$guardian_phone = $conn->real_escape_string($_POST['guardian_phone'] ?? '');
$guardian_address = $conn->real_escape_string($_POST['guardian_address'] ?? '');
$elem_name = $conn->real_escape_string($_POST['elem_name'] ?? '');
$elem_year = $conn->real_escape_string($_POST['elem_year'] ?? '');
$junior_name = $conn->real_escape_string($_POST['junior_name'] ?? '');
$junior_year = $conn->real_escape_string($_POST['junior_year'] ?? '');
$senior_name = $conn->real_escape_string($_POST['senior_name'] ?? '');
$senior_year = $conn->real_escape_string($_POST['senior_year'] ?? '');
$strand = $conn->real_escape_string($_POST['strand'] ?? '');

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($email)) {
    $_SESSION['error'] = 'Please fill in all required fields.';
    header('Location: student_editProfile.php');
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header('Location: student_editProfile.php');
    exit();
}

// Check if email is already used by another student
$check_sql = "SELECT id FROM students WHERE email = '" . $email . "' AND id != " . $student_id;
$check_result = $conn->query($check_sql);
if ($check_result && $check_result->num_rows > 0) {
    $_SESSION['error'] = 'This email is already registered by another student.';
    header('Location: student_editProfile.php');
    exit();
}

// Handle username change
$username_changed = false;
$final_username = $current_student['username']; // Default to current username

if (!empty($new_username)) {
    // Validate username format
    if (strlen($new_username) < 3) {
        $_SESSION['error'] = 'Username must be at least 3 characters long.';
        header('Location: student_editProfile.php');
        exit();
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $_SESSION['error'] = 'Username can only contain letters, numbers, and underscores.';
        header('Location: student_editProfile.php');
        exit();
    }
    
    // Add @student suffix
    $new_username_with_suffix = $new_username . '@student';
    
    // Check if username is different from current
    if ($new_username_with_suffix !== $current_student['username']) {
        // Check if username is already taken
        $username_check_sql = "SELECT id FROM students WHERE username = ? AND id != ?";
        $stmt = $conn->prepare($username_check_sql);
        $stmt->bind_param("si", $new_username_with_suffix, $student_id);
        $stmt->execute();
        $username_result = $stmt->get_result();
        
        if ($username_result->num_rows > 0) {
            $_SESSION['error'] = 'This username is already taken. Please choose a different username.';
            $stmt->close();
            header('Location: student_editProfile.php');
            exit();
        }
        $stmt->close();
        
        // Username is valid and different, will be updated
        $final_username = $new_username_with_suffix;
        $username_changed = true;
    }
}

// Handle password change
$password_changed = false;
if (!empty($old_password)) {
    // Verify old password
    if (!password_verify($old_password, $current_student['password'])) {
        $_SESSION['error'] = 'Current password is incorrect.';
        header('Location: student_editProfile.php');
        exit();
    }
    
    // Validate new password
    if (empty($new_password)) {
        $_SESSION['error'] = 'Please enter a new password.';
        header('Location: student_editProfile.php');
        exit();
    }
    
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = 'New password must be at least 6 characters long.';
        header('Location: student_editProfile.php');
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New password and confirm password do not match.';
        header('Location: student_editProfile.php');
        exit();
    }
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $password_changed = true;
}

// Build update query
$update_fields = array();
$update_fields[] = "first_name = '$first_name'";
$update_fields[] = "last_name = '$last_name'";
$update_fields[] = "email = '$email'";
$update_fields[] = "date_of_birth = " . (empty($date_of_birth) ? "NULL" : "'$date_of_birth'");
$update_fields[] = "gender = '$gender'";
$update_fields[] = "student_phone = '$student_phone'";
$update_fields[] = "address = '$address'";
$update_fields[] = "guardian_name = '$guardian_name'";
$update_fields[] = "guardian_phone = '$guardian_phone'";
$update_fields[] = "guardian_address = '$guardian_address'";
$update_fields[] = "elem_name = '$elem_name'";
$update_fields[] = "elem_year = '$elem_year'";
$update_fields[] = "junior_name = '$junior_name'";
$update_fields[] = "junior_year = '$junior_year'";
$update_fields[] = "senior_name = '$senior_name'";
$update_fields[] = "senior_year = '$senior_year'";
$update_fields[] = "strand = '$strand'";

// Add username to update if changed
if ($username_changed) {
    $update_fields[] = "username = '" . $conn->real_escape_string($final_username) . "'";
}

// Add password to update if changed
if ($password_changed) {
    $update_fields[] = "password = '" . $hashed_password . "'";
}

// Build and execute update query
$update_sql = "UPDATE students SET " . implode(", ", $update_fields) . " WHERE id = $student_id";

if ($conn->query($update_sql)) {
    // Update session username if changed
    if ($username_changed) {
        $_SESSION['username'] = $final_username;
    }
    
    $_SESSION['message'] = 'Profile updated successfully!';
    $conn->close();
    header('Location: student_profile.php');
    exit();
} else {
    $_SESSION['error'] = 'Failed to update profile. Error: ' . $conn->error;
    $conn->close();
    header('Location: student_editProfile.php');
    exit();
}

