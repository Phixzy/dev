<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Set charset for proper character encoding
$conn->set_charset("utf8mb4");
?>
