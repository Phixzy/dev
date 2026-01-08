<?php
session_start();
require_once '../config/dbcon.php';

header('Content-Type: application/json');

$response = [
    'available' => true,
    'message' => 'Username is available'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $exclude_id = isset($_POST['exclude_id']) ? (int)$_POST['exclude_id'] : 0;
    
    // Validate input
    if (empty($username)) {
        $response['available'] = false;
        $response['message'] = 'Username is required';
        echo json_encode($response);
        exit;
    }
    
    // Add @admin suffix if not present
    if (strpos($username, '@admin') === false) {
        $username .= '@admin';
    }
    
    // Validate username format
    if (strlen($username) < 8) { // minimum 5 chars + @admin
        $response['available'] = false;
        $response['message'] = 'Username must be at least 5 characters (excluding @admin)';
        echo json_encode($response);
        exit;
    }
    
    if (!preg_match('/^[a-zA-Z0-9._]+$/', str_replace('@admin', '', $username))) {
        $response['available'] = false;
        $response['message'] = 'Username can only contain letters, numbers, dots, and underscores';
        echo json_encode($response);
        exit;
    }
    
    // Check if username exists in admin_users table
    $check_sql = "SELECT id FROM admin_users WHERE username = ?";
    $params = [$username];
    $types = "s";
    
    // If exclude_id is provided, exclude that admin (for profile updates)
    if ($exclude_id > 0) {
        $check_sql .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['available'] = false;
        $response['message'] = 'Username is already taken';
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>

