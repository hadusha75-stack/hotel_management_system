<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class ReservationService {

    // ── Generate unique reservation code ─────────────────────
    public static function generateCode(): string {
        return 'ARG-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    // ── Check room availability for dates ────────────────────
    public static function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool {
        $db = getDB();
        $sql = "SELECT COUNT(*) AS cnt FROM reservations
                WHERE room_id = ?
                  AND status IN ('Confirmed','CheckedIn','Pending')
                  AND check_in_date  < ?
                  AND check_out_date > ?";
        $params = [$roomId, $checkOut, $checkIn];
        $types  = "iss";

        if ($excludeReservationId) {
            $sql     .= " AND id != ?";
            $params[] = $excludeReservationId;
            $types   .= "i";
        }

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return intval($row['cnt']) === 0;
    }

    // ── Create reservation ───────────────────────────────────
    public static function create(array $data): array {
        $db = getDB();

        // Validate dates
        if (strtotime($data['check_out_date']) <= strtotime($data['check_in_date']))
            return ['error' => 'Check-out date must be after check-in date.'];

        if (strtotime($data['check_in_date']) < strtotime(date('Y-m-d')))
            return ['error' => 'Check-in date cannot be in the past.'];

        // Check availability
        if (!self::isRoomAvailable($data['room_id'], $data['check_in_date'], $data['check_out_date']))
            return ['error' => 'Room is not available for the selected dates.'];

        // Check room status
        $room = $db->query("SELECT status, price_override, room_type_id FROM rooms WHERE id = {$data['room_id']}")->fetch_assoc();
        if (!$room) return ['error' => 'Room not found.'];
        if (in_array($room['status'], ['Occupied', 'Maintenance']))
            return ['error' => "Room is currently {$room['status']} and cannot be reserved."];

        // Calculate amount
        $nights = (strtotime($data['check_out_date']) - strtotime($data['check_in_date'])) / 86400;
        $price  = $room['price_override']
            ?? $db->query("SELECT base_price FROM room_types WHERE id = {$room['room_type_id']}")->fetch_assoc()['base_price'];
        $total  = $nights * $price;

        $code   = self::generateCode();
        $userId = currentUser()['id'] ?? null;

        $stmt = $db->prepare("INSERT INTO reservations
            (reservation_code, guest_id, room_id, check_in_date, check_out_date, adults, children, status, special_requests, total_amount, source, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $status  = 'Confirmed';
        $adults  = $data['adults']   ?? 1;
        $children= $data['children'] ?? 0;
        $special = $data['special_requests'] ?? '';
        $source  = $data['source']   ?? 'Online';
        $stmt->bind_param("siisssiisdsi",
            $code, $data['guest_id'], $data['room_id'],
            $data['check_in_date'], $data['check_out_date'],
            $adults, $children, $status, $special, $total, $source, $userId
        );

        if (!$stmt->execute()) return ['error' => 'Failed to create reservation: ' . $stmt->error];

        $id = $stmt->insert_id;
        $stmt->close();

        // Update room to Reserved
        $db->query("UPDATE rooms SET status='Reserved' WHERE id = {$data['room_id']}");

        auditLog('RESERVATION_CREATED', 'reservations', $id, [], ['code' => $code]);

        return ['id' => $id, 'reservation_code' => $code, 'total_amount' => $total, 'nights' => $nights];
    }

    // ── Cancel reservation ───────────────────────────────────
    public static function cancel(int $id, string $reason = ''): array {
        $db = getDB();
        $res = $db->query("SELECT * FROM reservations WHERE id = $id")->fetch_assoc();
        if (!$res) return ['error' => 'Reservation not found.'];
        if (in_array($res['status'], ['Cancelled', 'CheckedOut']))
            return ['error' => "Cannot cancel a reservation with status '{$res['status']}'."];
        if ($res['status'] === 'CheckedIn')
            return ['error' => 'Guest is already checked in. Process a checkout instead.'];

        $db->query("UPDATE reservations SET status='Cancelled', cancelled_at=NOW(), cancellation_reason=" . $db->real_escape_string($reason) . " WHERE id=$id");
        // Free the room
        $db->query("UPDATE rooms SET status='Available' WHERE id = {$res['room_id']}");

        auditLog('RESERVATION_CANCELLED', 'reservations', $id, ['status' => $res['status']], ['status' => 'Cancelled', 'reason' => $reason]);
        return ['success' => true, 'message' => 'Reservation cancelled successfully.'];
    }
}
