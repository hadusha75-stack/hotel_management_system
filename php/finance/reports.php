<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
ob_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache');
ob_clean();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['finance','manager'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in as Finance or Manager.']);
    exit;
}

$conn = getDB();

// Sanitize date inputs
$from = isset($_GET['from']) ? $conn->real_escape_string($_GET['from']) : date('Y-m-01');
$to   = isset($_GET['to'])   ? $conn->real_escape_string($_GET['to'])   : date('Y-m-d');

$isPg = (DB_TYPE === 'pgsql');

// ── 1. Monthly Revenue (last 12 months) ──────────────────────
$monthlyRev = [];
if ($isPg) {
    $sql = "SELECT TO_CHAR(checkout,'YYYY-MM') AS month,
                   SUM(totalamount) AS revenue, COUNT(*) AS guests
            FROM deleted_customers
            WHERE checkout >= CURRENT_DATE - INTERVAL '12 months'
              AND checkout IS NOT NULL
            GROUP BY month ORDER BY month ASC";
} else {
    $sql = "SELECT DATE_FORMAT(checkout,'%Y-%m') AS month,
                   SUM(totalamount) AS revenue, COUNT(*) AS guests
            FROM deleted_customers
            WHERE checkout >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND checkout IS NOT NULL AND checkout != ''
            GROUP BY month ORDER BY month ASC";
}
$r = $conn->query($sql);
if ($r) while ($row = $r->fetch_assoc()) $monthlyRev[] = $row;

// ── 2. Revenue in selected range ─────────────────────────────
$r = $conn->query("
    SELECT COALESCE(SUM(totalamount),0) AS total,
           COUNT(*) AS checkouts,
           COALESCE(AVG(totalamount),0) AS avg_stay,
           COALESCE(AVG(daystayed),0) AS avg_nights
    FROM deleted_customers
    WHERE checkout BETWEEN '$from' AND '$to'
");
$rangeStats = $r ? $r->fetch_assoc() : ['total'=>0,'checkouts'=>0,'avg_stay'=>0,'avg_nights'=>0];

// ── 3. Room occupancy ─────────────────────────────────────────
if ($isPg) {
    $r = $conn->query("SELECT COUNT(*) AS total,
                              SUM(CASE WHEN status != 'not booked' THEN 1 ELSE 0 END) AS booked
                       FROM rooms");
} else {
    $r = $conn->query("SELECT COUNT(*) AS total, SUM(status != 'not booked') AS booked FROM rooms");
}
$rooms = $r ? $r->fetch_assoc() : ['total'=>0,'booked'=>0];

// ── 4. Feedback breakdown ─────────────────────────────────────
$feedback = [];
$r = $conn->query("SELECT experience, COUNT(*) AS cnt FROM feedback GROUP BY experience");
if ($r) while ($row = $r->fetch_assoc()) $feedback[] = $row;

// ── 5. Top rooms by revenue ───────────────────────────────────
$topRooms = [];
$r = $conn->query("
    SELECT roomnumber, roomtype, bedtype,
           SUM(totalamount) AS revenue,
           COUNT(*) AS stays,
           AVG(daystayed) AS avg_nights
    FROM deleted_customers
    GROUP BY roomnumber, roomtype, bedtype
    ORDER BY revenue DESC LIMIT 5
");
if ($r) while ($row = $r->fetch_assoc()) $topRooms[] = $row;

// ── 6. Recent checkouts in range ─────────────────────────────
$recent = [];
$r = $conn->query("
    SELECT name, roomnumber, roomtype, checkin, checkout, daystayed, totalamount
    FROM deleted_customers
    WHERE checkout BETWEEN '$from' AND '$to'
    ORDER BY checkout DESC LIMIT 20
");
if ($r) while ($row = $r->fetch_assoc()) $recent[] = $row;

// ── 7. Active guests & pending revenue ───────────────────────
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
$active = $r ? $r->fetch_assoc() : ['cnt'=>0,'pending'=>0];

echo json_encode([
    'monthly_revenue' => $monthlyRev,
    'range_stats'     => $rangeStats,
    'rooms'           => $rooms,
    'feedback'        => $feedback,
    'top_rooms'       => $topRooms,
    'recent'          => $recent,
    'active'          => $active,
    'range'           => ['from'=>$from,'to'=>$to]
]);
?>
