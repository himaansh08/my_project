<?php
include_once(__DIR__ . '/../config.php');
// session_start();
session_destroy();
header("Location: login.php");
exit();
?>