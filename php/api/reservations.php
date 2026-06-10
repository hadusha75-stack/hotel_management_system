<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Reservations API
 * GET    /api/reservations.php?id=X          - Get one
 * GET    /api/reservations.php               - List all
 * POST   /api/reservations.php               - Create
 * PUT    /api/reservations.php?id=X          - Update
 * DELETE /api/reservations.php?id=X          - Cancel
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/ReservationService.php';
require_once __DIR__ . '/../auth/guard_manager.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$db     = getDB();

switch ($method) {

    case 'GET':
        if ($id) {
            $row = $db->query("SELECT r.*, g.full_name AS guest_name, rm.room_number
                               FROM reservations r
                               JOIN guests g ON r.guest_id = g.id
                               JOIN rooms rm ON r.room_id = rm.id
                               WHERE r.id = $id")->fetch_assoc();
            if (!$row) jsonError('Reservation not found.', 404);
            jsonSuccess($row);
        } else {
            $status = $db->real_escape_string($_GET['status'] ?? '');
            $where  = $status ? "WHERE r.status = '$status'" : '';
            $result = $db->query("SELECT r.id, r.reservation_code, r.check_in_date, r.check_out_date,
                                         r.status, r.total_amount, g.full_name AS guest_name, rm.room_number
                                  FROM reservations r
                                  JOIN guests g ON r.guest_id = g.id
                                  JOIN rooms rm ON r.room_id = rm.id
                                  $where ORDER BY r.created_at DESC LIMIT 100");
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            jsonSuccess($rows);
        }
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $required = ['guest_id','room_id','check_in_date','check_out_date'];
        foreach ($required as $f) {
            if (empty($body[$f])) jsonError("Field '$f' is required.");
        }
        $result = ReservationService::create($body);
        if (isset($result['error'])) jsonError($result['error']);
        jsonSuccess($result, 201);
        break;

    case 'PUT':
        if (!$id) jsonError('Reservation ID required.');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        // Only allow updating special_requests and dates if still Pending/Confirmed
        $res = $db->query("SELECT status FROM reservations WHERE id = $id")->fetch_assoc();
        if (!$res) jsonError('Not found.', 404);
        if (in_array($res['status'], ['CheckedIn','CheckedOut','Cancelled']))
            jsonError("Cannot modify a reservation with status '{$res['status']}'.");

        $special = $db->real_escape_string($body['special_requests'] ?? '');
        $db->query("UPDATE reservations SET special_requests='$special', updated_at=NOW() WHERE id = $id");
        auditLog('RESERVATION_UPDATED', 'reservations', $id);
        jsonSuccess(['message' => 'Reservation updated.']);
        break;

    case 'DELETE':
        if (!$id) jsonError('Reservation ID required.');
        $reason = $_GET['reason'] ?? '';
        $result = ReservationService::cancel($id, $reason);
        if (isset($result['error'])) jsonError($result['error']);
        jsonSuccess($result);
        break;

    default:
        jsonError('Method not allowed.', 405);
}
