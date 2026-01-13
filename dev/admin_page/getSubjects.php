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

// Get filter parameters (optional - for filtering by student info)
$course = $_GET['course'] ?? '';
$year_level = $_GET['year_level'] ?? '';
$student_username = $_GET['student_username'] ?? '';

// Get list of subjects that the student already has grades for (to exclude them)
$excluded_subjects = [];
if (!empty($student_username)) {
    $excluded_sql = "SELECT DISTINCT subject_code FROM grades WHERE student_username = ?";
    $excluded_stmt = $conn->prepare($excluded_sql);
    $excluded_stmt->bind_param("s", $student_username);
    $excluded_stmt->execute();
    $excluded_result = $excluded_stmt->get_result();
    
    if ($excluded_result && $excluded_result->num_rows > 0) {
        while ($row = $excluded_result->fetch_assoc()) {
            $excluded_subjects[] = $row['subject_code'];
        }
    }
    $excluded_stmt->close();
}

// Build query with optional filters
$sql = "SELECT id, subject_name, subject_code, course, year_level FROM subjects WHERE 1=1";
$params = [];
$types = "";

if (!empty($course)) {
    $sql .= " AND (course = ? OR course = 'All' OR course = '')";
    $params[] = $course;
    $types .= "s";
}

if (!empty($year_level)) {
    $sql .= " AND (year_level = ? OR year_level = 'All' OR year_level = '')";
    $params[] = $year_level;
    $types .= "s";
}

// Exclude subjects that the student already has grades for
if (!empty($excluded_subjects)) {
    $placeholders = implode(',', array_fill(0, count($excluded_subjects), '?'));
    $sql .= " AND subject_code NOT IN ($placeholders)";
    foreach ($excluded_subjects as $code) {
        $params[] = $code;
        $types .= "s";
    }
}

$sql .= " ORDER BY subject_code ASC";

// Prepare and execute
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
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
    'subjects' => $subjects
]);

$stmt->close();
$conn->close();
?>

