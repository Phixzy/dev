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

// Get parameters from request
$grade_id = isset($_GET['grade_id']) ? intval($_GET['grade_id']) : 0;
$student_username = isset($_GET['username']) ? urldecode(trim($_GET['username'])) : '';

error_log("URL-decoded username: [" . $student_username . "]");

// Debug: Log the raw username received
error_log("Raw username from GET: [" . $_GET['username'] . "]");
error_log("Trimmed username: [" . $student_username . "]");
error_log("Username length: " . strlen($student_username));
for ($i = 0; $i < strlen($student_username); $i++) {
    error_log("Char $i: " . ord($student_username[$i]) . " = '" . $student_username[$i] . "'");
}

// If username is provided, fetch all grades for that student
if (!empty($student_username)) {
    error_log("Fetching grades for student: " . $student_username);
    
    // Fetch student info
    $student_sql = "SELECT id, username, first_name, last_name, email, college_course, college_year 
                    FROM students WHERE username = ?";
    $stmt = $conn->prepare($student_sql);
    $stmt->bind_param("s", $student_username);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    $student = null;
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();
    }
    $stmt->close();
    
    error_log("Student found: " . ($student ? "Yes" : "No"));
    if ($student) {
        error_log("Student ID: " . $student['id'] . ", Username: " . $student['username']);
    }
    
    if (!$student) {
        // Return debug info in the error response
        echo json_encode([
            'success' => false, 
            'message' => 'Student not found',
            'debug' => [
                'username' => $student_username,
                'length' => strlen($student_username),
                'raw_get' => $_GET['username'] ?? ''
            ]
        ]);
        $conn->close();
        exit();
    }
    
    // Fetch all grades for this student with correct subject name from subjects table
    $grades_sql = "SELECT g.*, COALESCE(sub.subject_name, g.subject_name) as subject_name, COALESCE(sub.instructor_name, '') as instructor_name
                   FROM grades g 
                   LEFT JOIN subjects sub ON g.subject_code = sub.subject_code
                   WHERE g.student_username = ?
                   ORDER BY g.created_at DESC";
    $stmt = $conn->prepare($grades_sql);
    $stmt->bind_param("s", $student_username);
    $stmt->execute();
    $grades_result = $stmt->get_result();
    
    error_log("SQL Query: " . $grades_sql);
    error_log("Query parameter: " . $student_username);
    error_log("Number of grades found: " . $grades_result->num_rows);
    
    $grades = [];
    if ($grades_result->num_rows > 0) {
        while ($row = $grades_result->fetch_assoc()) {
            $grades[] = $row;
            error_log("Grade found: ID=" . $row['id'] . ", Subject=" . $row['subject_code'] . ", Prelim=" . $row['prelim_grade']);
        }
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'student' => $student, 
        'grades' => $grades
    ]);
} elseif ($grade_id > 0) {
    // Fetch single grade data with student and subject info
    $sql = "SELECT g.*, COALESCE(sub.subject_name, g.subject_name) as subject_name,
                   s.first_name, s.last_name, s.email
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
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>

