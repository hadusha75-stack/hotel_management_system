<?php
// ── JSON API response helpers ─────────────────────────────────
function jsonSuccess(array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function jsonError(string $message, int $code = 400, array $errors = []): void {
    http_response_code($code);
    header('Content-Type: application/json');
    $body = ['success' => false, 'message' => $message];
    if (!empty($errors)) $body['errors'] = $errors;
    echo json_encode($body);
    exit;
}

function jsonPaginated(array $items, int $total, int $page, int $perPage): void {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data'    => $items,
        'meta'    => [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage)
        ]
    ]);
    exit;
}
