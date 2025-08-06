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


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}


$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

try {
    // Server-side validation
    $errors = [];

    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName  = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $dob       = isset($_POST['dob']) ? $_POST['dob'] : '';
    $skills    = isset($_POST['skills']) ? $_POST['skills'] : [];


    // Validate required fields
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    } elseif (strlen($firstName) < 2 || strlen($firstName) > 50) {
        $errors[] = 'First name must be between 2 and 50 characters';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
        $errors[] = 'First name can only contain letters and spaces';
    }

    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    } elseif (strlen($lastName) < 2 || strlen($lastName) > 50) {
        $errors[] = 'Last name must be between 2 and 50 characters';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
        $errors[] = 'Last name can only contain letters and spaces';
    }

    // Validate date of birth
    if (!empty($dob)) {
        $dobDate = DateTimeImmutable::createFromFormat('Y-m-d', $dob);
        if (!$dobDate || $dobDate->format('Y-m-d') !== $dob) {
            $errors[] = 'Invalid date of birth format';
        } else {
            $today = new DateTime();
            $age = $today->diff($dobDate)->y;
            if ($age < 13 || $age > 120) {
                $errors[] = 'Age must be between 13 and 120 years';
            }
        }
    }

    // Validate and process skills - FIXED VERSION
    $skillIds = [];
    if (!empty($skills)) {
        foreach ($skills as $skillId) {
            $skillId = (int)$skillId;
            if ($skillId > 0) {
                $skillIds[] = $skillId;
            }
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    $stmt = $conn->prepare("SELECT profile_picture FROM users_info WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentImage = $result->fetch_assoc()['profile_picture'] ?? null;
    $stmt->close();
    // Handle file upload
    $profileImagePath = null;
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'resources/images/profiles/';

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['profilePhoto']['name']);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower($fileInfo['extension']);

        if (!in_array($extension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG, and GIF files are allowed']);
            exit;
        }

        // Check file size (5MB max)
        if ($_FILES['profilePhoto']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
            exit;
        }

        // Validate file type by checking MIME type
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['profilePhoto']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF images are allowed']);
            exit;
        }



        // Generate unique filename
        $newFileName = 'profile_' . $user_id . '_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['profilePhoto']['tmp_name'], $uploadPath)) {
            $profileImagePath = $uploadPath;

            // Delete old image if it exists and is not the default
            if ($currentImage && $currentImage !== 'resources/images/default.png' && file_exists($currentImage)) {
                unlink($currentImage);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload profile image']);
            exit;
        }
    }

    // Start transaction
    $conn->autocommit(false);

    try {
        // Update user profile
        if ($profileImagePath) {
            $stmt = $conn->prepare("UPDATE users_info SET first_name = ?, last_name = ?, dob = ?, profile_picture = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $firstName, $lastName, $dob, $profileImagePath, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users_info SET first_name = ?, last_name = ?, dob = ? WHERE id = ?");
            $stmt->bind_param("sssi", $firstName, $lastName, $dob, $user_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user profile: " . $stmt->error);
        }
        $stmt->close();

        // Delete existing user skills
        $stmt = $conn->prepare("DELETE FROM user_skills WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete old skills: " . $stmt->error);
        }
        $stmt->close();

        // Insert new skills if any are selected
        if (!empty($skillIds)) {



            // Insert each selected skill with duplicate prevention
            $stmt = $conn->prepare("INSERT IGNORE INTO user_skills (user_id, skill_id) VALUES (?, ?)");
            foreach ($skillIds as $skillId) {
                $stmt->bind_param("ii", $user_id, $skillId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert skill with ID: " . $skillId . " - " . $stmt->error);
                }
            }
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();

        // Update session data
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        if ($profileImagePath) {
            $_SESSION['profile_picture'] = $profileImagePath;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully!',
            'profile_picture' => $profileImagePath ?: ($currentImage ?: 'resources/images/default.png')
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Ensure autocommit is back on
    $conn->autocommit(true);

    // Log the error
    error_log("Profile update error: " . $e->getMessage());

    // Delete uploaded file if it exists and there was an error
    if (isset($profileImagePath) && $profileImagePath && file_exists($profileImagePath)) {
        unlink($profileImagePath);
    }

    echo json_encode(['success' => false, 'message' => 'An error occurred while updating profile. Please try again.']);
}

// Ensure autocommit is back on
$conn->autocommit(true);
