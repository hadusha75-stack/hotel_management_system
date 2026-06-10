<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Invoices & Payments API
 * GET  /api/invoices.php?checkin_id=X   - Get invoice for a checkin
 * GET  /api/invoices.php?id=X           - Get invoice by ID
 * POST /api/invoices.php/payment        - Record a payment
 *   Body: { invoice_id, amount, method, reference }
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/InvoiceService.php';
require_once __DIR__ . '/../auth/guard_manager.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if (isset($_GET['checkin_id'])) {
        $cid = (int)$_GET['checkin_id'];
        $inv = $db->query("SELECT i.*, g.full_name AS guest_name, r.room_number
                           FROM invoices i
                           JOIN guests g ON i.guest_id = g.id
                           JOIN checkins c ON i.checkin_id = c.id
                           JOIN rooms r ON c.room_id = r.id
                           WHERE i.checkin_id = $cid")->fetch_assoc();
        if (!$inv) jsonError('Invoice not found for this check-in.', 404);

        // Add payments
        $payments = [];
        $pRes = $db->query("SELECT * FROM payments WHERE invoice_id = {$inv['id']} ORDER BY paid_at DESC");
        while ($p = $pRes->fetch_assoc()) $payments[] = $p;
        $inv['payments']  = $payments;
        $inv['balance']   = InvoiceService::getBalance($inv['id']);
        jsonSuccess($inv);

    } elseif (isset($_GET['id'])) {
        $id  = (int)$_GET['id'];
        $inv = $db->query("SELECT * FROM invoices WHERE id = $id")->fetch_assoc();
        if (!$inv) jsonError('Invoice not found.', 404);
        $inv['balance'] = InvoiceService::getBalance($id);
        jsonSuccess($inv);
    } else {
        jsonError('Specify ?checkin_id=X or ?id=X');
    }

} elseif ($method === 'POST' && $action === 'payment') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $required = ['invoice_id', 'amount', 'method'];
    foreach ($required as $f) {
        if (empty($body[$f])) jsonError("Field '$f' is required.");
    }
    $allowed_methods = ['Cash', 'Card', 'BankTransfer', 'Mobile'];
    if (!in_array($body['method'], $allowed_methods))
        jsonError('Invalid payment method. Allowed: ' . implode(', ', $allowed_methods));

    $result = InvoiceService::recordPayment(
        (int)$body['invoice_id'],
        (float)$body['amount'],
        $body['method'],
        $body['reference'] ?? ''
    );
    if (isset($result['error'])) jsonError($result['error'], 422);
    jsonSuccess($result);
} else {
    jsonError('Method not allowed.', 405);
}
