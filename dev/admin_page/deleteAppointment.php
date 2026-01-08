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

// Get the ID from GET parameter
$id = $_GET['id'] ?? 0;

error_log("deleteAppointment.php called with ID: " . $id);

if ($id > 0) {
    // First get appointment info for the message
    $get_sql = "SELECT appointment_date, start_time FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($get_sql);
    if (!$stmt) {
        setErrorMessage("Database error: " . $conn->error);
        header('Location: adminpage.php');
        exit();
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        
        // Delete the appointment
        $sql = "DELETE FROM appointments WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            setErrorMessage("Database error: " . $conn->error);
            header('Location: adminpage.php');
            exit();
        }
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $date_str = date('M d, Y', strtotime($appointment['appointment_date']));
                $time_str = date('h:i A', strtotime($appointment['start_time']));
                setSuccessMessage("Appointment on {$date_str} at {$time_str} deleted successfully!");
            } else {
                setErrorMessage("No appointment was deleted. It may have already been removed.");
            }
        } else {
            setErrorMessage("Error deleting appointment: " . $stmt->error);
        }
        $stmt->close();
    } else {
        setErrorMessage("Appointment not found!");
    }
} else {
    setErrorMessage("Invalid appointment ID! ID value: " . var_export($id, true));
}

$conn->close();
header('Location: adminpage.php');
exit();
?>

