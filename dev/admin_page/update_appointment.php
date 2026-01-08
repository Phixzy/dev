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

function handleUpdateRequest() {
    global $conn;
    
    // Log all request data for debugging
    error_log("=== UPDATE REQUEST DEBUG ===");
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("GET: " . print_r($_GET, true));
    error_log("POST: " . print_r($_POST, true));
    
    $action = $_GET['action'] ?? '';
    
    // Handle both direct POST and form submissions
    if ($_SERVER["REQUEST_METHOD"] == "POST" && $action === 'update') {
        $id = $_POST['appointment_id'] ?? 0;
        $appointment_date = $_POST['appointment_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $total_slots = $_POST['total_slots'] ?? 1;
        $available_slots = $_POST['available_slots'] ?? $total_slots;
        
        // Log extracted data
        error_log("Extracted Data:");
        error_log("ID: $id");
        error_log("Date: $appointment_date");
        error_log("Start Time: $start_time");
        error_log("End Time: $end_time");
        error_log("Total Slots: $total_slots");
        error_log("Available Slots: $available_slots");
        
        if ($id > 0 && !empty($appointment_date) && !empty($start_time) && !empty($end_time)) {
            // Validation
            if ($available_slots > $total_slots) {
                setErrorMessage("Available slots cannot exceed total slots!");
                error_log("Validation failed: Available slots > total slots");
                header('Location: adminpage.php');
                exit();
            }
            
            if (strtotime($start_time) >= strtotime($end_time)) {
                setErrorMessage("End time must be after start time!");
                error_log("Validation failed: End time <= start time");
                header('Location: adminpage.php');
                exit();
            }
            
            // Perform update
            $sql = "UPDATE appointments SET appointment_date = ?, start_time = ?, end_time = ?, total_slots = ?, available_slots = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                setErrorMessage("Database prepare failed: " . $conn->error);
                error_log("Prepare failed: " . $conn->error);
                header('Location: adminpage.php');
                exit();
            }
            
            $stmt->bind_param("ssssii", $appointment_date, $start_time, $end_time, $total_slots, $available_slots, $id);
            
            if ($stmt->execute()) {
                $rows_affected = $stmt->affected_rows;
                error_log("Update executed successfully. Rows affected: $rows_affected");
                setSuccessMessage("Appointment updated successfully! ($rows_affected row(s) updated)");
            } else {
                $error = $conn->error;
                error_log("Execute failed: $error");
                setErrorMessage("Error updating appointment: " . $error);
            }
            
            $stmt->close();
        } else {
            error_log("Validation failed: Missing required fields");
            setErrorMessage("All fields are required!");
        }
        
        header('Location: adminpage.php');
        exit();
    } else {
        error_log("Invalid request method or action. Method: " . $_SERVER['REQUEST_METHOD'] . ", Action: $action");
        header('Location: adminpage.php');
        exit();
    }
}

// Handle the update request
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_GET['action'] ?? '') === 'update') {
    handleUpdateRequest();
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && ($_GET['action'] ?? '') === 'update') {
    // For debugging - show update form
    $id = $_GET['id'] ?? 0;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Debug Update Form</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .form-container { max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            .debug-info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Debug Update Form</h2>
            
            <div class="debug-info">
                <strong>Debug Information:</strong><br>
                Method: <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
                Action: <?php echo htmlspecialchars($_GET['action'] ?? 'none'); ?><br>
                ID: <?php echo $id; ?>
            </div>
            
            <form method="post" action="update_appointment.php?action=update">
                <input type="hidden" name="appointment_id" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label for="appointment_date"><i class="fas fa-calendar"></i> Date:</label>
                    <input type="date" id="appointment_date" name="appointment_date" value="2024-01-15" required>
                </div>
                
                <div class="form-group">
                    <label for="start_time"><i class="fas fa-clock"></i> Start Time:</label>
                    <input type="time" id="start_time" name="start_time" value="10:00" required>
                </div>
                
                <div class="form-group">
                    <label for="end_time"><i class="fas fa-clock"></i> End Time:</label>
                    <input type="time" id="end_time" name="end_time" value="12:00" required>
                </div>
                
                <div class="form-group">
                    <label for="total_slots"><i class="fas fa-users"></i> Total Slots:</label>
                    <input type="number" id="total_slots" name="total_slots" value="30" min="1" max="50" required>
                </div>
                
                <div class="form-group">
                    <label for="available_slots"><i class="fas fa-check-circle"></i> Available Slots:</label>
                    <input type="number" id="available_slots" name="available_slots" value="25" min="0" max="50" required>
                </div>
                
                <button type="submit">
                    <i class="fas fa-save"></i> Update Appointment (Debug)
                </button>
            </form>
            
            <div class="debug-info">
                <p><strong>This is a debug form to test the update functionality directly.</strong></p>
                <p>Use this form to verify that the database update works, then check why the modal form isn't working.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    echo "<h1>Invalid Request</h1>";
    echo "<p>This page expects a POST request with action=update.</p>";
    echo "<p>To test: <a href='update_appointment.php?action=update&id=1'>Click here for debug form</a></p>";
}

$conn->close();
?>
