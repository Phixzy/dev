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

// Process grade deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_id'])) {
    $grade_id = intval($_POST['grade_id']);
    
    // Get grade info before deletion for the message
    $get_grade = $conn->prepare("SELECT student_username, subject_code FROM grades WHERE id = ?");
    $get_grade->bind_param("i", $grade_id);
    $get_grade->execute();
    $grade_result = $get_grade->get_result();
    
    if ($grade_result->num_rows > 0) {
        $grade_info = $grade_result->fetch_assoc();
        $student_username = $grade_info['student_username'];
        $subject_code = $grade_info['subject_code'];
        
        // Delete the grade
        $delete_stmt = $conn->prepare("DELETE FROM grades WHERE id = ?");
        $delete_stmt->bind_param("i", $grade_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Grade deleted successfully for student: $student_username in subject: $subject_code";
        } else {
            $_SESSION['error'] = "Error deleting grade: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    } else {
        $_SESSION['error'] = "Grade not found.";
    }
    $get_grade->close();
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: grades.php");
exit();
?>

