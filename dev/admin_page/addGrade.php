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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_username = trim($_POST['student_username'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
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
    if (empty($student_username) || empty($subject_code) || empty($semester) || 
        $prelim_grade < 0 || $midterm_grade < 0 || $final_grade < 0) {
        $_SESSION['error'] = "All fields are required and grades must be non-negative.";
        header("Location: grades.php");
        exit();
    }
    
    // Check if student exists
    $check_student = $conn->prepare("SELECT id FROM students WHERE username = ?");
    $check_student->bind_param("s", $student_username);
    $check_student->execute();
    $student_result = $check_student->get_result();
    
    if ($student_result->num_rows === 0) {
        $_SESSION['error'] = "Student with username '$student_username' not found. Please use the exact username (e.g., john@student).";
        header("Location: grades.php");
        exit();
    }
    $check_student->close();
    
    // Check if subject exists
    $check_subject = $conn->prepare("SELECT id, subject_name, course, year_level FROM subjects WHERE subject_code = ?");
    $check_subject->bind_param("s", $subject_code);
    $check_subject->execute();
    $subject_result = $check_subject->get_result();
    
    if ($subject_result->num_rows === 0) {
        $_SESSION['error'] = "Subject with code '$subject_code' not found. Please check the subject code.";
        header("Location: grades.php");
        exit();
    }
    $subject = $subject_result->fetch_assoc();
    $check_subject->close();
    
    // Check if grade already exists for this student and subject
    $check_grade = $conn->prepare("SELECT id FROM grades WHERE student_username = ? AND subject_code = ?");
    $check_grade->bind_param("ss", $student_username, $subject_code);
    $check_grade->execute();
    $grade_result = $check_grade->get_result();
    
    if ($grade_result->num_rows > 0) {
        $_SESSION['error'] = "Grade already exists for student '$student_username' in subject '$subject_code'. Please edit the existing grade.";
        $check_grade->close();
        header("Location: grades.php");
        exit();
    }
    $check_grade->close();
    
    // Insert the grade
    $stmt = $conn->prepare("INSERT INTO grades (student_username, subject_code, subject_name, course, year_level, semester, prelim_grade, midterm_grade, final_grade, average, status, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssdddsss", 
        $student_username, 
        $subject_code,
        $subject['subject_name'],
        $subject['course'],
        $subject['year_level'],
        $semester,
        $prelim_grade,
        $midterm_grade,
        $final_grade,
        $average,
        $status,
        $remarks,
        $_SESSION['username']
    );
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Grade added successfully for student: $student_username in $subject_code";
    } else {
        $_SESSION['error'] = "Error adding grade: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: grades.php");
exit();
?>

