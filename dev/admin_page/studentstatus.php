<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $_SESSION['error'] = 'Database connection failed.';
    header('Location: student_status.php');
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if ($id === 0) {
        $_SESSION['error'] = 'Invalid student ID.';
        header('Location: student_status.php');
        exit();
    }
    
    // Verify student exists
    $check_sql = "SELECT id, status FROM students WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $_SESSION['error'] = 'Student not found.';
        $check_stmt->close();
        $conn->close();
        header('Location: student_status.php');
        exit();
    }
    
    $student = $check_result->fetch_assoc();
    $check_stmt->close();
    
    switch ($action) {
        case 'approve':
            $sql = "UPDATE students SET status = 'approved' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Student approved successfully!';
            } else {
                $_SESSION['error'] = 'Failed to approve student.';
            }
            $stmt->close();
            break;
            
        case 'reject':
            $sql = "UPDATE students SET status = 'rejected' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Student rejected successfully!';
            } else {
                $_SESSION['error'] = 'Failed to reject student.';
            }
            $stmt->close();
            break;
            
        case 'delete':
            // First, delete associated profile image if exists
            $img_sql = "SELECT image FROM students WHERE id = ?";
            $img_stmt = $conn->prepare($img_sql);
            $img_stmt->bind_param("i", $id);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            if ($img_row = $img_result->fetch_assoc()) {
                if (!empty($img_row['image']) && file_exists('../' . $img_row['image'])) {
                    unlink('../' . $img_row['image']);
                }
            }
            $img_stmt->close();
            
            // Disable foreign key checks temporarily to allow deletion
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Delete the student
            $sql = "DELETE FROM students WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Student deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete student: ' . $stmt->error;
            }
            $stmt->close();
            
            // Re-enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            break;
            
        default:
            $_SESSION['error'] = 'Invalid action.';
            break;
    }
} else {
    $_SESSION['error'] = 'Invalid request method.';
}

$conn->close();
header('Location: student_status.php');
exit();
?>

