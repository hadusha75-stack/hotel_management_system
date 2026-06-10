<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
$conn = getDB();

$username        = trim($_POST["username"]        ?? "");
$email           = trim($_POST["email"]           ?? "");
$address         = trim($_POST["address"]         ?? "");
$password        = trim($_POST["password"]        ?? "");
$security_hint   = trim($_POST["security_hint"]   ?? "");
$security_answer = strtolower(trim($_POST["security_answer"] ?? ""));

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$subfolder = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$base      = $protocol . '://' . $_SERVER['HTTP_HOST'] . $subfolder;

if (empty($username) || empty($email) || empty($password) || empty($security_hint) || empty($security_answer)) {
    echo "<script>alert('All fields including security question are required.'); window.history.back();</script>";
    exit;
}

$stmt = $conn->prepare("INSERT INTO customer_login (username, email, password, address, security_hint, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $username, $email, $password, $address, $security_hint, $security_answer);

if ($stmt->execute()) {
    echo "<script>alert('Successfully signed up! You can now log in.'); window.location.href='$base/html/public/auth.html';</script>";
} else {
    if (str_contains($conn->error ?? '', '1062') || str_contains($stmt->error ?? '', 'duplicate') || str_contains($stmt->error ?? '', 'unique')) {
        echo "<script>alert('This email is already registered. Please log in.'); window.history.back();</script>";
    } else {
        echo "<script>alert('Error. Please try again.'); window.history.back();</script>";
    }
}
?>
