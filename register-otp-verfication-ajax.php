<?php
header('Content-Type: application/json');
include_once(__DIR__ . '/config.php');

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $mail->Subject = 'Email Verification - New OTP Code';
        $mail->Body    = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #007bff; text-align: center;'>New Verification Code</h2>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>You requested a new verification code. Here's your new OTP:</p>
                <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                    <h3 style='margin: 0; color: #007bff;'>Your New OTP Code:</h3>
                    <h1 style='font-size: 36px; letter-spacing: 5px; color: #28a745; margin: 10px 0;'>{$otp}</h1>
                </div>
                <p><strong>Important:</strong> This OTP is valid for 10 minutes only.</p>
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
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // CSRF token validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }

    // Check if there's pending registration data
    if (!isset($_SESSION['pending_registration'])) {
        throw new Exception('No pending registration found. Please register again.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'verify_otp') {
        // Verify OTP
        $inputOTP = trim($_POST['otp'] ?? '');
        $pendingData = $_SESSION['pending_registration'];

        // Check if OTP has expired
        if (time() > $pendingData['otp_expires']) {
            throw new Exception('OTP has expired. Please request a new code.');
        }

        // Check if too many attempts
        if ($pendingData['otp_attempts'] >= 5) {
            throw new Exception('Too many failed attempts. Please register again.');
        }

        // Validate OTP format
        if (empty($inputOTP) || !preg_match('/^\d{6}$/', $inputOTP)) {
            throw new Exception('Please enter a valid 6-digit OTP');
        }

        // Check if OTP matches
        if ($inputOTP !== $pendingData['otp']) {
            // Increment attempts
            $_SESSION['pending_registration']['otp_attempts']++;
            $remainingAttempts = 5 - $_SESSION['pending_registration']['otp_attempts'];
            
            if ($remainingAttempts > 0) {
                throw new Exception("Invalid OTP. You have {$remainingAttempts} attempts remaining.");
            } else {
                // Clear session data after max attempts
                unset($_SESSION['pending_registration']);
                throw new Exception('Too many failed attempts. Please register again.');
            }
        }

        // OTP is correct - now create the user account
        $checkEmailQuery = "SELECT COUNT(*) as count FROM users_info WHERE email = ?";
        $stmt = $conn->prepare($checkEmailQuery);

        if (!$stmt) {
            throw new Exception('Database error occurred');
        }

        $stmt->bind_param("s", $pendingData['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            throw new Exception('An account with this email already exists');
        }

        $stmt->close();

        // Insert new user with email verified status
        $insertQuery = "INSERT INTO users_info (first_name, last_name, email, password, is_email_verified, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
        $stmt = $conn->prepare($insertQuery);

        if (!$stmt) {
            throw new Exception('Database error occurred');
        }

        $stmt->bind_param("ssss", 
            $pendingData['firstName'], 
            $pendingData['lastName'], 
            $pendingData['email'], 
            $pendingData['password']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to create account');
        }

        $stmt->close();

        // Clear pending registration data
        unset($_SESSION['pending_registration']);

        // Generate new CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! Your account has been created. Redirecting to login...',
            'redirectUrl' => 'login.php'
        ]);

    } elseif ($action === 'resend_otp') {
        // Resend OTP
        $pendingData = $_SESSION['pending_registration'];
        
        // Generate new OTP
        $newOTP = generateOTP();
        $fullName = $pendingData['firstName'] . ' ' . $pendingData['lastName'];

        // Update session data with new OTP
        $_SESSION['pending_registration']['otp'] = $newOTP;
        $_SESSION['pending_registration']['otp_expires'] = time() + (10 * 60); // 10 minutes
        $_SESSION['pending_registration']['otp_attempts'] = 0; // Reset attempts

        // Send new OTP email
        if (!sendOTPEmail($pendingData['email'], $fullName, $newOTP)) {
            throw new Exception('Failed to send OTP email. Please try again.');
        }

        echo json_encode([
            'success' => true,
            'message' => 'New verification code sent to your email!'
        ]);

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    error_log('OTP Verification Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>