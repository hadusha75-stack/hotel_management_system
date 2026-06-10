<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
$conn = getDB();
$name       = trim($_POST['name']       ?? '');
$email      = trim($_POST['email']      ?? '');
$experience = trim($_POST['experience'] ?? '');
$message    = trim($_POST['message']    ?? '');

// Validate
if (empty($name) || empty($email) || empty($experience) || empty($message)) {
    header("location: ../../html/public/feedback.html?error=empty");
    exit;
}

$name       = $conn->real_escape_string($name);
$email      = $conn->real_escape_string($email);
$experience = $conn->real_escape_string($experience);
$message    = $conn->real_escape_string($message);

$conn->query("INSERT INTO feedback (name, email, experience, message) VALUES ('$name','$email','$experience','$message')");
// PRG redirect — no "resubmit form" on refresh, shows success screen
header("location: ../../html/public/feedback.html?sent=1");
exit;
?>
