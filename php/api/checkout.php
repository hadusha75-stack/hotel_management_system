<?php
/**
 * Check-Out API
 * POST /api/checkout.php  - Process checkout
 *   Body: { checkin_id, force_checkout (optional, manager only) }
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../core/CheckOutService.php';
require_once __DIR__ . '/../auth/guard_manager.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (empty($body['checkin_id'])) jsonError('checkin_id is required.');

$options = [];
// Only manager can force checkout with unpaid balance
if (!empty($body['force_checkout']) && currentUser()['role'] === 'manager')
    $options['force_checkout'] = true;

$result = CheckOutService::process((int)$body['checkin_id'], $options);
if (isset($result['error'])) jsonError($result['error'], 422);
jsonSuccess($result);
