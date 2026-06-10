<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
// ── Session & Role helpers ────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

function currentUser(): ?array {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function requireRole(array $roles): void {
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles)) {
        if (isApiRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Required role: ' . implode(' or ', $roles)]);
            exit;
        }
        header("location: " . baseUrl() . "html/public/auth.html?error=unauthorized");
        exit;
    }
}

function requireLogin(): void {
    if (!currentUser()) {
        if (isApiRequest()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required.']);
            exit;
        }
        header("location: " . baseUrl() . "html/public/auth.html?error=login_required");
        exit;
    }
}

function isApiRequest(): bool {
    return (
        !empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
        || !empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')
        || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
    );
}

function baseUrl(): string {
    // Adjust if your project is not at /php/
    return '/php/';
}

function auditLog(string $action, string $table = '', int $recordId = 0, array $old = [], array $new = []): void {
    try {
        $db      = getDB();
        $userId  = currentUser()['id'] ?? null;
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $oldJson = empty($old) ? null : json_encode($old);
        $newJson = empty($new) ? null : json_encode($new);
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id,action,table_name,record_id,old_values,new_values,ip_address,user_agent) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issiisss", $userId, $action, $table, $recordId, $oldJson, $newJson, $ip, $ua);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) { /* silently fail — don't break main flow */ }
}
