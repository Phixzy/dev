<?php
session_start();
require_once '../config/dbcon.php';
require_once 'message_handler.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $total_slots = $_POST['total_slots'];
    $available_slots = $available_slots = $total_slots; // Initially, available slots equal total slots
    
    // Basic validation
    if (empty($appointment_date) || empty($start_time) || empty($end_time) || empty($total_slots)) {
        setErrorMessage("All fields are required!");
        header('Location: adminpage.php');
        exit();
    }
    
    // Validate time range
    if (strtotime($start_time) >= strtotime($end_time)) {
        setErrorMessage("End time must be after start time!");
        header('Location: adminpage.php');
        exit();
    }
    
    // Use prepared statement for better security
    $sql = "INSERT INTO appointments (appointment_date, start_time, end_time, total_slots, available_slots) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $appointment_date, $start_time, $end_time, $total_slots, $available_slots);
    
    if ($stmt->execute()) {
        setSuccessMessage("Appointment schedule added successfully!");
    } else {
        setErrorMessage("Error: " . $conn->error);
    }
    
    $stmt->close();
    header('Location: adminpage.php');
    exit();
} else {
    header('Location: adminpage.php');
    exit();
}

$conn->close();
?>
