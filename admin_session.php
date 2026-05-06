<?php
session_start();
$email_admin = "administrador@gmail.com"; 
if (
    !isset($_SESSION['loggedin']) || 
    $_SESSION['loggedin'] !== true || 
    !isset($_SESSION['email']) ||
    strtolower($_SESSION['email']) !== strtolower($email_admin)
) {
    header("location: login.php");
    exit;
}
?>