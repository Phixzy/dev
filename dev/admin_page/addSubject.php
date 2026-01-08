<?php
session_start();
require_once '../config/dbcon.php';
require_once 'message_handler.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];
    $course = $_POST['course'];
    $year_level = $_POST['year_level'];
    $hour = $_POST['hour'];
    $instructor_name = $_POST['instructor_name'];
    
    // Validate input
    if (empty($subject_name) || empty($subject_code) || empty($course) || 
        empty($year_level) || empty($hour) || empty($instructor_name)) {
        setErrorMessage("All fields are required!");
        header('Location: adminpage.php');
        exit();
    }
    
    // Check if subject code already exists
    $check_sql = "SELECT id FROM subjects WHERE subject_code = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $subject_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setErrorMessage("Subject code already exists! Please choose a different code.");
        header('Location: adminpage.php');
        exit();
    }
    
    // Insert into subjects table
    $sql = "INSERT INTO subjects (subject_name, subject_code, course, year_level, hour, instructor_name) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssis", $subject_name, $subject_code, $course, $year_level, $hour, $instructor_name);
    
    if ($stmt->execute()) {
        setSuccessMessage("Subject and instructor added successfully!");
    } else {
        setErrorMessage("Error: " . $conn->error);
    }
    
    $stmt->close();
} else {
    setErrorMessage("Invalid request method!");
}

$conn->close();
header('Location: adminpage.php');
exit();
?>
