<?php
session_start();
require_once 'config/dbcon.php';

// Check if admin_users table exists
$table_check_sql = "SHOW TABLES LIKE 'admin_users'";
$result = $conn->query($table_check_sql);

if (!$result || $result->num_rows == 0) {
    // Create the admin_users table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if (!$conn->query($create_table_sql)) {
        die("Error creating table: " . $conn->error);
    }
}

// Check if phix@admin already exists
$check_sql = "SELECT id FROM admin_users WHERE username = ?";
$stmt = $conn->prepare($check_sql);
$username = 'phix@admin';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Admin user 'phix@admin' already exists!";
} else {
    // Insert phix@admin with password 'phixadmin123'
    $password = password_hash('phixadmin123', PASSWORD_DEFAULT);
    $insert_sql = "INSERT INTO admin_users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ss", $username, $password);
    
    if ($stmt->execute()) {
        echo "Success! Admin user 'phix@admin' has been created.";
        echo "<br>You can now login with:<br>";
        echo "<strong>Username:</strong> phix@admin<br>";
        echo "<strong>Password:</strong> phixadmin123";
    } else {
        echo "Error inserting user: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f7fa;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        .error {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        .login-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-user-shield"></i> Admin Setup</h1>
</body>
</html>

