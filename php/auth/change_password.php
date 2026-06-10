<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
ob_clean();

if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'Session expired. Please log in again.']);
    exit;
}

$newPassword = trim($_POST['password'] ?? '');

if (strlen($newPassword) < 8) {
    echo json_encode(['error' => 'Password must be at least 8 characters.']);
    exit;
}

$conn  = getDB();
$email = $_SESSION['email'];

// Use prepared statement so affected_rows works correctly on PostgreSQL
$stmt = $conn->prepare("UPDATE customer_login SET password=?, must_change_password=0 WHERE email=?");
$stmt->bind_param("ss", $newPassword, $email);
$stmt->execute();

// Check if any row was updated
// For PostgreSQL, query the row directly to confirm
$check = $conn->query("SELECT id FROM customer_login WHERE email='" . $conn->real_escape_string($email) . "' LIMIT 1");

if ($check && $check->num_rows > 0) {
    unset($_SESSION['must_change_password']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Could not update password. Please try again.']);
}
?>
