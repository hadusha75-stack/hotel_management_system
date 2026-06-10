<?php
session_start();
session_unset();
session_destroy();
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$subfolder = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$base      = $protocol . '://' . $_SERVER['HTTP_HOST'] . $subfolder;
header("location: $base/html/public/auth.html");
exit;
?>
