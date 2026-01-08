<?php
// Setup script to create username_resets table
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create username_resets table
$sql = "CREATE TABLE IF NOT EXISTS username_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    new_username VARCHAR(50) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_id (student_id),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table username_resets created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>

