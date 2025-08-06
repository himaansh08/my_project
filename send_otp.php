<?php
include_once(__DIR__ . '/config.php');
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$response = ['success' => false];

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        $response['error'] = 'Invalid request method';
        echo json_encode($response);
        exit;
    }

    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['error'] = 'Invalid CSRF token';
        echo json_encode($response);
        exit;
    }

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($email)) {
        $response['error'] = 'Email is required';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $response['error'] = 'Invalid email format';
        echo json_encode($response);
        exit;
    }

    // Check if email exists in the database
    $stmt = $conn->prepare("SELECT id FROM users_info WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(400);
        $response['error'] = 'No account found with this email address';
        echo json_encode($response);
        exit;
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $_SESSION['forgot_password_otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_expiry'] = $expiry;

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;

    // Recipients
    $mail->setFrom(SMTP_USERNAME, 'Password Reset');
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP for Password Reset';
    $mail->Body = '
        <h3>Password Reset Request</h3>
        <p>Your OTP for password reset is: <strong>' . $otp . '</strong></p>
        <p>This OTP will expire in 1 hour.</p>
        <p>If you didn\'t request this, please ignore this email.</p>
    ';

    $mail->send();

    // Success response

    $response = [
        'success' => true,
        'message' => 'OTP has been sent to your email address'
    ];
    echo json_encode($response);
} catch (Exception $e) {
    // Email sending failed
    http_response_code(500);
    $response['error'] = 'Failed to send email. Please try again later';
    echo json_encode($response);
}
