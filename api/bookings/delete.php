<?php
/**
 * POST /api/bookings/delete.php
 * Body: { "booking_id": 42 }
 *
 * Permanently deletes a booking.
 * User can only delete their own bookings, and only if they are cancelled.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['booking_id']);

$booking_id = clean_int($payload['booking_id'], 1);
$user_id    = $_SESSION['user_id'];

// Confirm booking belongs to this user
$check = $pdo->prepare("
    SELECT id, status FROM bookings
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$booking_id, $user_id]);
$row = $check->fetch();

if (!$row) {
    json_error('Booking not found.', 404);
}
if ($row['status'] !== 'cancelled') {
    json_error('Only cancelled bookings can be permanently deleted.', 400);
}

try {
    $pdo->beginTransaction();

    // Delete associated add-ons first (or rely on ON DELETE CASCADE if set, but explicit is safer here)
    $pdo->prepare("DELETE FROM booking_addons WHERE booking_id = ?")->execute([$booking_id]);

    // Delete the booking
    $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$booking_id]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

json_success(['booking_id' => $booking_id], 'Booking permanently deleted.');
