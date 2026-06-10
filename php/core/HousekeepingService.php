<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class HousekeepingService {

    // ── Get all pending tasks ─────────────────────────────────
    public static function getPendingTasks(): array {
        $db = getDB();
        $result = $db->query("
            SELECT ht.*, r.room_number, r.status AS room_status,
                   u.full_name AS assigned_to_name
            FROM housekeeping_tasks ht
            JOIN rooms r ON ht.room_id = r.id
            LEFT JOIN users u ON ht.assigned_to = u.id
            WHERE ht.status != 'Done'
            ORDER BY ht.created_at ASC
        ");
        $tasks = [];
        while ($row = $result->fetch_assoc()) $tasks[] = $row;
        return $tasks;
    }

    // ── Update task status ────────────────────────────────────
    public static function updateStatus(int $taskId, string $status): array {
        $db = getDB();
        $task = $db->query("SELECT * FROM housekeeping_tasks WHERE id = $taskId")->fetch_assoc();
        if (!$task) return ['error' => 'Task not found.'];

        $allowed = ['Pending', 'InProgress', 'Done'];
        if (!in_array($status, $allowed))
            return ['error' => 'Invalid status. Must be: ' . implode(', ', $allowed)];

        $now = $status === 'InProgress' ? ', started_at=NOW()' : ($status === 'Done' ? ', completed_at=NOW()' : '');
        $db->query("UPDATE housekeeping_tasks SET status='$status' $now WHERE id = $taskId");

        // When Done → set room to Available & Clean
        if ($status === 'Done') {
            $isPg = defined('DB_TYPE') && DB_TYPE === 'pgsql';
            $col  = $isPg ? '"cleanDerty"' : 'cleanDerty';
            $db->query("UPDATE rooms SET status='Available', $col='Clean' WHERE id = {$task['room_id']}");
            auditLog('HOUSEKEEPING_DONE', 'housekeeping_tasks', $taskId, ['status' => $task['status']], ['status' => 'Done']);
        }

        return ['success' => true, 'room_id' => $task['room_id'], 'new_status' => $status];
    }

    // ── Assign task to staff ──────────────────────────────────
    public static function assign(int $taskId, int $staffUserId): array {
        $db = getDB();
        $db->query("UPDATE housekeeping_tasks SET assigned_to = $staffUserId WHERE id = $taskId");
        return ['success' => true];
    }
}
