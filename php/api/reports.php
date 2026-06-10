<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Reports API
 * GET /api/reports.php?type=daily_revenue&date=Y-m-d
 * GET /api/reports.php?type=monthly_revenue&year=2025&month=6
 * GET /api/reports.php?type=occupancy&date=Y-m-d
 * GET /api/reports.php?type=guest_stats
 * GET /api/reports.php?type=reservation_stats
 * GET /api/reports.php?type=inventory_report
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../auth/guard_finance.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonError('Method not allowed.', 405);

$db   = getDB();
$type = $_GET['type'] ?? '';

switch ($type) {

    // ── Daily Revenue ────────────────────────────────────────
    case 'daily_revenue':
        $date = $db->real_escape_string($_GET['date'] ?? date('Y-m-d'));
        $row  = $db->query("
            SELECT
                COUNT(DISTINCT co.id)          AS total_checkouts,
                COALESCE(SUM(i.total_amount),0) AS gross_revenue,
                COALESCE(SUM(p.amount),0)       AS collected,
                COALESCE(SUM(i.total_amount),0) - COALESCE(SUM(p.amount),0) AS outstanding
            FROM checkouts co
            JOIN invoices i  ON co.invoice_id = i.id
            LEFT JOIN payments p ON p.invoice_id = i.id AND p.status = 'Completed'
            WHERE DATE(co.checkout_time) = '$date'
        ")->fetch_assoc();
        jsonSuccess(['date' => $date, 'report' => $row]);

    // ── Monthly Revenue ──────────────────────────────────────
    case 'monthly_revenue':
        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        $rows  = [];
        $res   = $db->query("
            SELECT
                DATE(co.checkout_time)          AS date,
                COUNT(co.id)                    AS checkouts,
                COALESCE(SUM(i.total_amount),0) AS revenue
            FROM checkouts co
            JOIN invoices i ON co.invoice_id = i.id
            WHERE YEAR(co.checkout_time) = $year AND MONTH(co.checkout_time) = $month
            GROUP BY DATE(co.checkout_time)
            ORDER BY date ASC
        ");
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $total = array_sum(array_column($rows, 'revenue'));
        jsonSuccess(['year' => $year, 'month' => $month, 'total_revenue' => $total, 'daily_breakdown' => $rows]);

    // ── Occupancy Rate ───────────────────────────────────────
    case 'occupancy':
        $date  = $db->real_escape_string($_GET['date'] ?? date('Y-m-d'));
        $total = $db->query("SELECT COUNT(*) AS cnt FROM rooms WHERE is_active = 1")->fetch_assoc()['cnt'];
        $occ   = $db->query("SELECT COUNT(*) AS cnt FROM rooms WHERE status = 'Occupied'")->fetch_assoc()['cnt'];
        $rate  = $total > 0 ? round(($occ / $total) * 100, 2) : 0;
        $breakdown = [];
        $res   = $db->query("SELECT rt.name, COUNT(r.id) AS total,
                                    SUM(r.status='Occupied') AS occupied,
                                    SUM(r.status='Available') AS available,
                                    SUM(r.status='Cleaning') AS cleaning,
                                    SUM(r.status='Maintenance') AS maintenance
                             FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id
                             WHERE r.is_active = 1 GROUP BY rt.name");
        while ($row = $res->fetch_assoc()) $breakdown[] = $row;
        jsonSuccess(['date' => $date, 'total_rooms' => $total, 'occupied' => $occ, 'occupancy_rate' => $rate, 'breakdown' => $breakdown]);

    // ── Guest Statistics ─────────────────────────────────────
    case 'guest_stats':
        $stats = $db->query("
            SELECT
                (SELECT COUNT(*) FROM guests)                                AS total_guests,
                (SELECT COUNT(*) FROM checkins WHERE status='Active')        AS currently_staying,
                (SELECT COUNT(*) FROM guests WHERE is_vip = 1)              AS vip_guests,
                (SELECT AVG(nights) FROM (
                    SELECT DATEDIFF(COALESCE(actual_checkout, NOW()), check_in_datetime) AS nights
                    FROM checkins) AS t)                                     AS avg_stay_nights,
                (SELECT COUNT(*) FROM guests WHERE nationality = 'Ethiopian') AS ethiopian_guests
        ")->fetch_assoc();
        // Nationality breakdown
        $nat = [];
        $res = $db->query("SELECT nationality, COUNT(*) AS cnt FROM guests WHERE nationality != '' GROUP BY nationality ORDER BY cnt DESC LIMIT 10");
        while ($row = $res->fetch_assoc()) $nat[] = $row;
        $stats['nationality_breakdown'] = $nat;
        jsonSuccess($stats);

    // ── Reservation Statistics ───────────────────────────────
    case 'reservation_stats':
        $from = $db->real_escape_string($_GET['from'] ?? date('Y-m-01'));
        $to   = $db->real_escape_string($_GET['to']   ?? date('Y-m-d'));
        $res  = $db->query("
            SELECT
                status,
                COUNT(*) AS count,
                COALESCE(SUM(total_amount),0) AS total_value
            FROM reservations
            WHERE DATE(created_at) BETWEEN '$from' AND '$to'
            GROUP BY status
        ");
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        jsonSuccess(['from' => $from, 'to' => $to, 'by_status' => $rows]);

    // ── Inventory Report ─────────────────────────────────────
    case 'inventory_report':
        $rows = [];
        $res  = $db->query("
            SELECT i.*,
                   (i.quantity <= i.reorder_level) AS low_stock,
                   (SELECT COALESCE(SUM(quantity),0) FROM inventory_transactions
                    WHERE item_id = i.id AND type = 'StockOut'
                      AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS used_last_30_days
            FROM inventory_items i
            ORDER BY low_stock DESC, name ASC
        ");
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $lowCount = count(array_filter($rows, fn($r) => $r['low_stock']));
        jsonSuccess(['total_items' => count($rows), 'low_stock_count' => $lowCount, 'items' => $rows]);

    default:
        jsonError('Invalid report type. Available: daily_revenue, monthly_revenue, occupancy, guest_stats, reservation_stats, inventory_report');
}
