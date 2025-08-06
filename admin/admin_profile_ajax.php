<?php
include_once(__DIR__ . '/../config.php');

header('Content-Type: application/json');
// Add at the beginning of the file after headers
error_log("AJAX called with action: " . ($_POST['action'] ?? 'none'));
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['error'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit;
    }

    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($action == 'update_profile') {
        $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $homeaddress = isset($_POST['homeaddress']) ? trim($_POST['homeaddress']) : '';
        $profileImage = isset($_FILES['profile_image']['name']) && !empty($_FILES['profile_image']['name']) ? $_FILES['profile_image'] : null;

        $errors = []; // Initialize errors array

        // Validate input
        if (empty($firstName)) {
            $errors[] = 'First Name is required.';
        }
        if (empty($lastName)) {
            $errors[] = 'Last Name is required.';
        }
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        } else {
            // Check if the new email is already used by another admin
            $userId = $_SESSION['admin_id'];
            $emailCheckQry = "SELECT COUNT(*) as count FROM `admin_info` WHERE `email` = ? AND `admin_id` != ?";
            $stmt = $conn->prepare($emailCheckQry);
            $stmt->bind_param('si', $email, $userId);
            $stmt->execute();
            $emailCheckResult = $stmt->get_result();
            $emailCheck = $emailCheckResult->fetch_assoc();
            $stmt->close();
            
            if ($emailCheck['count'] > 0) {
                $errors[] = 'Email address is already in use by another admin.';
            }
        }
        
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = 'Invalid phone number format.';
        }
        
        if ($profileImage != null) {
            $validImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($profileImage['type'], $validImageTypes)) {
                $errors[] = 'Invalid image type. Only JPG, PNG, and GIF are allowed.';
            }
            if ($profileImage['size'] > 2 * 1024 * 1024) { // 2MB
                $errors[] = 'Image size exceeds 2MB.';
            }
        }

        if (empty($errors)) {
            // No validation errors, proceed to update the profile
            $userId = $_SESSION['admin_id'];

            // Get current profile image path
            $currentImageQry = "SELECT profile_image FROM `admin_info` WHERE admin_id = ?";
            $stmt = $conn->prepare($currentImageQry);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $currentImagePath = $user['profile_image'] ?? '';
            $stmt->close();

            // Prepare update query
            $updateFields = [];
            $updateValues = [];
            $updateTypes = '';

            $updateFields[] = 'first_name = ?';
            $updateValues[] = $firstName;
            $updateTypes .= 's';

            $updateFields[] = 'last_name = ?';
            $updateValues[] = $lastName;
            $updateTypes .= 's';

            $updateFields[] = 'email = ?';
            $updateValues[] = $email;
            $updateTypes .= 's';

            $updateFields[] = 'phone = ?';
            $updateValues[] = $phone;
            $updateTypes .= 's';

            $updateFields[] = 'homeaddress = ?';
            $updateValues[] = $homeaddress;
            $updateTypes .= 's';

            if ($profileImage) {
                // Create upload directory if it doesn't exist
                $uploadDir = __DIR__ . '/uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Generate unique filename and save directly in uploads folder
                $ext = pathinfo($profileImage["name"], PATHINFO_EXTENSION);
                $newFileName = uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($profileImage["tmp_name"], $targetFile)) {
                    // Delete old image file if it exists and is not default
                    if (!empty($currentImagePath) && $currentImagePath !== 'uploads/default.png') {
                        $oldImagePath = __DIR__ . '/' . $currentImagePath;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $updateFields[] = 'profile_image = ?';
                    $updateValues[] = 'uploads/' . $newFileName;
                    $updateTypes .= 's';
                } else {
                    $response['error'] = 'Failed to upload profile image.';
                    echo json_encode($response);
                    exit;
                }
            }

            // Add user ID to the end
            $updateValues[] = $userId;
            $updateTypes .= 'i';

            $updateQry = "UPDATE `admin_info` SET " . implode(', ', $updateFields) . " WHERE `admin_id` = ?";
            $stmt = $conn->prepare($updateQry);
            $stmt->bind_param($updateTypes, ...$updateValues);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Profile updated successfully!';
            } else {
                $response['error'] = 'Failed to update profile. Please try again later.';
            }
            $stmt->close();
        } else {
            $response['error'] = implode('<br>', $errors);
        }

        echo json_encode($response);
        exit;
    }

    if ($action == 'update_password') {
        $currentPassword = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
        $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        $confirmNewPassword = isset($_POST['confirm_new_password']) ? trim($_POST['confirm_new_password']) : '';

        $errors = [];

        // Check if any password field is filled
        $passwordFieldsFilled = !empty($currentPassword) || !empty($newPassword) || !empty($confirmNewPassword);

        if ($passwordFieldsFilled) {
            // If any password field is filled, all three must be filled
            if (empty($currentPassword)) {
                $errors[] = 'Current Password is required.';
            }
            if (empty($newPassword)) {
                $errors[] = 'New Password is required.';
            }
            if (empty($confirmNewPassword)) {
                $errors[] = 'Confirm New Password is required.';
            } else if ($newPassword !== $confirmNewPassword) {
                $errors[] = 'Passwords do not match.';
            }
        }

        if (empty($errors)) {
            if ($passwordFieldsFilled) {
                $userId = $_SESSION['admin_id'];
                $qry = "SELECT `password` FROM `admin_info` WHERE `admin_id` = ?";
                $stmt = $conn->prepare($qry);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if (!password_verify($currentPassword, $user['password'])) {
                    $errors[] = 'Incorrect current password.';
                } else {
                    $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $updateQry = "UPDATE `admin_info` SET `password` = ? WHERE `admin_id` = ?";
                    $stmt = $conn->prepare($updateQry);
                    $stmt->bind_param('si', $hashedNewPassword, $userId);

                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Password updated successfully!';
                    } else {
                        $response['error'] = 'Failed to update password. Please try again later.';
                    }
                    $stmt->close();
                }
            }
        }

        if (!empty($errors)) {
            $response['error'] = implode('<br>', $errors);
        }

        echo json_encode($response);
        exit;
    }
} else {
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}
?>