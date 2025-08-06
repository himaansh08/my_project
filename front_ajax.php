<?php
// Clean output buffer to prevent any HTML from mixing with JSON


include_once(__DIR__ . '/config.php');
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Clean any output that might have been generated
ob_clean();

header('Content-Type: application/json'); 
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start session if not already started
  
    
    // CSRF token check
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        ob_clean(); // Clean any output
        http_response_code(400);
        $response['error'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit;
    }

    // Get action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if (empty($action)) {
        ob_clean(); // Clean any output
        http_response_code(400);
        $response['error'] = 'Action parameter is required.';
        echo json_encode($response);
        exit;
    }

    // Handle different actions
    if ($action == 'contact_form') {
        handleContactForm();
    } else if ($action == 'register_user') {
        handleRegisterForm();
    } else {
        ob_clean(); // Clean any output
        http_response_code(400);
        $response['error'] = 'Invalid action.';
        echo json_encode($response);
        exit;
    }

} else {
    ob_clean(); // Clean any output
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
}

// ajax for contact page form 
function handleContactForm() {
    global $conn, $response;
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (empty($name) || empty($email) || empty($message)) {
        ob_clean(); // Clean any output
        http_response_code(400);
        $response['error'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean(); // Clean any output
        http_response_code(400);
        $response['error'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }

    // File validation
    $file = isset($_FILES['file']) ? $_FILES['file'] : null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $allowedExtensions = ['docx', 'pdf', 'xlsx'];
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileMimeType = mime_content_type($file['tmp_name']);
        $allowedMimeTypes = ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            ob_clean(); // Clean any output
            http_response_code(400);
            $response['error'] = 'Invalid file type. Only DOCX, PDF, and XLSX files are allowed.';
            echo json_encode($response);
            exit;
        }

        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            ob_clean(); // Clean any output
            http_response_code(400);
            $response['error'] = 'The uploaded file type is not allowed. Please upload only DOCX, PDF, or XLSX files.';
            echo json_encode($response);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB max file size
            ob_clean(); // Clean any output
            http_response_code(400);
            $response['error'] = 'File size must be less than 5MB.';
            echo json_encode($response);
            exit;
        }
    }

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);

        // Attach file if exists
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $mail->addAttachment($file['tmp_name'], $file['name']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Message from ' . $name;
        $mail->Body = 'Name: ' . $name . '<br>Email: ' . $email . '<br>Message: ' . $message;
        $mail->AltBody = 'Name: ' . $name . "\nEmail: " . $email . "\nMessage: " . $message;

        $mail->send();

        // Save into database
        $stmt = $conn->prepare("INSERT INTO contacts_info (contact_name, contact_email, contact_message, contact_created_on) VALUES (?, ?, ?, ?)");
        $created_on = date('Y-m-d H:i:s');
        $stmt->bind_param("ssss", $name, $email, $message, $created_on);

        if ($stmt->execute()) {
            ob_clean(); // Clean any output
            http_response_code(200);
            $response['message'] = 'Your message has been successfully sent. We will get back to you shortly.';
        } else {
            ob_clean(); // Clean any output
            http_response_code(500);
            $response['error'] = 'Database error: ' . $stmt->error;
        }

        $stmt->close();
        echo json_encode($response);
        
    } catch (Exception $e) {
        ob_clean(); // Clean any output
        http_response_code(500);
        $response['error'] = 'Unexpected error occurred: ' . $e->getMessage();
        echo json_encode($response);
    }
}

