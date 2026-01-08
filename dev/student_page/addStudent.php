<?php
session_start();
require_once '../config/dbcon.php';

// Function to display messages
function setMessage($message, $type = 'error') {
    $_SESSION['enrollment_message'] = $message;
    $_SESSION['enrollment_message_type'] = $type;
    header('Location: enroll.php');
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate appointment selection
    if (!isset($_POST['selected_appointment']) || empty($_POST['selected_appointment'])) {
        setMessage("Please select an appointment schedule before submitting.");
    }
    
    $appointment_id = $_POST['selected_appointment'];
    
    // Check if appointment still has available slots
    $check_sql = "SELECT id, available_slots FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        setMessage("The selected appointment is no longer available.");
    }
    
    $appointment = $result->fetch_assoc();
    if ($appointment['available_slots'] <= 0) {
        setMessage("The selected appointment is fully booked. Please choose another slot.");
    }
    
    // Get form data
    $username = $_POST['username'] . "@student";
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $student_phone = $_POST['student_phone'];
    $address = $_POST['address'];
    $guardian_name = $_POST['guardian_name'];
    $guardian_phone = $_POST['guardian_phone'];
    $guardian_address = $_POST['guardian_address'];
    $elem_name = $_POST['elem_name'];
    $elem_year = $_POST['elem_year'];
    $junior_name = $_POST['junior_name'];
    $junior_year = $_POST['junior_year'];
    $senior_name = $_POST['senior_name'];
    $senior_year = $_POST['senior_year'];
    $strand = $_POST['strand'];
    $college_course = $_POST['college_course'];
    $college_year = $_POST['college_year'];
    $appointment_display = $_POST['appointment_display'] ?? '';
    
    // Handle file upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../userpfp/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (!in_array($file_extension, $allowed_extensions)) {
            setMessage("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
        }
        
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = 'userpfp/' . $new_filename;
        } else {
            setMessage("Error uploading image. Please try again.");
        }
    } else {
        setMessage("Profile image is required.");
    }
    
    // Check if username already exists
    $check_sql = "SELECT id FROM students WHERE username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setMessage("Username already exists. Please choose a different username.");
    }
    
    // Check if email already exists
    $check_sql = "SELECT id FROM students WHERE email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setMessage("Email already registered. Please use a different email.");
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert student data
        $sql = "INSERT INTO students (
            username, password, email, image, first_name, last_name, date_of_birth, 
            gender, student_phone, address, guardian_name, guardian_phone, guardian_address,
            elem_name, elem_year, junior_name, junior_year, senior_name, senior_year, strand,
            college_course, college_year, appointment_id, appointment_details, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssssssssssssssssss", 
            $username, $password, $email, $image_path, $first_name, $last_name, 
            $date_of_birth, $gender, $student_phone, $address, $guardian_name, 
            $guardian_phone, $guardian_address, $elem_name, $elem_year, $junior_name, 
            $junior_year, $senior_name, $senior_year, $strand, $college_course, 
            $college_year, $appointment_id, $appointment_display
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error registering student: " . $stmt->error);
        }
        
        $student_id = $stmt->insert_id;
        
        // Update available slots for the appointment
        $new_available_slots = $appointment['available_slots'] - 1;
        $update_sql = "UPDATE appointments SET available_slots = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $new_available_slots, $appointment_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating appointment slots: " . $stmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Store success message and redirect
        $_SESSION['enrollment_success'] = true;
        $_SESSION['enrollment_message'] = "Enrollment application submitted successfully! Your appointment is confirmed.";
        $_SESSION['enrollment_message_type'] = 'success';
        header('Location: enroll.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        setMessage($e->getMessage());
    }
    
    $stmt->close();
}

$conn->close();
?>

