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

// Handle different actions based on request method and action parameter
// Check both GET and POST for action parameter (form may use POST with query params)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_subjects':
        getSubjects();
        break;
    case 'delete':
        deleteSubject();
        break;
    default:
        // For form submissions without action parameter
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            handleFormSubmission();
        }
        break;
}

function getSubjects() {
    global $conn;
    
    $sql = "SELECT * FROM subjects ORDER BY course, year_level, subject_name";
    $result = $conn->query($sql);
    
    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $subjects]);
    exit();
}

function deleteSubject() {
    global $conn;
    
    // Get ID from both GET and POST (form may use either)
    $id = $_GET['id'] ?? $_POST['id'] ?? 0;
    
    error_log("DeleteSubject called with ID: " . $id);
    
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
}

function handleFormSubmission() {
    global $conn;
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            deleteSubject();
            break;
        default:
            setErrorMessage("Unknown action!");
            header('Location: adminpage.php');
            exit();
    }
}
?>
