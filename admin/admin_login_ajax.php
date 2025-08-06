<?php
include_once(__DIR__ . '/../config.php'); // Adjust the path to include config.php from the base directory

header('Content-Type: application/json');
$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['success'] = false;
        $response['error'] = 'Invalid CSRF token.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if (empty($email) || empty($password)) {
            $response['success'] = false;
            $response['error'] = 'Both email and password are required.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['success'] = false;
            $response['error'] = 'Invalid email format.';
        } else {
            // Prepare and execute the query to prevent SQL injection
            $stmt = $conn->prepare("SELECT admin_id, password FROM admin_info WHERE email = ? ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $response['success'] = false;
                $response['error'] = 'No admin found with this email address.';
            } else {
                $admin = $result->fetch_assoc();

                // Verify password (assuming passwords are hashed in the database)
                if (!password_verify($password, $admin['password'])) {
                    $response['success'] = false;
                    $response['error'] = 'Incorrect password.';
                } else {
                    // Successful login
                    $_SESSION['admin_id'] = $admin['admin_id'];

                    $response['success'] = true;
                    $response['message'] = 'Login successful!';
                    $response['redirectUrl'] = 'customers.php'; // Redirect to a protected page after login
                }
            }
            $stmt->close();
        }
    }
} else {
    $response['success'] = false;
    $response['error'] = 'Invalid request method.';
}

// Always return HTTP 200 and let the frontend handle success/error based on the response content
http_response_code(200);
echo json_encode($response);
?>