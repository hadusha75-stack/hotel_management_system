<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Housekeeping API
 * GET  /api/housekeeping.php            - Get all tasks
 * POST /api/housekeeping.php            - Create task
 * PUT  /api/housekeeping.php?id=X       - Update task status / assign
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/HousekeepingService.php';
require_once __DIR__ . '/../auth/guard_staff.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $tasks = HousekeepingService::getPendingTasks();
        jsonSuccess($tasks);
        break;

    case 'POST':
        $body   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $roomId = (int)($body['room_id'] ?? 0);
        if (!$roomId) jsonError('room_id is required.');
        $type = $body['task_type'] ?? 'Cleaning';
        $notes= getDB()->real_escape_string($body['notes'] ?? '');
        getDB()->query("INSERT INTO housekeeping_tasks (room_id, task_type, notes) VALUES ($roomId, '$type', '$notes')");
        jsonSuccess(['id' => getDB()->insert_id, 'message' => 'Task created.'], 201);
        break;

    case 'PUT':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonError('Task ID required.');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        if (isset($body['assigned_to'])) {
            $r = HousekeepingService::assign($id, (int)$body['assigned_to']);
            jsonSuccess($r);
        } elseif (isset($body['status'])) {
            $r = HousekeepingService::updateStatus($id, $body['status']);
            if (isset($r['error'])) jsonError($r['error']);
            jsonSuccess($r);
        } else {
            jsonError('Provide status or assigned_to.');
        }
        break;

    default:
        jsonError('Method not allowed.', 405);
}
