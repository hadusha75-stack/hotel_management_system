<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "manager") {
    $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $subfolder = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    $base      = $protocol . '://' . $_SERVER['HTTP_HOST'] . $subfolder;
    header("location: $base/html/public/auth.html?error=unauthorized");
    exit;
}
?>
