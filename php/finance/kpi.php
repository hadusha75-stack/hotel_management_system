<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
ob_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache');
ob_clean();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['finance','manager'])) {
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$conn = getDB();
$isPg = (DB_TYPE === 'pgsql');

// Collected revenue from checked-out guests
$r = $conn->query("SELECT COALESCE(SUM(totalamount),0) AS rev FROM deleted_customers");
$collected_revenue = $r ? floatval($r->fetch_assoc()['rev']) : 0;

// Completed stays count
$r = $conn->query("SELECT COUNT(*) AS cnt FROM deleted_customers");
$completed_stays = $r ? intval($r->fetch_assoc()['cnt']) : 0;

// Active guests & pending revenue (calculated live)
if ($isPg) {
    $r = $conn->query("
        SELECT COUNT(*) AS cnt,
               COALESCE(SUM(
                   priceperday * GREATEST(1, EXTRACT(DAY FROM (CURRENT_DATE - checkin::date)))
               ), 0) AS pending
        FROM customer
        WHERE checkin IS NOT NULL
    ");
} else {
    $r = $conn->query("
        SELECT COUNT(*) AS cnt,
               COALESCE(SUM(priceperday * GREATEST(1, DATEDIFF(CURDATE(), checkin))), 0) AS pending
        FROM customer
        WHERE checkin IS NOT NULL AND checkin != ''
    ");
}
$row = $r ? $r->fetch_assoc() : ['cnt'=>0,'pending'=>0];
$active_guests   = intval($row['cnt']);
$pending_revenue = floatval($row['pending']);

// Room stats
if ($isPg) {
    $r = $conn->query("SELECT COUNT(*) AS total,
                              SUM(CASE WHEN status != 'not booked' THEN 1 ELSE 0 END) AS booked
                       FROM rooms");
} else {
    $r = $conn->query("SELECT COUNT(*) AS total, SUM(status != 'not booked') AS booked FROM rooms");
}
$row = $r ? $r->fetch_assoc() : ['total'=>0,'booked'=>0];
$total_rooms  = intval($row['total']);
$booked_rooms = intval($row['booked']);

echo json_encode([
    'collected_revenue' => $collected_revenue,
    'pending_revenue'   => $pending_revenue,
    'active_guests'     => $active_guests,
    'completed_stays'   => $completed_stays,
    'total_rooms'       => $total_rooms,
    'booked_rooms'      => $booked_rooms,
]);
$conn->close();
?>
