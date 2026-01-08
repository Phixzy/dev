<?php
header('Content-Type: application/json');
require_once '../config/dbcon.php';

try {
    // Fetch available appointments (only future dates with available slots)
    $sql = "SELECT id, appointment_date, start_time, end_time, total_slots, available_slots 
            FROM appointments 
            WHERE appointment_date >= CURDATE() 
            AND available_slots > 0 
            ORDER BY appointment_date ASC, start_time ASC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $appointments = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format time for display (convert to 12-hour format)
            $start_time = date('g:i A', strtotime($row['start_time']));
            $end_time = date('g:i A', strtotime($row['end_time']));
            
            $appointments[] = [
                'id' => $row['id'],
                'appointment_date' => $row['appointment_date'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'total_slots' => (int)$row['total_slots'],
                'available_slots' => (int)$row['available_slots']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'count' => count($appointments)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'appointments' => []
    ]);
}

$conn->close();
?>

