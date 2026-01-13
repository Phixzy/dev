<?php


session_start();
require_once '../config/dbcon.php';

// Message handling
$message = '';
$message_type = '';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update courses from abbreviations to full names
$courses_to_update = [
    'CS' => 'BS Computer Science',
    'IT' => 'BS Information Technology',
    'CE' => 'BS Computer Engineering'
];

$update_count = 0;
$errors = [];

foreach ($courses_to_update as $old_abbrev => $new_name) {
    $sql = "UPDATE subjects SET course = ? WHERE course = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ss", $new_name, $old_abbrev);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $update_count += $affected;
        $stmt->close();
        
        if ($affected > 0) {
            echo "Updated $affected subjects from '$old_abbrev' to '$new_name'<br>";
        }
    } else {
        $errors[] = "Failed to prepare statement for $old_abbrev: " . $conn->error;
    }
}

// Verify the update
$verify_sql = "SELECT DISTINCT course FROM subjects";
$result = $conn->query($verify_sql);

echo "<h2>Course Migration Results</h2>";
echo "<p>Total subjects updated: <strong>$update_count</strong></p>";

if (!empty($errors)) {
    echo "<p style='color: red;'>Errors:</p>";
    foreach ($errors as $error) {
        echo "<p style='color: red;'>- $error</p>";
    }
}

echo "<h3>Current courses in database:</h3>";
echo "<ul>";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['course']) . "</li>";
    }
} else {
    echo "<li>No courses found</li>";
}
echo "</ul>";

// Also check if any old abbreviations remain
$remaining_sql = "SELECT COUNT(*) as count FROM subjects WHERE course IN ('CS', 'IT', 'CE')";
$result = $conn->query($remaining_sql);
$remaining = $result->fetch_assoc()['count'];

if ($remaining > 0) {
    echo "<p style='color: orange;'>Warning: $remaining subjects still have old course abbreviations</p>";
} else {
    echo "<p style='color: green;'>✓ All courses have been successfully migrated to full names!</p>";
}

echo "<p><a href='adminpage.php'>Return to Admin Page</a></p>";

$conn->close();
?>

