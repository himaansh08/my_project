<?php
include_once(__DIR__ . '/config.php');

// Load Composer's autoloader
require 'vendor/autoload.php';

header('Content-Type: application/json');
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['error'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit;
    }

    // Check if user is authorized (OTP verified)
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {

        $response['error'] = 'Unauthorized access. Please verify OTP first.';
        echo json_encode($response);
        exit;
    }

    // Get email from SESSION instead of POST
    $email = isset($_SESSION['otp_email']) ? trim($_SESSION['otp_email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Check if email exists in session
    if (empty($email)) {

        $response['error'] = 'Session expired. Please restart the password reset process.';
        echo json_encode($response);
        exit;
    }

    // Check password fields
    if (empty($password) || empty($confirm_password)) {;
        $response['error'] = 'Password fields are required.';
        echo json_encode($response);
        exit;
    }

    // Validate password length
    if (strlen($password) < 8) {
        $response['error'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }

    if (!preg_match('/(?=.*[0-9])(?=.*[a-zA-Z])/', $password)) {
        http_response_code(400);
        $response['error'] = 'Password must contain at least one letter and one number.';
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirm_password) {
        $response['error'] = 'Passwords do not match.';
        echo json_encode($response);
        exit;
    }

    // Check if email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users_info WHERE email = ?");
    $stmt->bind_param("s", $email); // "s" means string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['error'] = 'We couldn’t find an account with that email address.';
        echo json_encode($response);
        exit;
    }

    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Update the password in the database
    $qry = "UPDATE users_info SET password = '" . $conn->real_escape_string($hashed_password) . "' WHERE email = '" . $conn->real_escape_string($email) . "'";
    if ($conn->query($qry)) {
        // Clear session data after successful password update
        unset($_SESSION['forgot_password_otp']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_verified']);

        $response['message'] = 'Password updated successfully.';
        $response['success'] = true;
        echo json_encode($response);
    } else {
       
        $response['error'] = 'There was an error updating the password. Please try again later.';
        echo json_encode($response);
    }
} else {
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
}
