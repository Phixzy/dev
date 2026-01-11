<?php
session_start();
require_once '../config/dbcon.php';

// Message handling functions
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check database connection
    if (!$conn) {
        setErrorMessage("Database connection failed!");
        header('Location: adminpage.php');
        exit();
    }
    
    // Get form data
    $subject_id = $_POST['subject_id'] ?? 0;
    $subject_name = $_POST['subject_name'] ?? '';
    $subject_code = $_POST['subject_code'] ?? '';
    $course = $_POST['course'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $instructor_name = $_POST['instructor_name'] ?? '';
    $hour = $_POST['hour'] ?? 1;
    
    // Debug logging
    error_log("Subject Update - ID: $subject_id, Name: $subject_name, Code: $subject_code");
    
    // Validate input
    if (empty($subject_id) || $subject_id <= 0) {
        setErrorMessage("Invalid subject ID!");
        header('Location: adminpage.php');
        exit();
    }
    
    if (empty($subject_name)) {
        setErrorMessage("Subject name is required!");
        header('Location: adminpage.php');
        exit();
    }
    
    if (empty($subject_code)) {
        setErrorMessage("Subject code is required!");
        header('Location: adminpage.php');
        exit();
    }
    
    if (empty($course)) {
        setErrorMessage("Course is required!");
        header('Location: adminpage.php');
        exit();
    }
    
    if (empty($year_level)) {
        setErrorMessage("Year level is required!");
        header('Location: adminpage.php');
        exit();
    }
    
    if (empty($instructor_name)) {
        setErrorMessage("Instructor name is required!");
        header('Location: adminpage.php');
        exit();
    }
    
    if ($hour <= 0) {
        setErrorMessage("Hours must be greater than 0!");
        header('Location: adminpage.php');
        exit();
    }
    
    // Check if subject code already exists (excluding current subject)
    $check_sql = "SELECT id FROM subjects WHERE subject_code = ? AND id != ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("si", $subject_code, $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setErrorMessage("Subject code already exists! Please choose a different code.");
        header('Location: adminpage.php');
        exit();
    }
    
    // Check if subject exists before updating
    $exists_sql = "SELECT id FROM subjects WHERE id = ?";
    $exists_stmt = $conn->prepare($exists_sql);
    $exists_stmt->bind_param("i", $subject_id);
    $exists_stmt->execute();
    $exists_result = $exists_stmt->get_result();
    
    if ($exists_result->num_rows === 0) {
        setErrorMessage("Subject not found!");
        header('Location: adminpage.php');
        exit();
    }
    $exists_stmt->close();
    
    // Update subject
    $sql = "UPDATE subjects SET subject_name = ?, subject_code = ?, course = ?, 
            year_level = ?, instructor_name = ?, hour = ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        setErrorMessage("Failed to prepare statement: " . $conn->error);
        header('Location: adminpage.php');
        exit();
    }
    
    $stmt->bind_param("sssssii", $subject_name, $subject_code, $course, $year_level, $instructor_name, $hour, $subject_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            setSuccessMessage("Subject and instructor updated successfully!");
        } else {
            setErrorMessage("No changes were made. The subject data might be the same.");
        }
    } else {
        setErrorMessage("Error updating subject: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    setErrorMessage("Invalid request method!");
}

if (isset($conn)) {
    $conn->close();
}

header('Location: adminpage.php');
exit();
?>
