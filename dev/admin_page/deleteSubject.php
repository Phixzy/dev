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

error_log("deleteSubject.php called with ID: " . $id);

if ($id > 0) {
    // First get subject info for the message
    $get_sql = "SELECT subject_name, subject_code FROM subjects WHERE id = ?";
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
        $subject = $result->fetch_assoc();
        
        // Delete the subject
        $sql = "DELETE FROM subjects WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            setErrorMessage("Database error: " . $conn->error);
            header('Location: adminpage.php');
            exit();
        }
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                setSuccessMessage("Subject '{$subject['subject_name']}' ({$subject['subject_code']}) deleted successfully!");
            } else {
                setErrorMessage("No subject was deleted. It may have already been removed.");
            }
        } else {
            setErrorMessage("Error deleting subject: " . $stmt->error);
        }
        $stmt->close();
    } else {
        setErrorMessage("Subject not found!");
    }
} else {
    setErrorMessage("Invalid subject ID! ID value: " . var_export($id, true));
}

$conn->close();
header('Location: adminpage.php');
exit();
?>

