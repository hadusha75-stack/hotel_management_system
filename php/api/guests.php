<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Guests API
 * GET  /api/guests.php?id=X     - Get guest + stay history
 * GET  /api/guests.php?search=X - Search guests
 * POST /api/guests.php          - Create guest
 * PUT  /api/guests.php?id=X     - Update guest
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../auth/guard_manager.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $id    = (int)$_GET['id'];
            $guest = $db->query("SELECT * FROM guests WHERE id = $id")->fetch_assoc();
            if (!$guest) jsonError('Guest not found.', 404);

            // Stay history
            $history = [];
            $res = $db->query("SELECT ci.check_in_datetime, ci.actual_checkout, ci.status,
                                      r.room_number, rt.name AS room_type, i.total_amount, i.status AS invoice_status
                               FROM checkins ci
                               JOIN rooms r ON ci.room_id = r.id
                               JOIN room_types rt ON r.room_type_id = rt.id
                               LEFT JOIN invoices i ON i.checkin_id = ci.id
                               WHERE ci.guest_id = $id ORDER BY ci.check_in_datetime DESC");
            while ($row = $res->fetch_assoc()) $history[] = $row;
            $guest['stay_history'] = $history;
            $guest['total_stays']  = count($history);
            jsonSuccess($guest);

        } elseif (isset($_GET['search'])) {
            $q    = '%' . $db->real_escape_string($_GET['search']) . '%';
            $res  = $db->query("SELECT id, full_name, email, phone, nationality, id_number
                                FROM guests WHERE full_name LIKE '$q' OR email LIKE '$q' OR id_number LIKE '$q' LIMIT 20");
            $rows = [];
            while ($row = $res->fetch_assoc()) $rows[] = $row;
            jsonSuccess($rows);
        } else {
            $res  = $db->query("SELECT id, full_name, email, phone, nationality, created_at FROM guests ORDER BY created_at DESC LIMIT 50");
            $rows = [];
            while ($row = $res->fetch_assoc()) $rows[] = $row;
            jsonSuccess($rows);
        }
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($body['full_name'])) jsonError('full_name is required.');

        $name  = $db->real_escape_string($body['full_name']);
        $email = $db->real_escape_string($body['email'] ?? '');
        $phone = $db->real_escape_string($body['phone'] ?? '');
        $nat   = $db->real_escape_string($body['nationality'] ?? '');
        $gender= $db->real_escape_string($body['gender'] ?? 'Male');
        $idType= $db->real_escape_string($body['id_type'] ?? 'NationalID');
        $idNum = $db->real_escape_string($body['id_number'] ?? '');
        $addr  = $db->real_escape_string($body['address'] ?? '');

        $db->query("INSERT INTO guests (full_name,email,phone,nationality,gender,id_type,id_number,address) VALUES ('$name','$email','$phone','$nat','$gender','$idType','$idNum','$addr')");
        if ($db->errno) jsonError('Failed to create guest: ' . $db->error);
        $guestId = $db->insert_id;
        auditLog('GUEST_CREATED', 'guests', $guestId, [], $body);
        jsonSuccess(['id' => $guestId, 'message' => 'Guest created.'], 201);
        break;

    case 'PUT':
        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonError('Guest ID required.');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $sets = [];
        $fields = ['full_name','email','phone','nationality','gender','id_type','id_number','address'];
        foreach ($fields as $f) {
            if (isset($body[$f])) $sets[] = "$f='" . $db->real_escape_string($body[$f]) . "'";
        }
        if (empty($sets)) jsonError('Nothing to update.');
        $db->query("UPDATE guests SET " . implode(',', $sets) . " WHERE id = $id");
        auditLog('GUEST_UPDATED', 'guests', $id, [], $body);
        jsonSuccess(['message' => 'Guest updated.']);
        break;

    default:
        jsonError('Method not allowed.', 405);
}
