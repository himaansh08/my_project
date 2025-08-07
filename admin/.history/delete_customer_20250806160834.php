<?php
include_once(__DIR__ . '/../config.php');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Delete query
    $stmt = $conn->prepare("DELETE FROM users_info WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: customers.php?deleted=1");
        exit();
    } else {
        echo "Error deleting customer.";
    }

    $stmt->close();
} else {
    echo "Invalid ID.";
}


?>