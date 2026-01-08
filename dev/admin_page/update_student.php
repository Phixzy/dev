<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $_SESSION['error'] = 'Database connection failed: ' . $conn->connect_error;
    header('Location: student_status.php');
    exit();
}

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate required fields
    if (empty($_POST['id']) || empty($_POST['first_name']) || empty($_POST['last_name']) || 
        empty($_POST['email']) || empty($_POST['username']) || empty($_POST['password']) ||
        empty($_POST['college_course']) || empty($_POST['college_year']) || empty($_POST['status'])) {
        $_SESSION['error'] = 'All required fields must be filled.';
        header('Location: edit_student.php?id=' . $_POST['id']);
        exit();
    }

    // Sanitize and validate inputs
    $id = intval($_POST['id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $date_of_birth = !empty($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
    $gender = !empty($_POST['gender']) ? trim($_POST['gender']) : '';
    $student_phone = !empty(trim($_POST['student_phone'])) ? trim($_POST['student_phone']) : '';
    $address = !empty(trim($_POST['address'])) ? trim($_POST['address']) : '';
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $guardian_name = !empty(trim($_POST['guardian_name'])) ? trim($_POST['guardian_name']) : '';
    $guardian_phone = !empty(trim($_POST['guardian_phone'])) ? trim($_POST['guardian_phone']) : '';
    $guardian_address = !empty(trim($_POST['guardian_address'])) ? trim($_POST['guardian_address']) : '';
    $elem_name = !empty(trim($_POST['elem_name'])) ? trim($_POST['elem_name']) : '';
    $elem_year = !empty(trim($_POST['elem_year'])) ? trim($_POST['elem_year']) : '';
    $junior_name = !empty(trim($_POST['junior_name'])) ? trim($_POST['junior_name']) : '';
    $junior_year = !empty(trim($_POST['junior_year'])) ? trim($_POST['junior_year']) : '';
    $senior_name = !empty(trim($_POST['senior_name'])) ? trim($_POST['senior_name']) : '';
    $senior_year = !empty(trim($_POST['senior_year'])) ? trim($_POST['senior_year']) : '';
    $strand = !empty(trim($_POST['strand'])) ? trim($_POST['strand']) : '';
    $college_year = trim($_POST['college_year']);
    $college_course = trim($_POST['college_course']);
    $status = trim($_POST['status']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
        header('Location: edit_student.php?id=' . $id);
        exit();
    }

    // Check if student exists
    $check_sql = "SELECT id FROM students WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        $_SESSION['error'] = 'Failed to prepare check statement: ' . $conn->error;
        header('Location: edit_student.php?id=' . $id);
        exit();
    }
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $_SESSION['error'] = 'Student not found.';
        $check_stmt->close();
        $conn->close();
        header('Location: student_status.php');
        exit();
    }
    $check_stmt->close();

    // Prepare the UPDATE statement with all fields
    $sql = "UPDATE students SET 
        first_name = ?, 
        last_name = ?, 
        date_of_birth = ?, 
        gender = ?, 
        student_phone = ?, 
        address = ?, 
        email = ?, 
        username = ?, 
        password = ?, 
        guardian_name = ?, 
        guardian_phone = ?, 
        guardian_address = ?, 
        elem_name = ?, 
        elem_year = ?, 
        junior_name = ?, 
        junior_year = ?, 
        senior_name = ?, 
        senior_year = ?, 
        strand = ?, 
        college_year = ?, 
        college_course = ?, 
        status = ? 
        WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $_SESSION['error'] = 'Failed to prepare update statement: ' . $conn->error;
        $conn->close();
        header('Location: edit_student.php?id=' . $id);
        exit();
    }
    
    // Bind parameters - 22 string parameters + 1 integer parameter = 23 total
    // Type string: 22 's' for strings + 1 'i' for integer id = 23 type indicators
    $types = str_repeat('s', 22) . 'i';
    $stmt->bind_param($types, 
        $first_name, 
        $last_name, 
        $date_of_birth, 
        $gender, 
        $student_phone, 
        $address, 
        $email, 
        $username, 
        $password, 
        $guardian_name, 
        $guardian_phone, 
        $guardian_address, 
        $elem_name, 
        $elem_year, 
        $junior_name, 
        $junior_year, 
        $senior_name, 
        $senior_year, 
        $strand, 
        $college_year, 
        $college_course, 
        $status, 
        $id
    );

    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = 'Student information updated successfully!';
            $stmt->close();
            $conn->close();
            header('Location: view_student.php?id=' . $id);
            exit();
        } else {
            $_SESSION['message'] = 'No changes were made but data is valid.';
            $stmt->close();
            $conn->close();
            header('Location: view_student.php?id=' . $id);
            exit();
        }
    } else {
        // Log error for debugging
        error_log("Database error: " . $stmt->error);
        $_SESSION['error'] = 'Error updating student information. Please try again.';
        $stmt->close();
        $conn->close();
        header('Location: edit_student.php?id=' . $id);
        exit();
    }
} else {
    // Not a POST request
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: student_status.php');
    exit();
}