// Register user form handler
function handleRegisterForm() {
    global $conn, $response;
    
    try {

        
        // Get and sanitize input data
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        // Server-side validation
        $errors = validateFormData($firstName, $lastName, $email, $password, $confirmPassword);

        if (!empty($errors)) {
            ob_clean(); // Clean any output
            http_response_code(422); // Use 422 for validation errors instead of 400
            $response['success'] = false;
            $response['message'] = 'Please fix the errors and try again';
            $response['errors'] = $errors;
            echo json_encode($response);
            exit;
        }

        // Check if email already exists
        $checkEmailStmt = $conn->prepare("SELECT id, is_email_verified FROM users_info WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $result = $checkEmailStmt->get_result();

        if ($result->num_rows > 0) {
            $existingUser = $result->fetch_assoc();

            if ($existingUser['is_email_verified'] == 1) {
                ob_clean(); // Clean any output
                http_response_code(422); // Use 422 for validation errors
                $response['success'] = false;
                $response['message'] = 'Email already registered and verified. Please login or use a different email address.';
                echo json_encode($response);
                exit;
            } else {
                // Delete unverified account
                $deleteStmt = $conn->prepare("DELETE FROM users_info WHERE id = ?");
                $deleteStmt->bind_param("i", $existingUser['id']);
                $deleteStmt->execute();
                $deleteStmt->close();
            }
        }
        $checkEmailStmt->close();

        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insertStmt = $conn->prepare("INSERT INTO users_info (first_name, last_name, email, password, is_email_verified, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $insertStmt->bind_param("ssss", $firstName, $lastName, $email, $hashedPassword);

        if ($insertStmt->execute()) {
            $userId = $conn->insert_id;
            $otp = sprintf("%06d", mt_rand(100000, 999999));

            // Save session data
            $_SESSION['temp_user_id'] = $userId;
            $_SESSION['temp_otp'] = $otp;
            $_SESSION['temp_email'] = $email;
            $_SESSION['temp_name'] = $firstName . ' ' . $lastName;
            $_SESSION['otp_expiry'] = time() + 300; // 5 minutes

            // Send OTP email
            $emailResult = sendOTPEmail($email, $otp);

            if ($emailResult['sent']) {
                ob_clean(); // Clean any output
                http_response_code(200);
                $response['success'] = true;
                $response['message'] = 'Registration successful! Please check your email for the OTP verification code.';
                $response['redirect'] = 'verify_otp.php';
            } else {
                ob_clean(); // Clean any output
                http_response_code(500);
                $response['success'] = false;
                $response['message'] = 'Error: We could not send the OTP email. Please try again later.';
            }
        } else {
            throw new Exception('Error inserting user data');
        }

        $insertStmt->close();
        ob_clean(); // Clean any output
        echo json_encode($response);

    } catch (Exception $e) {
        ob_clean(); // Clean any output
        http_response_code(500);
        $response['success'] = false;
        $response['message'] = 'Registration failed. Please try again.';
        error_log("Registration Error: " . $e->getMessage());
        echo json_encode($response);
    }
}

// Validation function for registration form
function validateFormData($firstName, $lastName, $email, $password, $confirmPassword) {
    $errors = array();

    // Validate First Name
    if (empty($firstName)) {
        $errors['firstName'] = 'First name is required';
    } elseif (strlen($firstName) < 2) {
        $errors['firstName'] = 'First name must be at least 2 characters';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
        $errors['firstName'] = 'First name can only contain letters and spaces';
    }

    // Validate Last Name
    if (empty($lastName)) {
        $errors['lastName'] = 'Last name is required';
    } elseif (strlen($lastName) < 2) {
        $errors['lastName'] = 'Last name must be at least 2 characters';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
        $errors['lastName'] = 'Last name can only contain letters and spaces';
    }

    // Validate Email
    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($email) > 255) {
        $errors['email'] = 'Email address is too long';
    }

    // Validate Password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must include at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors['password'] = 'Password must include at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must include at least one number';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $errors['password'] = 'Password must include at least one special character';
    }

    // Validate Confirm Password
    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = 'Please confirm your password';
    } elseif ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match';
    }

    return $errors;
}

// Function to send OTP email
function sendOTPEmail($email, $otp) {
    $result = ['sent' => false, 'error' => ''];

    try {
        $mail = new PHPMailer(true);

        // Server settings - using config constants if available, otherwise fallback
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : 'himanshsood311@gmail.com';
        $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : 'zkrj euwx dfnl tdwy';
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'himanshsood311@gmail.com';
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Oriental Outsourcing';
        
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);
        $mail->addReplyTo($fromEmail, $fromName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome! Please verify your email address';

        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Welcome - Email Verification</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #f4f4f4; padding: 20px; border-radius: 10px;">
                <h2 style="color: #2c3e50; text-align: center;">Welcome to ' . $fromName . '!</h2>
                <p>Thank you for registering with us. To complete your registration, please verify your email address using the OTP code below:</p>
                <div style="background: #3498db; color: white; padding: 20px; text-align: center; border-radius: 5px; margin: 20px 0;">
                    <h1 style="margin: 0; font-size: 32px; letter-spacing: 5px;">' . $otp . '</h1>
                </div>
                <p><strong>Important:</strong> This OTP is valid for 5 minutes only.</p>
                <p>If you did not create this account, please ignore this email.</p>
                <hr style="border: 1px solid #eee; margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>';

        $mail->AltBody = "Welcome to $fromName!\n\nYour email verification OTP is: $otp\n\nThis OTP is valid for 5 minutes only.\n\nIf you did not create this account, please ignore this email.";

        $mail->send();
        $result['sent'] = true;

    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        error_log("Email Error: " . $e->getMessage());
    }

    return $result;
}
?>