<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Check-In API
 * POST /api/checkin.php                     - Check in (from reservation or walk-in)
 * GET  /api/checkin.php?id=X                - Get checkin details
 * GET  /api/checkin.php?active=1            - Get all active checkins
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/CheckInService.php';
require_once __DIR__ . '/../auth/guard_manager.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id  = (int)$_GET['id'];
            $row = $db->query("
                SELECT ci.*, g.full_name AS guest_name, g.email, g.phone,
                       r.room_number, rt.name AS room_type
                FROM checkins ci
                JOIN guests g  ON ci.guest_id = g.id
                JOIN rooms r   ON ci.room_id  = r.id
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE ci.id = $id
            ")->fetch_assoc();
            if (!$row) jsonError('Check-in not found.', 404);
            jsonSuccess($row);
        } elseif (isset($_GET['active'])) {
            $result = $db->query("
                SELECT ci.id, ci.check_in_datetime, ci.expected_checkout,
                       g.full_name AS guest_name, r.room_number, rt.name AS room_type
                FROM checkins ci
                JOIN guests g  ON ci.guest_id = g.id
                JOIN rooms r   ON ci.room_id  = r.id
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE ci.status = 'Active'
                ORDER BY ci.check_in_datetime DESC
            ");
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            jsonSuccess($rows);
        } else {
            jsonError('Specify ?id=X or ?active=1');
        }
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $type = $body['type'] ?? 'reservation'; // 'reservation' or 'walkin'

        if ($type === 'reservation') {
            if (empty($body['reservation_id'])) jsonError('reservation_id is required.');
            $result = CheckInService::fromReservation(
                (int)$body['reservation_id'],
                !empty($body['id_verified']),
                $body['notes'] ?? ''
            );
        } else {
            // Walk-in
            $required = ['guest_id', 'room_id', 'expected_checkout'];
            foreach ($required as $f) {
                if (empty($body[$f])) jsonError("Field '$f' is required for walk-in check-in.");
            }
            $result = CheckInService::walkIn($body);
        }

        if (isset($result['error'])) jsonError($result['error']);
        jsonSuccess($result, 201);
        break;

    default:
        jsonError('Method not allowed.', 405);
}
