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
    
    $grade_id = intval($_POST['grade_id'] ?? 0);
    $subject_code = trim($_POST['subject_code'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $prelim_grade = floatval($_POST['prelim_grade'] ?? 0);
    $midterm_grade = floatval($_POST['midterm_grade'] ?? 0);
    $final_grade = floatval($_POST['final_grade'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Get the original grade_id for checking
    $original_grade_id = $_POST['grade_id'] ?? null;
    
    // Validate that grade_id is valid (must be present and a valid integer)
    if ($original_grade_id === null || $original_grade_id === '') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid grade ID: Missing value']);
        exit();
    }
    
    // Additional validation: grade_id must be a positive integer
    if (!is_numeric($original_grade_id) || intval($original_grade_id) <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid grade ID: Must be a positive number, got: ' . $original_grade_id]);
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
    
    // Check if grade exists
    $check_grade = $conn->prepare("SELECT id, student_username FROM grades WHERE id = ?");
    $check_grade->bind_param("i", $grade_id);
    $check_grade->execute();
    $grade_result = $check_grade->get_result();
    
    if ($grade_result->num_rows === 0) {
        $check_grade->close();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Grade not found with ID: ' . $grade_id]);
        exit();
    }
    
    $grade_info = $grade_result->fetch_assoc();
    $student_username = $grade_info['student_username'];
    $check_grade->close();
    
    // Update the grade (including subject_code)
    // SQL has 9 placeholders: subject_code, semester, prelim_grade, midterm_grade, final_grade, average, status, remarks, id
    // Type string: s (subject_code), s (semester), d (prelim), d (midterm), d (final), d (average), s (status), s (remarks), i (id)
    $stmt = $conn->prepare("UPDATE grades SET subject_code = ?, semester = ?, prelim_grade = ?, midterm_grade = ?, final_grade = ?, average = ?, status = ?, remarks = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssddddssi", 
        $subject_code,
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
        if ($stmt->affected_rows > 0) {
            ob_end_clean();
            echo json_encode([
                'success' => true, 
                'message' => 'Grade updated successfully',
                'data' => [
                    'average' => number_format($average, 2),
                    'status' => $status
                ]
            ]);
        } else {
            // Update was successful but no rows were affected (same data)
            ob_end_clean();
            echo json_encode([
                'success' => true, 
                'message' => 'Grade saved (no changes detected)',
                'data' => [
                    'average' => number_format($average, 2),
                    'status' => $status
                ]
            ]);
        }
    } else {
        error_log("Error updating grade: " . $stmt->error);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Error updating grade: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();

