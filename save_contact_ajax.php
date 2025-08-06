<?php
include_once(__DIR__ . '/config.php');
require 'vendor/autoload.php'; // Load Composer's autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
$response = []; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(400);
        $response['error'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit;
    }
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        $response['error'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
            http_response_code(400);
            $response['error'] = 'Invalid file type. Only DOCX, PDF, and XLSX files are allowed.';
            echo json_encode($response);
            exit;
        }

        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            http_response_code(400);
            $response['error'] = 'Invalid mime type for the uploaded file.';
            echo json_encode($response);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB max file size
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
        $mail->SMTPDebug = 0;                      // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;                   // TCP port to connect to

        // Recipients
        $mail->setFrom(SMTP_USERNAME, 'Mailer');
        $mail->addAddress('contact@orientaloutsourcing.com', 'Oriental Outsourcing'); // Add a recipient

        // Attach file if exists
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $mail->addAttachment($file['tmp_name'], $file['name']);
        }

        // Content
        $mail->isHTML(true);                      // Set email format to HTML
        $mail->Subject = 'New Contact Message from ' . $name;
        $mail->Body    = 'Name: ' . $name . '<br>Email: ' . $email . '<br>Message: ' . $message;
        $mail->AltBody = 'Name: ' . $name . "\nEmail: " . $email . "\nMessage: " . $message;

        $mail->send();

        // Save into database
        $qry = "INSERT INTO contacts_info (contact_name, contact_email, contact_message, contact_created_on) VALUES ('".$conn->real_escape_string($name)."', '".$conn->real_escape_string($email)."', '".$conn->real_escape_string($message)."', '".date('Y-m-d H:i:s')."' )";
        $result = $conn->query($qry);

        http_response_code(200);
        $response['message'] = 'Your message has been successfully sent. We will get back to you shortly.';
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        $response['error'] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        echo json_encode($response);
    }
} else {
    http_response_code(405);
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
}
?>