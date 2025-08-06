<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
include_once(__DIR__ . '/config.php');

// Function to validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 255;
}

// Function to validate name
function isValidName($name) {
    return !empty($name) && strlen($name) >= 2 && preg_match('/^[a-zA-Z\s]+$/', $name);
}

// Function to validate password
function isValidPassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password) &&
           preg_match('/[\W_]/', $password);
}

// Function to generate OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send OTP email
function sendOTPEmail($email, $name, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - OTP Code';
        $mail->Body    = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #007bff; text-align: center;'>Email Verification Required</h2>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>Thank you for registering with us! To complete your registration, please verify your email address.</p>
                <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                    <h3 style='margin: 0; color: #007bff;'>Your OTP Code:</h3>
                    <h1 style='font-size: 36px; letter-spacing: 5px; color: #28a745; margin: 10px 0;'>{$otp}</h1>
                </div>
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This OTP is valid for 10 minutes only</li>
                    <li>Do not share this code with anyone</li>
                    <li>If you didn't request this, please ignore this email</li>
                </ul>
                <p>Best regards,<br><strong>Oriental Outsourcing Team</strong></p>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Start session if not already started
    

    // CSRF token validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token. Please refresh the page and try again.');
    }

    // Check if action is register_user
    if (empty($_POST['action']) || $_POST['action'] !== 'register_user') {
        throw new Exception('Invalid action');
    }

    // Get and sanitize input data
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Server-side validation
    $errors = [];

    // Validate first name
    if (empty($firstName)) {
        $errors['firstName'] = 'First name is required';
    } elseif (!isValidName($firstName)) {
        $errors['firstName'] = 'First name must be at least 2 characters and contain only letters';
    }

    // Validate last name
    if (empty($lastName)) {
        $errors['lastName'] = 'Last name is required';
    } elseif (!isValidName($lastName)) {
        $errors['lastName'] = 'Last name must be at least 2 characters and contain only letters';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!isValidEmail($email)) {
        $errors['email'] = 'Invalid email format';
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (!isValidPassword($password)) {
        $errors['password'] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
    }

    // Validate confirm password
    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = 'Confirm password is required';
    } elseif ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match';
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }

    // Check database connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Check if email already exists
    $checkEmailQuery = "SELECT COUNT(*) as count FROM users_info WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);

    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param("s", $email);
    
    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'An account with this email already exists'
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    // Generate OTP
    $otp = generateOTP();
    $fullName = $firstName . ' ' . $lastName;

    // Store user data and OTP in session (temporary storage)
    $_SESSION['pending_registration'] = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'otp' => $otp,
        'otp_expires' => time() + (10 * 60), // 10 minutes
        'otp_attempts' => 0
    ];

    // Send OTP email
    if (!sendOTPEmail($email, $fullName, $otp)) {
        throw new Exception('Failed to send OTP email. Please try again.');
    }

    // Generate new CSRF token for security
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please check your email for the OTP verification code.',
        'redirectUrl' => 'register-otp-verification.php'
    ]);

} catch (Exception $e) {
    // Log the error (in production, log to file instead)
    error_log('Registration error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>