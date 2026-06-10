<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
ob_start();

header('Content-Type: application/json');
ob_clean();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager','finance'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized.']);
    exit;
}

$conn = getDB();
// Get the current max feedback ID
$res = $conn->query("SELECT MAX(id) as max_id FROM feedback");
$row = $res ? $res->fetch_assoc() : null;
$maxId = $row ? (int)$row['max_id'] : 0;

// Store in cookie for 30 days
setcookie('fb_last_seen', $maxId, time() + (30 * 24 * 60 * 60), '/');

echo json_encode(['ok' => true, 'last_seen' => $maxId]);
?>
