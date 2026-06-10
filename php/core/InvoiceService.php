<?php
require_once __DIR__ . '/db.php';

class InvoiceService {

    public static function generateNumber(): string {
        return 'INV-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public static function generate(int $checkinId, int $guestId, float $roomCharge, float $serviceCharge, float $discount = 0, float $taxRate = 0.15): array {
        $db = getDB();

        // Check if already exists
        $existing = $db->query("SELECT id FROM invoices WHERE checkin_id = $checkinId")->fetch_assoc();
        if ($existing) {
            // Update existing
            $tax   = ($roomCharge + $serviceCharge - $discount) * $taxRate;
            $total = $roomCharge + $serviceCharge - $discount + $tax;
            $db->query("UPDATE invoices SET room_charge=$roomCharge, service_charge=$serviceCharge,
                        discount=$discount, tax=$tax, total_amount=$total, status='Issued', issued_at=NOW()
                        WHERE id = {$existing['id']}");
            return ['invoice_id' => $existing['id'], 'total' => $total];
        }

        $tax    = ($roomCharge + $serviceCharge - $discount) * $taxRate;
        $total  = $roomCharge + $serviceCharge - $discount + $tax;
        $number = self::generateNumber();

        $stmt = $db->prepare("INSERT INTO invoices (invoice_number, checkin_id, guest_id, room_charge, service_charge, discount, tax, total_amount, status, issued_at) VALUES (?,?,?,?,?,?,?,?,'Issued',NOW())");
        $stmt->bind_param("siidddd d", $number, $checkinId, $guestId, $roomCharge, $serviceCharge, $discount, $tax, $total);

        // Fix: proper bind
        $stmt->close();
        $stmt = $db->prepare("INSERT INTO invoices (invoice_number, checkin_id, guest_id, room_charge, service_charge, discount, tax, total_amount, status, issued_at) VALUES (?,?,?,?,?,?,?,?,'Issued',NOW())");
        $stmt->bind_param("siidddd d", $number, $checkinId, $guestId, $roomCharge, $serviceCharge, $discount, $tax, $total);
        $stmt->close();

        // Direct query to avoid bind issues with floats
        $number = $db->real_escape_string($number);
        $db->query("INSERT INTO invoices (invoice_number, checkin_id, guest_id, room_charge, service_charge, discount, tax, total_amount, status, issued_at)
                    VALUES ('$number', $checkinId, $guestId, $roomCharge, $serviceCharge, $discount, $tax, $total, 'Issued', NOW())");

        $invoiceId = $db->insert_id;
        return ['invoice_id' => $invoiceId, 'invoice_number' => $number, 'total' => $total];
    }

    public static function getBalance(int $invoiceId): float {
        $db = getDB();
        $inv = $db->query("SELECT total_amount FROM invoices WHERE id = $invoiceId")->fetch_assoc();
        if (!$inv) return 0;

        $paid = $db->query("SELECT COALESCE(SUM(amount),0) AS paid FROM payments WHERE invoice_id = $invoiceId AND status = 'Completed'")->fetch_assoc()['paid'];
        return max(0, floatval($inv['total_amount']) - floatval($paid));
    }

    public static function recordPayment(int $invoiceId, float $amount, string $method, string $reference = ''): array {
        $db = getDB();
        $balance = self::getBalance($invoiceId);

        if ($amount <= 0)         return ['error' => 'Payment amount must be greater than zero.'];
        if ($amount > $balance)   return ['error' => "Amount ($amount) exceeds outstanding balance ($balance)."];

        $userId = null; // currentUser()['id'] ?? null
        $ref    = $db->real_escape_string($reference);
        $db->query("INSERT INTO payments (invoice_id, amount, method, reference, status, paid_at) VALUES ($invoiceId, $amount, '$method', '$ref', 'Completed', NOW())");

        $newBalance = self::getBalance($invoiceId);
        $status     = $newBalance <= 0 ? 'Paid' : 'Partially Paid';
        $db->query("UPDATE invoices SET status='$status' WHERE id = $invoiceId");

        return ['success' => true, 'remaining_balance' => $newBalance, 'invoice_status' => $status];
    }
}
