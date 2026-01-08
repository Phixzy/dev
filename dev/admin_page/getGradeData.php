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

// Get grade ID from request
$grade_id = isset($_GET['grade_id']) ? intval($_GET['grade_id']) : 0;

if ($grade_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid grade ID']);
    exit();
}

// Fetch grade data with student and subject info
$sql = "SELECT g.*, s.first_name, s.last_name, s.email, sub.subject_name
        FROM grades g 
        LEFT JOIN students s ON g.student_username = s.username 
        LEFT JOIN subjects sub ON g.subject_code = sub.subject_code
        WHERE g.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $grade_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $grade = $result->fetch_assoc();
    echo json_encode(['success' => true, 'grade' => $grade]);
} else {
    echo json_encode(['success' => false, 'message' => 'Grade not found']);
}

$stmt->close();
$conn->close();
?>

