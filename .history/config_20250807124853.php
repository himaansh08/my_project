<?php
session_start();

require 'vendor/autoload.php'; // Load Composer's autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_project_db";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}


define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'himanshsood311@gmail.com');
define('SMTP_PASSWORD', ''); // App password
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'himanshsood311@gmail.com');
define('SMTP_FROM_NAME', 'Oriental Outsourcing');
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);


define('SITE_URL', 'http://localhost/task15/my_page');
define('RESOURCES_URL', SITE_URL . '/resources');
?>