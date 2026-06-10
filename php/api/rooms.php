<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Rooms API
 * GET  /api/rooms.php                   - List all rooms (with filters)
 * GET  /api/rooms.php?id=X              - Get one room
 * GET  /api/rooms.php?available=1&check_in=Y-m-d&check_out=Y-m-d  - Available rooms
 * POST /api/rooms.php                   - Add room (manager only)
 * PUT  /api/rooms.php?id=X              - Update room status/info
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/ReservationService.php';
require_once __DIR__ . '/../auth/guard_manager.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id  = (int)$_GET['id'];
            $row = $db->query("SELECT r.*, rt.name AS type_name, rt.base_price
                               FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id
                               WHERE r.id = $id")->fetch_assoc();
            if (!$row) jsonError('Room not found.', 404);
            jsonSuccess($row);

        } elseif (isset($_GET['available'])) {
            $checkIn  = $db->real_escape_string($_GET['check_in']  ?? date('Y-m-d'));
            $checkOut = $db->real_escape_string($_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day')));
            $bedType  = $db->real_escape_string($_GET['bed_type']  ?? '');
            $acType   = $db->real_escape_string($_GET['ac_type']   ?? '');

            $where = "r.status NOT IN ('Occupied','Maintenance') AND r.is_active = 1";
            if ($bedType) $where .= " AND r.bed_type = '$bedType'";
            if ($acType)  $where .= " AND r.ac_type  = '$acType'";

            $result = $db->query("
                SELECT r.id, r.room_number, r.bed_type, r.ac_type, r.status,
                       rt.name AS type_name,
                       COALESCE(r.price_override, rt.base_price) AS price_per_night
                FROM rooms r
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE $where
                  AND r.id NOT IN (
                      SELECT room_id FROM reservations
                      WHERE status IN ('Confirmed','CheckedIn','Pending')
                        AND check_in_date < '$checkOut'
                        AND check_out_date > '$checkIn'
                  )
                ORDER BY r.room_number
            ");
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            jsonSuccess($rows);

        } else {
            $status = $db->real_escape_string($_GET['status'] ?? '');
            $where  = $status ? "WHERE r.status = '$status'" : "WHERE r.is_active = 1";
            $result = $db->query("SELECT r.*, rt.name AS type_name, COALESCE(r.price_override, rt.base_price) AS effective_price
                                  FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id $where ORDER BY r.room_number");
            $rows = [];
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            jsonSuccess($rows);
        }
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $required = ['room_number','room_type_id','bed_type','ac_type'];
        foreach ($required as $f) if (empty($body[$f])) jsonError("Field '$f' is required.");

        $num    = $db->real_escape_string($body['room_number']);
        $typeId = (int)$body['room_type_id'];
        $bed    = $db->real_escape_string($body['bed_type']);
        $ac     = $db->real_escape_string($body['ac_type']);
        $floor  = (int)($body['floor'] ?? 0);
        $price  = isset($body['price_override']) ? (float)$body['price_override'] : 'NULL';

        $db->query("INSERT INTO rooms (room_number,room_type_id,bed_type,ac_type,floor,price_override) VALUES ('$num',$typeId,'$bed','$ac',$floor,$price)");
        if ($db->errno) jsonError('Failed to add room: ' . $db->error);
        auditLog('ROOM_ADDED', 'rooms', $db->insert_id, [], $body);
        jsonSuccess(['id' => $db->insert_id, 'message' => 'Room added successfully.'], 201);
        break;

    case 'PUT':
        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonError('Room ID required.');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $allowed_statuses = ['Available','Reserved','Occupied','Cleaning','Maintenance'];
        if (isset($body['status']) && !in_array($body['status'], $allowed_statuses))
            jsonError('Invalid status. Allowed: ' . implode(', ', $allowed_statuses));

        $sets = [];
        if (isset($body['status']))   $sets[] = "status='"    . $db->real_escape_string($body['status'])   . "'";
        if (isset($body['notes']))    $sets[] = "notes='"     . $db->real_escape_string($body['notes'])    . "'";
        if (isset($body['cleaning_status'])) $sets[] = "cleaning_status='" . $db->real_escape_string($body['cleaning_status']) . "'";

        if (empty($sets)) jsonError('Nothing to update.');
        $db->query("UPDATE rooms SET " . implode(',', $sets) . " WHERE id = $id");
        auditLog('ROOM_UPDATED', 'rooms', $id, [], $body);
        jsonSuccess(['message' => 'Room updated.']);
        break;

    default:
        jsonError('Method not allowed.', 405);
}
