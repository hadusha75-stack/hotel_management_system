<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
ob_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache');
ob_clean();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager','finance'])) {
    echo json_encode(['count' => 0, 'items' => []]);
    exit;
}

$conn = getDB();
// Last-seen feedback ID is stored in a cookie (set by mark_seen.php)
$lastSeen = isset($_COOKIE['fb_last_seen']) ? (int)$_COOKIE['fb_last_seen'] : 0;

// Count unseen feedbacks
$countSql = "SELECT COUNT(*) as cnt FROM feedback WHERE id > $lastSeen";
$countRes = $conn->query($countSql);
$count    = $countRes ? (int)$countRes->fetch_assoc()['cnt'] : 0;

// Fetch latest 5 for the preview list
$previewSql = "SELECT id, name, experience, message, created_at
               FROM feedback
               WHERE id > $lastSeen
               ORDER BY id DESC
               LIMIT 5";
$previewRes = $conn->query($previewSql);
$items = [];
if ($previewRes) {
    while ($row = $previewRes->fetch_assoc()) {
        $items[] = [
            'id'         => (int)$row['id'],
            'name'       => htmlspecialchars($row['name']),
            'experience' => htmlspecialchars($row['experience']),
            'message'    => htmlspecialchars(mb_substr($row['message'], 0, 60)) . (mb_strlen($row['message']) > 60 ? '…' : ''),
            'time'       => $row['created_at'],
        ];
    }
}

echo json_encode(['count' => $count, 'items' => $items]);
?>
