<?php
// Test database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dev";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connected successfully!<br>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'student_messages'");
if ($table_check->num_rows > 0) {
    echo "Table 'student_messages' exists!<br>";
} else {
    echo "Table 'student_messages' does NOT exist!<br>";
    exit;
}

// Test insert with simple query first (not prepared statement)
echo "<br>Testing simple INSERT...<br>";
$test_sql = "INSERT INTO student_messages (student_id, student_name, email, subject, category, message, sent_at) VALUES (0, 'Test Name', 'test@test.com', 'Test Subject', 'test', 'Test message', NOW())";

if ($conn->query($test_sql) === TRUE) {
    echo "Simple INSERT successful! New ID: " . $conn->insert_id . "<br>";
} else {
    echo "Simple INSERT failed: " . $conn->error . "<br>";
}

// Now test with prepared statement
echo "<br>Testing prepared statement INSERT...<br>";
$stmt = $conn->prepare("INSERT INTO student_messages (student_id, student_name, email, subject, category, message, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    echo "Prepare failed: " . $conn->error . "<br>";
} else {
    $student_id = 0;
    $name = "Test Name 2";
    $email = "test2@test.com";
    $subject = "Test Subject 2";
    $category = "test";
    $message_text = "Test message 2";
    
    $stmt->bind_param("isssss", $student_id, $name, $email, $subject, $category, $message_text);
    
    if ($stmt->execute()) {
        echo "Prepared statement INSERT successful! New ID: " . $stmt->insert_id . "<br>";
    } else {
        echo "Execute failed: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

$conn->close();
echo "<br>Test completed.";
?>


