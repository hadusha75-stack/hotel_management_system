<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class CheckInService {

    // ── Check in from confirmed reservation ──────────────────
    public static function fromReservation(int $reservationId, bool $idVerified = false, string $notes = ''): array {
        $db = getDB();

        $res = $db->query("SELECT * FROM reservations WHERE id = $reservationId")->fetch_assoc();
        if (!$res)               return ['error' => 'Reservation not found.'];
        if ($res['status'] === 'Cancelled') return ['error' => 'Cannot check in a cancelled reservation.'];
        if ($res['status'] === 'NoShow')    return ['error' => 'This reservation was marked as No Show.'];
        if ($res['status'] === 'CheckedIn') return ['error' => 'Guest is already checked in.'];
        if ($res['status'] !== 'Confirmed') return ['error' => 'Only Confirmed reservations can be checked in.'];

        return self::doCheckIn(
            $res['guest_id'], $res['room_id'],
            $res['check_out_date'], $idVerified,
            $reservationId, $notes,
            $res['adults'], $res['children']
        );
    }

    // ── Walk-in check-in (no reservation) ────────────────────
    public static function walkIn(array $data): array {
        $db = getDB();

        // Room must be Available
        $room = $db->query("SELECT status FROM rooms WHERE id = {$data['room_id']}")->fetch_assoc();
        if (!$room) return ['error' => 'Room not found.'];
        if ($room['status'] !== 'Available')
            return ['error' => "Room is currently {$room['status']}. Only Available rooms can be assigned."];

        // Validate check-out date
        if (strtotime($data['expected_checkout']) <= strtotime(date('Y-m-d')))
            return ['error' => 'Expected check-out must be a future date.'];

        return self::doCheckIn(
            $data['guest_id'], $data['room_id'],
            $data['expected_checkout'],
            $data['id_verified'] ?? false,
            null,
            $data['notes'] ?? '',
            $data['adults'] ?? 1,
            $data['children'] ?? 0
        );
    }

    // ── Core check-in logic ───────────────────────────────────
    private static function doCheckIn(
        int $guestId, int $roomId, string $expectedCheckout,
        bool $idVerified, ?int $reservationId, string $notes,
        int $adults, int $children
    ): array {
        $db     = getDB();
        $userId = currentUser()['id'] ?? null;
        $idV    = $idVerified ? 1 : 0;

        $stmt = $db->prepare("INSERT INTO checkins
            (reservation_id, guest_id, room_id, check_in_datetime, expected_checkout, adults, children, id_verified, checked_in_by, notes)
            VALUES (?,?,?,NOW(),?,?,?,?,?,?)");
        $stmt->bind_param("iiisiiiss",
            $reservationId, $guestId, $roomId,
            $expectedCheckout, $adults, $children,
            $idV, $userId, $notes
        );

        if (!$stmt->execute()) return ['error' => 'Check-in failed: ' . $stmt->error];

        $checkinId = $stmt->insert_id;
        $stmt->close();

        // Update room status → Occupied
        $db->query("UPDATE rooms SET status='Occupied', cleaning_status='Dirty' WHERE id = $roomId");

        // Update reservation status
        if ($reservationId)
            $db->query("UPDATE reservations SET status='CheckedIn' WHERE id = $reservationId");

        auditLog('CHECKIN', 'checkins', $checkinId, [], ['room_id' => $roomId, 'guest_id' => $guestId]);

        return ['success' => true, 'checkin_id' => $checkinId];
    }
}
