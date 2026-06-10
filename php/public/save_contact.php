<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
$conn = getDB();
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    header("location: ../../html/public/contact.html?error=empty");
    exit;
}

$name    = $conn->real_escape_string($name);
$email   = $conn->real_escape_string($email);
$message = $conn->real_escape_string($message);

$conn->query("INSERT INTO contact_messages (name, email, message) VALUES ('$name','$email','$message')");
// PRG redirect — shows success state on contact page
header("location: ../../html/public/contact.html?sent=1");
exit;
?>
