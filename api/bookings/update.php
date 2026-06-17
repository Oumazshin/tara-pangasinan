<?php
/**
 * POST /api/bookings/update.php
 * Body: { 
 *   "booking_id": 42,
 *   "adults": 2,
 *   "children": 1,
 *   "infants": 0,
 *   "contact_name": "Carlo Reyes",
 *   "contact_phone": "+63 917 234 5678",
 *   "requests": "Vegetarian meals please."
 * }
 *
 * Updates an upcoming booking's details and recalculates the total price.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['booking_id', 'adults', 'contact_name']);

$booking_id = clean_int($payload['booking_id'], 1);
$user_id    = $_SESSION['user_id'];

$adults        = clean_int($payload['adults'], 1, 20);
$children      = clean_int($payload['children'] ?? 0, 0, 20);
$infants       = clean_int($payload['infants']  ?? 0, 0, 20);
$contact_name  = clean_str($payload['contact_name']);
$contact_phone = clean_str($payload['contact_phone'] ?? '');
$requests      = clean_str($payload['requests'] ?? '');

if (strlen($contact_name) < 2) {
    json_error('Name is too short.', 400);
}

// 1. Get current booking details
$check = $pdo->prepare("
    SELECT b.id, b.status, b.tour_id, b.promo_code, t.price AS tour_price
    FROM bookings b
    JOIN tours t ON t.id = b.tour_id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$check->execute([$booking_id, $user_id]);
$booking = $check->fetch();

if (!$booking) {
    json_error('Booking not found.', 404);
}
if ($booking['status'] !== 'upcoming') {
    json_error('Only upcoming bookings can be edited.', 400);
}

// 2. Get add-ons for this booking
$a_stmt = $pdo->prepare("SELECT price_charged FROM booking_addons WHERE booking_id = ?");
$a_stmt->execute([$booking_id]);
$addon_prices = $a_stmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Get promo details if applicable
$promo_row = null;
if ($booking['promo_code']) {
    $p_stmt = $pdo->prepare("SELECT type, value FROM promos WHERE code = ?");
    $p_stmt->execute([$booking['promo_code']]);
    $promo_row = $p_stmt->fetch();
}

// 4. Recalculate total
$tour_price = (int) $booking['tour_price'];
$guests     = $adults + $children; // Infants don't pay

$subtotal = $tour_price * $guests;
foreach ($addon_prices as $ap) {
    $subtotal += (int) $ap * $guests;
}

$discount = 0;
if ($promo_row) {
    if ($promo_row['type'] === 'percent') {
        $discount = (int) round($subtotal * ((int) $promo_row['value']) / 100);
    } else { // fixed
        $discount = min((int) $promo_row['value'], $subtotal);
    }
}
$total = max(0, $subtotal - $discount);

// 5. Update the database
$upd = $pdo->prepare("
    UPDATE bookings
    SET adults = ?, children = ?, infants = ?,
        discount = ?, total = ?,
        contact_name = ?, contact_phone = ?, requests = ?
    WHERE id = ?
");
$upd->execute([
    $adults, $children, $infants,
    $discount, $total,
    $contact_name, $contact_phone, $requests,
    $booking_id
]);

json_success([
    'booking_id' => $booking_id,
    'total' => $total
], 'Booking updated successfully.');
