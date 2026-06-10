<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
session_start();

$conn = getDB();

$email    = trim($_POST["email"]    ?? "");
$password = trim($_POST["password"] ?? "");

// Build base URL including subfolder (works on localhost/php/ and sabawyanhotel.xo.je)
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$subfolder = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$base      = $protocol . '://' . $_SERVER['HTTP_HOST'] . $subfolder;

if (empty($email) || empty($password)) {
    header("location: $base/html/public/auth.html?error=empty");
    exit;
}

// ── Staff roles ───────────────────────────────────────────────
if ($email === "manager@sabawyan.com" && $password === "manager123") {
    $_SESSION["role"]  = "manager";
    $_SESSION["email"] = $email;
    $_SESSION["name"]  = "Manager";
    header("location: $base/html/dashboards/manager.html");
    exit;
}

if ($email === "finance@sabawyan.com" && $password === "finance123") {
    $_SESSION["role"]  = "finance";
    $_SESSION["email"] = $email;
    $_SESSION["name"]  = "Finance Officer";
    header("location: $base/html/dashboards/finance.html");
    exit;
}

if ($email === "staff@sabawyan.com" && $password === "staff123") {
    $_SESSION["role"]  = "staff";
    $_SESSION["email"] = $email;
    $_SESSION["name"]  = "Staff";
    header("location: $base/php/staff/housekeeping.php");
    exit;
}

// ── Guest login from DB ───────────────────────────────────────
$stmt = $conn->prepare("SELECT username, email, must_change_password FROM customer_login WHERE email=? AND password=?");

if (!$stmt) {
    header("location: $base/html/public/auth.html?error=db");
    exit;
}

$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION["role"]  = "guest";
    $_SESSION["email"] = $row["email"];
    $_SESSION["name"]  = $row["username"];

    if (!empty($row["must_change_password"])) {
        $_SESSION["must_change_password"] = true;
        header("location: $base/html/public/change_password.html");
        exit;
    }

    header("location: $base/php/public/rooms.php");
    exit;
} else {
    header("location: $base/html/public/auth.html?error=invalid");
    exit;
}
?>
