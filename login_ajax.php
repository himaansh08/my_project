<?php
include_once(__DIR__ . '/config.php');

header('Content-Type: application/json');
$response = [];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      
        $response['error'] = 'Invalid CSRF token.';
    } else {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if (empty($email) || empty($password)) {
            
            $response['error'] = 'Both email and password are required.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
           
            $response['error'] = 'Invalid email format.';
        } else {
            // Validate user credentials (example using a simple database check)
            $stmt = $conn->prepare("SELECT * FROM users_info WHERE email = ? AND is_email_verified = 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();


            if ($result->num_rows === 0) {
              
                $response['error'] = 'Invalid Credentials.';
            } else {
                $user = $result->fetch_assoc();

                // Verify password (assuming passwords are hashed in the database)
                if (!password_verify($password, $user['password'])) {
                
                    $response['error'] = 'Incorrect password.';
                } else {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];


                    $response['success'] = true;
                    $response['message'] = 'Login successful!';
                    $response['redirectUrl'] = 'profile.php'; // Redirect to a protected page after login
                }
            }
        }
    }
} else {
  
    $response['error'] = 'Invalid request method.';
}

echo json_encode($response);
