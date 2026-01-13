<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get student info from username
$student_username = $_GET['student_username'] ?? '';

if (empty($student_username)) {
    echo json_encode(['success' => false, 'message' => 'Student username required']);
    $conn->close();
    exit();
}

// Fetch student info
$student_sql = "SELECT college_course, college_year FROM students WHERE username = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("s", $student_username);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    $stmt->close();
    $conn->close();
    exit();
}

$student = $student_result->fetch_assoc();
$stmt->close();

$course = $student['college_course'];
$year_level = $student['college_year'];

// Fetch subjects matching student's course and year level
$sql = "SELECT id, subject_name, subject_code, course, year_level, instructor_name 
        FROM subjects 
        WHERE (course = ? OR course = 'All' OR course = '') 
        AND (year_level = ? OR year_level = 'All' OR year_level = '')
        ORDER BY subject_code ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $course, $year_level);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'subjects' => $subjects,
    'student' => [
        'course' => $course,
        'year_level' => $year_level
    ]
]);

$stmt->close();
$conn->close();
?>

