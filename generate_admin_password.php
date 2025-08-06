<?php
// Define the password you want to hash
$password = '12345678';

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Output the hashed password
echo "Original Password: " . $password . "<br>";
echo "Hashed Password: " . $hashedPassword . "<br>";

// Optionally, you can verify the hashed password later
if (password_verify($password, $hashedPassword)) {
    echo "Password is valid!";
} else {
    echo "Password is invalid.";
}
?>
