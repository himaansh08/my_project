<?php
include_once(__DIR__ . '/config.php');

header('Content-Type: application/json');
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['error'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit;
    }
    
    // Get email from session instead of POST data
    $email = isset($_SESSION['otp_email']) ? trim($_SESSION['otp_email']) : '';
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    // Check if email exists in session
    if (empty($email)) {
        $response['error'] = 'Session expired. Please restart the password reset process.';
        echo json_encode($response);
        exit;
    }

    if (empty($otp)) {
        $response['error'] = 'OTP is required.';
        echo json_encode($response);
        exit;
    }

    // Check if OTP is set in session
    if (!isset($_SESSION['forgot_password_otp']) || !isset($_SESSION['otp_email'])) {
        $response['error'] = 'OTP not set or session expired.';
        echo json_encode($response);
        exit;
    }

    // Check if OTP matches
    if ($_SESSION['forgot_password_otp'] != $otp) {
        $response['error'] = 'Invalid OTP.';
        echo json_encode($response);
        exit;
    }

    // OTP is valid
    $_SESSION['otp_verified'] = true;
    $response['success'] = true;
    $response['message'] = 'OTP verified successfully. Please proceed to reset your password.';
    echo json_encode($response);


} else {
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
}
?>