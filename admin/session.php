<?php
// session_dump.php
// session_start();
include_once(__DIR__ . '/../config.php'); // Adjust the path to include config.php from the base directory
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
