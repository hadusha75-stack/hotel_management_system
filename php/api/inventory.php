<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
/**
 * Inventory API
 * GET  /api/inventory.php              - List items (with low-stock flag)
 * POST /api/inventory.php              - Add item
 * PUT  /api/inventory.php?id=X         - Stock in / out / adjust
 *   Body: { type: StockIn|StockOut|Adjustment, quantity, reason }
 */
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/response.php';
require_once __DIR__ . '/../auth/guard_manager.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

switch ($method) {
    case 'GET':
        $lowStock = isset($_GET['low_stock']);
        $having   = $lowStock ? 'HAVING quantity <= reorder_level' : '';
        $result   = $db->query("SELECT *, (quantity <= reorder_level) AS is_low_stock
                                FROM inventory_items $having ORDER BY name");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        jsonSuccess($rows);
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($body['name'])) jsonError('name is required.');
        $name    = $db->real_escape_string($body['name']);
        $cat     = $db->real_escape_string($body['category'] ?? '');
        $unit    = $db->real_escape_string($body['unit'] ?? 'Pieces');
        $qty     = (int)($body['quantity'] ?? 0);
        $reorder = (int)($body['reorder_level'] ?? 10);
        $cost    = (float)($body['unit_cost'] ?? 0);
        $db->query("INSERT INTO inventory_items (name,category,unit,quantity,reorder_level,unit_cost) VALUES ('$name','$cat','$unit',$qty,$reorder,$cost)");
        jsonSuccess(['id' => $db->insert_id, 'message' => 'Item added.'], 201);
        break;

    case 'PUT':
        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) jsonError('Item ID required.');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $type = $body['type'] ?? '';
        $qty  = (int)($body['quantity'] ?? 0);
        if (!in_array($type, ['StockIn','StockOut','Adjustment'])) jsonError('Invalid type.');
        if ($qty <= 0) jsonError('Quantity must be positive.');

        $item = $db->query("SELECT quantity FROM inventory_items WHERE id = $id")->fetch_assoc();
        if (!$item) jsonError('Item not found.', 404);

        if ($type === 'StockOut' && $item['quantity'] < $qty)
            jsonError("Insufficient stock. Available: {$item['quantity']}.");

        $delta   = ($type === 'StockOut') ? -$qty : $qty;
        $reason  = $db->real_escape_string($body['reason'] ?? '');
        $userId  = currentUser()['id'] ?? 'NULL';
        $db->query("UPDATE inventory_items SET quantity = quantity + $delta WHERE id = $id");
        $db->query("INSERT INTO inventory_transactions (item_id,type,quantity,reason,done_by) VALUES ($id,'$type',$qty,'$reason',$userId)");

        $newQty = $db->query("SELECT quantity, reorder_level FROM inventory_items WHERE id = $id")->fetch_assoc();
        $alert  = $newQty['quantity'] <= $newQty['reorder_level'];
        jsonSuccess(['new_quantity' => $newQty['quantity'], 'low_stock_alert' => $alert]);
        break;

    default:
        jsonError('Method not allowed.', 405);
}
