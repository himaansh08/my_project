<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get form data
    $currentPassword = isset($_POST['currentPassword']) ? trim($_POST['currentPassword']) : '';
    $newPassword = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : '';
    $confirmNewPassword = isset($_POST['confirmNewPassword']) ? trim($_POST['confirmNewPassword']) : '';

    // Server-side validation
    $errors = [];

    if (empty($currentPassword)) {
        $errors[] = 'Current password is required';
    }

    if (empty($newPassword)) {
        $errors[] = 'New password is required';
    } else {
        // Validate password strength
        if (strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
            $errors[] = 'Password must contain at least one special character';
        }
    }

    if (empty($confirmNewPassword)) {
        $errors[] = 'Please confirm your new password';
    } elseif ($newPassword !== $confirmNewPassword) {
        $errors[] = 'Passwords do not match';
    }

    if ($currentPassword === $newPassword) {
        $errors[] = 'New password must be different from current password';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Get current password hash from database - FIXED TABLE NAME
    $stmt = $conn->prepare("SELECT password FROM users_info WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'We could not find your account. Please try again later.']);
        exit;
    }


    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password in database - FIXED TABLE NAME
    $stmt = $conn->prepare("UPDATE users_info SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $newPasswordHash, $user_id);

    if ($stmt->execute()) {
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully! Redirecting to profile...'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
    }
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while changing password. Please try again.']);
}
