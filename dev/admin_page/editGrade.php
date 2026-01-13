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

// Process grade update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade_id = intval($_POST['grade_id'] ?? 0);
    $semester = trim($_POST['semester'] ?? '');
    $prelim_grade = floatval($_POST['prelim_grade'] ?? 0);
    $midterm_grade = floatval($_POST['midterm_grade'] ?? 0);
    $final_grade = floatval($_POST['final_grade'] ?? 0);
    $average = floatval($_POST['average'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Auto-calculate status based on grades entered
    $status = '';
    if ($prelim_grade > 0 && $midterm_grade === 0 && $final_grade === 0) {
        // Only prelim entered
        $status = 'In Progress';
    } elseif ($prelim_grade > 0 && $midterm_grade > 0 && $final_grade === 0) {
        // Prelim and midterm entered
        $status = 'In Progress';
    } elseif ($prelim_grade > 0 && $midterm_grade === 0 && $final_grade > 0) {
        // Prelim and final entered but midterm missing
        $status = 'Incomplete';
    } elseif ($prelim_grade > 0 && $midterm_grade > 0 && $final_grade > 0) {
        // All grades entered - determine Passed/Failed
        if ($average >= 75) {
            $status = 'Passed';
        } else {
            $status = 'Failed';
        }
    } else {
        // No grades entered or other cases
        $status = 'In Progress';
    }
    
    // Validate required fields
    if (empty($grade_id) || empty($semester) || 
        $prelim_grade < 0 || $midterm_grade < 0 || $final_grade < 0) {
        $_SESSION['error'] = "All fields are required and grades must be non-negative.";
        header("Location: grades.php");
        exit();
    }
    
    // Check if grade exists
    $check_grade = $conn->prepare("SELECT id, student_username, subject_code FROM grades WHERE id = ?");
    $check_grade->bind_param("i", $grade_id);
    $check_grade->execute();
    $grade_result = $check_grade->get_result();
    
    if ($grade_result->num_rows === 0) {
        $_SESSION['error'] = "Grade not found.";
        $check_grade->close();
        header("Location: grades.php");
        exit();
    }
    
    $grade_info = $grade_result->fetch_assoc();
    $student_username = $grade_info['student_username'];
    $subject_code = $grade_info['subject_code'];
    $check_grade->close();
    
    // Update the grade
    $stmt = $conn->prepare("UPDATE grades SET semester = ?, prelim_grade = ?, midterm_grade = ?, final_grade = ?, average = ?, status = ?, remarks = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sddddssi", 
        $semester,
        $prelim_grade,
        $midterm_grade,
        $final_grade,
        $average,
        $status,
        $remarks,
        $grade_id
    );
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Grade updated successfully for student: $student_username in subject: $subject_code";
    } else {
        $_SESSION['error'] = "Error updating grade: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: grades.php");
exit();
?>

