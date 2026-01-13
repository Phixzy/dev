<?php
// Enable error reporting for debugging - MUST be at the very top
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Start output buffering to ensure clean JSON output
ob_start();

// Set JSON content type
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $student_username = trim($_POST['student_username'] ?? '');
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $prelim_grade = floatval($_POST['prelim_grade'] ?? 0);
    $midterm_grade = floatval($_POST['midterm_grade'] ?? 0);
    $final_grade = floatval($_POST['final_grade'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Validate inputs
    if (empty($student_username)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Student username is required']);
        exit();
    }
    
    if (empty($subject_code)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Subject code is required']);
        exit();
    }
    
    if (empty($semester)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Semester is required']);
        exit();
    }
    
    // Check if student exists and get their course and year level
    $check_student = $conn->prepare("SELECT id, college_course, college_year FROM students WHERE username = ?");
    $check_student->bind_param("s", $student_username);
    $check_student->execute();
    $student_result = $check_student->get_result();
    
    if ($student_result->num_rows === 0) {
        $check_student->close();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Student not found: ' . $student_username]);
        exit();
    }
    
    $student_info = $student_result->fetch_assoc();
    $student_course = $student_info['college_course'] ?? '';
    $student_year_level = $student_info['college_year'] ?? '';
    $check_student->close();
    
    // Check if subject exists and verify it matches student's course and year level
    $check_subject = $conn->prepare("SELECT subject_name, course, year_level, instructor_name FROM subjects WHERE subject_code = ?");
    $check_subject->bind_param("s", $subject_code);
    $check_subject->execute();
    $subject_result = $check_subject->get_result();
    
    if ($subject_result->num_rows === 0) {
        $check_subject->close();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Subject not found in the subjects list: ' . $subject_code]);
        exit();
    }
    
    $subject_info = $subject_result->fetch_assoc();
    $subject_course = $subject_info['course'] ?? '';
    $subject_year_level = $subject_info['year_level'] ?? '';
    $subject_instructor_name = $subject_info['instructor_name'] ?? '';
    $check_subject->close();
    
    // Validate that subject matches student's course and year level
    // Subject must either be for All courses/years OR match the student's specific course/year
    $course_matches = empty($subject_course) || $subject_course === 'All' || $subject_course === $student_course;
    $year_matches = empty($subject_year_level) || $subject_year_level === 'All' || $subject_year_level === $student_year_level;
    
    if (!$course_matches || !$year_matches) {
        $message = 'Subject "' . $subject_code . '" is not available for this student\'s course and year level.';
        if (!$course_matches && !$year_matches) {
            $message = 'Subject "' . $subject_code . '" is not available for ' . $student_course . ' - ' . $student_year_level . '.';
        } elseif (!$course_matches) {
            $message = 'Subject "' . $subject_code . '" is not available for course: ' . $student_course . '.';
        } elseif (!$year_matches) {
            $message = 'Subject "' . $subject_code . '" is not available for year level: ' . $student_year_level . '.';
        }
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }
    
    // Get subject name from database
    $subject_name = $subject_info['subject_name'] ?? $subject_code;
    $course = $student_course;
    $year_level = $student_year_level;
    
    // Validate grades (allow 0 as valid grade for "not yet taken" but still count it)
    if ($prelim_grade < 0 || $prelim_grade > 100 || 
        $midterm_grade < 0 || $midterm_grade > 100 || 
        $final_grade < 0 || $final_grade > 100) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Grades must be between 0 and 100']);
        exit();
    }
    
    // Calculate average - count grades that are explicitly entered (including 0)
    // Check if each grade is provided (not empty/missing)
    $prelim_entered = isset($_POST['prelim_grade']);
    $midterm_entered = isset($_POST['midterm_grade']);
    $final_entered = isset($_POST['final_grade']);
    
    $grades_count = 0;
    $grades_sum = 0;
    
    if ($prelim_entered) {
        $grades_count++;
        $grades_sum += $prelim_grade;
    }
    if ($midterm_entered) {
        $grades_count++;
        $grades_sum += $midterm_grade;
    }
    if ($final_entered) {
        $grades_count++;
        $grades_sum += $final_grade;
    }
    
    $average = $grades_count > 0 ? round($grades_sum / $grades_count, 2) : 0;
    
    // Determine status based on grades entered
    $status = '';
    if ($prelim_entered && $midterm_entered && $final_entered) {
        // All grades entered
        if ($average >= 75) {
            $status = 'Passed';
        } else {
            $status = 'Failed';
        }
    } elseif ($prelim_entered || $midterm_entered || $final_entered) {
        // Some grades entered
        $status = 'Incomplete';
    } else {
        $status = 'Incomplete';
    }
    
    // Check if grade already exists for this student and subject
    $check_grade = $conn->prepare("SELECT id FROM grades WHERE student_username = ? AND subject_code = ?");
    $check_grade->bind_param("ss", $student_username, $subject_code);
    $check_grade->execute();
    $grade_result = $check_grade->get_result();
    
    if ($grade_result->num_rows > 0) {
        $check_grade->close();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Grade already exists for this student in this subject. Please edit the existing grade.']);
        exit();
    }
    $check_grade->close();
    
    // Insert the new grade
    $stmt = $conn->prepare("INSERT INTO grades (student_username, subject_code, subject_name, course, year_level, semester, prelim_grade, midterm_grade, final_grade, average, status, remarks, instructor_name, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $admin_username = $_SESSION['admin_username'] ?? 'admin';
    $stmt->bind_param("sssssssdddssss", 
        $student_username, 
        $subject_code,
        $subject_name,
        $course,
        $year_level,
        $semester,
        $prelim_grade,
        $midterm_grade,
        $final_grade,
        $average,
        $status,
        $remarks,
        $subject_instructor_name,
        $admin_username
    );
    
    if ($stmt->execute()) {
        $new_grade_id = $stmt->insert_id;
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Grade added successfully',
            'data' => [
                'id' => $new_grade_id,
                'average' => number_format($average, 2),
                'status' => $status
            ]
        ]);
    } else {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Error adding grade: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();

