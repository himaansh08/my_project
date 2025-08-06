<?php
include_once(__DIR__ . '/../config.php'); // Adjust the path to include config.php

// Admin details to insert
$email = 'himansh@gmail.com'; // Change this to the desired email
$password = 'himansh'; // Change this to the desired password

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin with this email already exists
    $check_stmt = $conn->prepare("SELECT admin_id FROM admin_info WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "Error: Admin with email '$email' already exists.\n";
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
    
    // Insert new admin record
    $stmt = $conn->prepare("INSERT INTO admin_info (email, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $hashed_password);
    
    if ($stmt->execute()) {
        $admin_a = $conn->insert_id;
        echo "Success: New admin created successfully!\n";
        echo "Admin ID: $admin_id\n";
        echo "Email: $email\n";
        echo "Password: $password (store this securely - it won't be shown again)\n";
    } else {
        echo "Error: Failed to create admin record.\n";
        echo "MySQL Error: " . $stmt->error . "\n";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>