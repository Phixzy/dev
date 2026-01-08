<?php
session_start();
require_once '../config/dbcon.php';

// Message handling functions (same as in adminpage.php)
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
    case 'get_appointments':
        getAppointments();
        break;
    case 'delete':
        deleteAppointment();
        break;
    case 'edit':
        editAppointment();
        break;
    case 'update':
        updateAppointment();
        break;
    default:
        // For form submissions without action parameter
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            handleFormSubmission();
        }
        break;
}

function getAppointments() {
    global $conn;
    
    $sql = "SELECT * FROM appointments ORDER BY appointment_date DESC, start_time DESC";
    $result = $conn->query($sql);
    
    $appointments = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $appointments]);
    exit();
}

function deleteAppointment() {
    global $conn;
    
    // Get ID from both GET and POST (form may use either)
    $id = $_GET['id'] ?? $_POST['id'] ?? 0;
    
    error_log("DeleteAppointment called with ID: " . $id);
    
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
}

function editAppointment() {
    global $conn;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id = $_POST['id'] ?? 0;
        $appointment_date = $_POST['appointment_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $total_slots = $_POST['total_slots'] ?? 1;
        $available_slots = $_POST['available_slots'] ?? $total_slots;
        
        if ($id > 0 && $appointment_date && $start_time && $end_time) {
            $sql = "UPDATE appointments SET appointment_date = ?, start_time = ?, end_time = ?, total_slots = ?, available_slots = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $appointment_date, $start_time, $end_time, $total_slots, $available_slots, $id);
            
            if ($stmt->execute()) {
                setSuccessMessage("Appointment updated successfully!");
            } else {
                setErrorMessage("Error updating appointment: " . $conn->error);
            }
            
            $stmt->close();
        } else {
            setErrorMessage("All fields are required!");
        }
        
        header('Location: adminpage.php');
        exit();
    } else {
        // GET request - show edit form
        $id = $_GET['id'] ?? 0;
        if ($id > 0) {
            $sql = "SELECT * FROM appointments WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $appointment = $result->fetch_assoc();
                ?>
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Edit Appointment</title>
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                </head>
                <body>
                    <div style="max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                        <h2><i class="fas fa-edit"></i> Edit Appointment</h2>
                        <form method="post" action="manageAppointments.php?action=edit">
                            <input type="hidden" name="id" value="<?php echo $appointment['id']; ?>">
                            
                            <div style="margin: 15px 0;">
                                <label><i class="fas fa-calendar"></i> Date:</label>
                                <input type="date" name="appointment_date" value="<?php echo $appointment['appointment_date']; ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
                            </div>
                            
                            <div style="margin: 15px 0;">
                                <label><i class="fas fa-clock"></i> Start Time:</label>
                                <input type="time" name="start_time" value="<?php echo $appointment['start_time']; ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
                            </div>
                            
                            <div style="margin: 15px 0;">
                                <label><i class="fas fa-clock"></i> End Time:</label>
                                <input type="time" name="end_time" value="<?php echo $appointment['end_time']; ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
                            </div>
                            
                            <div style="margin: 15px 0;">
                                <label><i class="fas fa-users"></i> Total Slots:</label>
                                <input type="number" name="total_slots" value="<?php echo $appointment['total_slots']; ?>" min="1" max="50" required style="width: 100%; padding: 8px; margin-top: 5px;">
                            </div>
                            
                            <div style="margin: 15px 0;">
                                <label><i class="fas fa-check-circle"></i> Available Slots:</label>
                                <input type="number" name="available_slots" value="<?php echo $appointment['available_slots']; ?>" min="0" max="<?php echo $appointment['total_slots']; ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
                            </div>
                            
                            <div style="margin: 20px 0;">
                                <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
                                    <i class="fas fa-save"></i> Update Appointment
                                </button>
                                <a href="adminpage.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </body>
                </html>
                <?php
            } else {
                echo "Appointment not found!";
            }
            $stmt->close();
        } else {
            echo "Invalid appointment ID!";
        }
    }
}

function updateAppointment() {
    global $conn;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id = $_POST['appointment_id'] ?? 0;
        $appointment_date = $_POST['appointment_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $total_slots = $_POST['total_slots'] ?? 1;
        $available_slots = $_POST['available_slots'] ?? $total_slots;
        
        if ($id > 0 && $appointment_date && $start_time && $end_time) {
            // Validate that available slots doesn't exceed total slots
            if ($available_slots > $total_slots) {
                setErrorMessage("Available slots cannot exceed total slots!");
                header('Location: adminpage.php');
                exit();
            }
            
            // Validate time range
            if (strtotime($start_time) >= strtotime($end_time)) {
                setErrorMessage("End time must be after start time!");
                header('Location: adminpage.php');
                exit();
            }
            
            $sql = "UPDATE appointments SET appointment_date = ?, start_time = ?, end_time = ?, total_slots = ?, available_slots = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $appointment_date, $start_time, $end_time, $total_slots, $available_slots, $id);
            
            if ($stmt->execute()) {
                setSuccessMessage("Appointment updated successfully!");
            } else {
                setErrorMessage("Error updating appointment: " . $conn->error);
            }
            
            $stmt->close();
        } else {
            setErrorMessage("All fields are required!");
        }
        
        header('Location: adminpage.php');
        exit();
    } else {
        // Not a POST request, redirect
        header('Location: adminpage.php');
        exit();
    }
}

function handleFormSubmission() {
    global $conn;
    
    $appointment_date = $_POST['appointment_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $total_slots = $_POST['total_slots'] ?? 1;
    $available_slots = $total_slots; // Initially, available slots equal total slots
    
    // Basic validation
    if (empty($appointment_date) || empty($start_time) || empty($end_time)) {
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
    
    // Insert into appointments table
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
    $conn->close();
    header('Location: adminpage.php');
    exit();
}
?>
