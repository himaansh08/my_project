<?php
include_once(__DIR__ . '/config.php');

header('Content-Type: application/json');

$response = [];

$uploadDir = __DIR__ . '/uploads/profile_pictures/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function handleImageUpload($file, $uploadDir, $userId)
{
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024;

    $fileType = $file['type'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and GIF files are allowed.'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size must be less than 2MB.'];
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'Invalid image file.'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => './uploads/profile_pictures/' . $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload image.'];
    }
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        $response = ['success' => false, 'message' => 'CSRF token missing.'];
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $response = ['success' => false, 'message' => 'Invalid CSRF token.'];
    } else {
        $user_id = intval($_SESSION['user_id']);
        $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
        $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
        $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
        $confirmNewPassword = isset($_POST['confirmNewPassword']) ? $_POST['confirmNewPassword'] : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
        $skills = isset($_POST['skills']) ? trim($_POST['skills']) : '';

        $errors = [];

        // Validate First Name
        if (empty($firstName)) {
            $errors['firstName'] = 'First name is required.';
        } elseif (strlen($firstName) < 2) {
            $errors['firstName'] = 'First name must be at least 2 characters long.';
        } elseif (strlen($firstName) > 50) {
            $errors['firstName'] = 'First name must not exceed 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
            $errors['firstName'] = 'First name must contain only letters and spaces.';
        }

        // Validate Last Name
        if (empty($lastName)) {
            $errors['lastName'] = 'Last name is required.';
        } elseif (strlen($lastName) < 2) {
            $errors['lastName'] = 'Last name must be at least 2 characters long.';
        } elseif (strlen($lastName) > 50) {
            $errors['lastName'] = 'Last name must not exceed 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
            $errors['lastName'] = 'Last name must contain only letters and spaces.';
        }

        // Validate Email
        if (empty($email)) {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (strlen($email) > 255) {
            $errors['email'] = 'Email address is too long.';
        }

        // Validate Phone (optional field)
        if (!empty($phone)) {
            if (!preg_match('/^[\+]?[\d\s\-\(\)]{10,15}$/', $phone)) {
                $errors['phone'] = 'Please enter a valid phone number (10-15 digits).';
            }
        }

        // Validate Date of Birth (optional field)
        if (!empty($dob)) {
            $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$dobDate || $dobDate->format('Y-m-d') !== $dob) {
                $errors['dob'] = 'Please enter a valid date in YYYY-MM-DD format.';
            } else {
                $today = new DateTime();
                $age = $today->diff($dobDate)->y;
                
                if ($dobDate > $today) {
                    $errors['dob'] = 'Date of birth cannot be in the future.';
                } elseif ($age > 120) {
                    $errors['dob'] = 'Please enter a realistic date of birth.';
                } elseif ($age < 13) {
                    $errors['dob'] = 'You must be at least 13 years old.';
                }
            }
        }

        // Validate Skills (optional field)
        if (!empty($skills)) {
            $validSkills = ['PHP', 'HTML', 'CSS', 'JavaScript', 'Python'];
            $userSkills = array_map('trim', explode(',', $skills));
            $userSkills = array_filter($userSkills); // Remove empty values
            
            foreach ($userSkills as $skill) {
                if (!in_array($skill, $validSkills)) {
                    $errors['skills'] = 'Invalid skill selected: ' . htmlspecialchars($skill);
                    break;
                }
            }
            
            // Update skills to be clean comma-separated string
            $skills = implode(',', $userSkills);
        }

        // Validate Current Password
        if (empty($currentPassword)) {
            $errors['currentPassword'] = 'Current password is required to save changes.';
        }

        // Validate New Password (optional)
        if (!empty($newPassword)) {
            $requirements = [];
            if (strlen($newPassword) < 8) $requirements[] = 'at least 8 characters';
            if (!preg_match('/[A-Z]/', $newPassword)) $requirements[] = '1 uppercase letter';
            if (!preg_match('/[a-z]/', $newPassword)) $requirements[] = '1 lowercase letter';
            if (!preg_match('/[0-9]/', $newPassword)) $requirements[] = '1 number';
            if (!preg_match('/[\W_]/', $newPassword)) $requirements[] = '1 special character';

            if (!empty($requirements)) {
                $errors['newPassword'] = 'Password must contain ' . implode(', ', $requirements) . '.';
            }

            if ($newPassword !== $confirmNewPassword) {
                $errors['confirmNewPassword'] = 'New passwords do not match.';
            }
        } elseif (!empty($confirmNewPassword)) {
            $errors['newPassword'] = 'Please enter a new password.';
        }

        // Validate Profile Picture (optional)
        $profilePicturePath = null;
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload($_FILES['profilePicture'], $uploadDir, $user_id);
            if (!$uploadResult['success']) {
                $errors['profilePicture'] = $uploadResult['error'];
            } else {
                $profilePicturePath = $uploadResult['path'];
            }
        }

        // If there are validation errors, return them
        if (!empty($errors)) {
            $response = [
                'success' => false, 
                'message' => 'Please fix the following errors:', 
                'errors' => $errors
            ];
        } else {
            // Get current user data
            $stmt = $conn->prepare("SELECT * FROM users_info WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $response = ['success' => false, 'message' => 'User not found.'];
            } else {
                $user = $result->fetch_assoc();

                // Verify current password
                if (!password_verify($currentPassword, $user['password'])) {
                    $response = [
                        'success' => false,
                        'message' => 'Current password is incorrect.',
                        'errors' => ['currentPassword' => 'Current password is incorrect.']
                    ];
                } else {
                    // Check if email is already taken by another user
                    if ($email !== $user['email']) {
                        $emailCheckStmt = $conn->prepare("SELECT id FROM users_info WHERE email = ? AND id != ?");
                        $emailCheckStmt->bind_param("si", $email, $user_id);
                        $emailCheckStmt->execute();
                        $emailResult = $emailCheckStmt->get_result();
                        
                        if ($emailResult->num_rows > 0) {
                            $response = [
                                'success' => false,
                                'message' => 'Email address is already taken.',
                                'errors' => ['email' => 'This email address is already registered.']
                            ];
                        }
                    }

                    // If no email conflict, proceed with update
                    if (!isset($response['success']) || $response['success'] !== false) {
                        $conn->begin_transaction();
                        try {
                            $password_to_update = !empty($newPassword) ? password_hash($newPassword, PASSWORD_DEFAULT) : $user['password'];

                            if ($profilePicturePath) {
                                // Delete old profile picture
                                if (!empty($user['profile_picture']) && 
                                    $user['profile_picture'] !== 'resources/images/default.png' && 
                                    file_exists(__DIR__ . '/' . $user['profile_picture'])) {
                                    unlink(__DIR__ . '/' . $user['profile_picture']);
                                }

                                $stmt = $conn->prepare("UPDATE users_info SET 
                                    first_name = ?, 
                                    last_name = ?, 
                                    email = ?,
                                    phone = ?, 
                                    password = ?, 
                                    profile_picture = ?, 
                                    dob = ?, 
                                    skills = ?, 
                                    updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = ?");

                                $stmt->bind_param(
                                    "ssssssssi",
                                    $firstName,
                                    $lastName,
                                    $email,
                                    $phone,
                                    $password_to_update,
                                    $profilePicturePath,
                                    $dob,
                                    $skills,
                                    $user_id
                                );
                            } else {
                                $stmt = $conn->prepare("UPDATE users_info SET 
                                    first_name = ?, 
                                    last_name = ?, 
                                    email = ?,
                                    phone = ?, 
                                    password = ?, 
                                    dob = ?, 
                                    skills = ?, 
                                    updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = ?");

                                $stmt->bind_param(
                                    "sssssssi",
                                    $firstName,
                                    $lastName,
                                    $email,
                                    $phone,
                                    $password_to_update,
                                    $dob,
                                    $skills,
                                    $user_id
                                );
                            }

                            if ($stmt->execute()) {
                                $conn->commit();
                                $response = [
                                    'success' => true,
                                    'message' => 'Profile updated successfully!',
                                    'user' => [
                                        'firstName' => $firstName,
                                        'lastName' => $lastName,
                                        'email' => $email,
                                        'phone' => $phone,
                                        'dob' => $dob,
                                        'skills' => $skills,
                                        'profilePicture' => $profilePicturePath ?: $user['profile_picture']
                                    ]
                                ];
                            } else {
                                throw new Exception('Database update failed: ' . $stmt->error);
                            }
                        } catch (Exception $e) {
                            $conn->rollback();
                            // Clean up uploaded file if database update fails
                            if ($profilePicturePath && file_exists(__DIR__ . '/' . $profilePicturePath)) {
                                unlink(__DIR__ . '/' . $profilePicturePath);
                            }
                            $response = [
                                'success' => false,
                                'message' => 'An error occurred while updating your profile. Please try again.'
                            ];
                            error_log("Profile update error for user ID $user_id: " . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
}

echo json_encode($response);