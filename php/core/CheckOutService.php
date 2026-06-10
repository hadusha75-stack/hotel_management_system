<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/InvoiceService.php';

class CheckOutService {

    public static function process(int $checkinId, array $options = []): array {
        $db = getDB();

        // Load checkin
        $checkin = $db->query("SELECT c.*, r.price_override, rt.base_price
            FROM checkins c
            JOIN rooms r ON c.room_id = r.id
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE c.id = $checkinId")->fetch_assoc();

        if (!$checkin)                         return ['error' => 'Check-in record not found.'];
        if ($checkin['status'] === 'CheckedOut') return ['error' => 'Guest has already checked out.'];

        // Calculate nights
        $checkInDate  = date('Y-m-d', strtotime($checkin['check_in_datetime']));
        $checkOutDate = date('Y-m-d');
        $nights       = max(1, (strtotime($checkOutDate) - strtotime($checkInDate)) / 86400);

        // Room rate
        $pricePerNight = $checkin['price_override'] ?? $checkin['base_price'];
        $roomCharge    = $nights * $pricePerNight;

        // Service charges
        $serviceResult = $db->query("SELECT COALESCE(SUM(total),0) AS total FROM checkin_charges WHERE checkin_id = $checkinId");
        $serviceCharge = floatval($serviceResult->fetch_assoc()['total']);

        // Check for existing invoice
        $existingInvoice = $db->query("SELECT id, status FROM invoices WHERE checkin_id = $checkinId")->fetch_assoc();

        if ($existingInvoice && $existingInvoice['status'] === 'Paid') {
            $invoiceId = $existingInvoice['id'];
        } else {
            // Generate invoice
            $invoiceResult = InvoiceService::generate($checkinId, $checkin['guest_id'], $roomCharge, $serviceCharge);
            if (isset($invoiceResult['error'])) return $invoiceResult;
            $invoiceId = $invoiceResult['invoice_id'];
        }

        // Check unpaid balance (unless manager override)
        $balance = InvoiceService::getBalance($invoiceId);
        if ($balance > 0 && empty($options['force_checkout'])) {
            return [
                'error'      => 'Unpaid balance exists. Complete payment before checkout.',
                'invoice_id' => $invoiceId,
                'balance'    => $balance
            ];
        }

        $userId = currentUser()['id'] ?? null;

        // Record checkout
        $stmt = $db->prepare("INSERT INTO checkouts (checkin_id, invoice_id, checkout_time, nights_stayed, processed_by) VALUES (?,?,NOW(),?,?)");
        $stmt->bind_param("iiii", $checkinId, $invoiceId, $nights, $userId);
        $stmt->execute();
        $stmt->close();

        // Update checkin record
        $db->query("UPDATE checkins SET status='CheckedOut', actual_checkout=NOW() WHERE id = $checkinId");

        // Update reservation
        $db->query("UPDATE reservations SET status='CheckedOut'
                    WHERE id = (SELECT reservation_id FROM checkins WHERE id = $checkinId)");

        // Room → Cleaning (housekeeping task created automatically)
        $db->query("UPDATE rooms SET status='Cleaning', cleaning_status='Dirty' WHERE id = {$checkin['room_id']}");
        $db->query("INSERT INTO housekeeping_tasks (room_id, task_type, status) VALUES ({$checkin['room_id']}, 'Cleaning', 'Pending')");

        auditLog('CHECKOUT', 'checkouts', $checkinId, [], ['nights' => $nights, 'room_charge' => $roomCharge]);

        return [
            'success'       => true,
            'invoice_id'    => $invoiceId,
            'nights_stayed' => $nights,
            'room_charge'   => $roomCharge,
            'service_charge'=> $serviceCharge,
            'total'         => $roomCharge + $serviceCharge
        ];
    }
}
