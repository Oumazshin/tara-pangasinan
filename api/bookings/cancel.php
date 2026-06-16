<?php
/**
 * POST /api/bookings/cancel.php
 * Body: { "booking_id": 42 }
 *
 * Sets a booking's status to 'cancelled' and stamps cancelled_at.
 * User can only cancel their own bookings.
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

// Confirm booking belongs to this user (otherwise: 404, not 403,
// to avoid leaking that the ID exists for someone else)
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
if ($row['status'] === 'cancelled') {
    json_error('Booking already cancelled.', 409);
}
if ($row['status'] === 'completed') {
    json_error('Cannot cancel a completed tour.', 400);
}

$pdo->prepare("
    UPDATE bookings
    SET status = 'cancelled', cancelled_at = NOW()
    WHERE id = ?
")->execute([$booking_id]);

json_success(['booking_id' => $booking_id], 'Booking cancelled.');
