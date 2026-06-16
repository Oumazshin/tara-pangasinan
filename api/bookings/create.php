<?php
/**
 * POST /api/bookings/create.php
 *
 * Body:
 * {
 *   "tour_id":   "hundred-islands",
 *   "date":      "2026-07-15",
 *   "time":      "8:00 AM",
 *   "adults":    2,
 *   "children":  1,
 *   "infants":   0,
 *   "addon_ids": ["snorkel", "lunch"],
 *   "promo_code": "TARA10" | null,
 *   "name":      "Carlo Reyes",
 *   "email":     "carlo@example.com",
 *   "phone":     "+63 917 234 5678",
 *   "requests":  "Vegetarian meals please."
 * }
 *
 * Server recomputes the total — never trusts client-side math.
 * Returns the booking reference + full record.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/validation.php';

$payload = read_json_body();
require_fields($payload, ['tour_id', 'date', 'time', 'adults', 'name', 'email']);

// ── Parse + clean inputs ─────────────────────────────────────
$tour_id   = clean_str($payload['tour_id']);
$date      = clean_str($payload['date']);
$time      = clean_str($payload['time']);
$adults    = clean_int($payload['adults'],   1, 20);
$children  = clean_int($payload['children'] ?? 0, 0, 20);
$infants   = clean_int($payload['infants']  ?? 0, 0, 20);
$addon_ids = is_array($payload['addon_ids'] ?? null) ? $payload['addon_ids'] : [];
$promo     = isset($payload['promo_code']) ? strtoupper(trim($payload['promo_code'])) : '';
$name      = clean_str($payload['name']);
$email     = clean_str($payload['email']);
$phone     = clean_str($payload['phone'] ?? '');
$requests  = clean_str($payload['requests'] ?? '');

// User may be a guest (no session)
$user_id = $_SESSION['user_id'] ?? null;

// ── Validation ───────────────────────────────────────────────
if (!valid_slug($tour_id))           json_error('Invalid tour.', 400);
if (!valid_email($email))            json_error('Invalid email address.', 400);
if (strlen($name) < 2)               json_error('Name is too short.', 400);

// Date must be YYYY-MM-DD and at least tomorrow
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_error('Invalid date format.', 400);
}
if (strtotime($date) < strtotime('+1 day midnight')) {
    json_error('Tours must be booked at least one day in advance.', 400);
}

// Time slot must be one of the allowed values
$allowed_times = ['6:00 AM', '8:00 AM', '1:00 PM'];
if (!in_array($time, $allowed_times, true)) {
    json_error('Invalid time slot.', 400);
}

// ── Look up tour ─────────────────────────────────────────────
$tour_stmt = $pdo->prepare("
    SELECT id, name, price, image, location
    FROM tours
    WHERE id = ?
    LIMIT 1
");
$tour_stmt->execute([$tour_id]);
$tour = $tour_stmt->fetch();
if (!$tour) {
    json_error('Tour not found.', 404);
}

// ── Look up add-ons (validates each id is real) ──────────────
$addon_rows = [];
if ($addon_ids) {
    $addon_ids = array_filter(array_map('clean_str', $addon_ids), 'valid_slug');
    if ($addon_ids) {
        $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));
        $a_stmt = $pdo->prepare("
            SELECT id, name, price
            FROM addons
            WHERE id IN ($placeholders)
        ");
        $a_stmt->execute($addon_ids);
        $addon_rows = $a_stmt->fetchAll();
    }
}

// ── Validate promo (if provided) ─────────────────────────────
$promo_row = null;
if ($promo !== '') {
    $p_stmt = $pdo->prepare("
        SELECT code, type, value
        FROM promos
        WHERE code = ?
          AND is_active = 1
          AND (expires_at IS NULL OR expires_at >= CURDATE())
        LIMIT 1
    ");
    $p_stmt->execute([$promo]);
    $promo_row = $p_stmt->fetch();
    // Silently drop invalid promos rather than fail the whole booking;
    // user already passed the validation step in the UI.
}

// ── Compute pricing server-side ──────────────────────────────
$tour_price = (int) $tour['price'];
$guests     = $adults + $children;  // infants don't pay (matches frontend logic)

$subtotal = $tour_price * $guests;
foreach ($addon_rows as $a) {
    $subtotal += (int) $a['price'] * $guests;
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

// ── Generate reference + insert ──────────────────────────────
$reference = 'TPG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

try {
    $pdo->beginTransaction();

    $ins = $pdo->prepare("
        INSERT INTO bookings (
            reference, user_id, tour_id, tour_date, tour_time,
            adults, children, infants,
            promo_code, discount, total, status,
            contact_name, contact_email, contact_phone, requests, booked_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', ?, ?, ?, ?, NOW())
    ");
    $ins->execute([
        $reference, $user_id, $tour_id, $date, $time,
        $adults, $children, $infants,
        $promo_row['code'] ?? null, $discount, $total,
        $name, $email, $phone, $requests
    ]);
    $booking_id = (int) $pdo->lastInsertId();

    // Link add-ons with the price locked in at booking time
    if ($addon_rows) {
        $ba = $pdo->prepare("
            INSERT INTO booking_addons (booking_id, addon_id, price_charged)
            VALUES (?, ?, ?)
        ");
        foreach ($addon_rows as $a) {
            $ba->execute([$booking_id, $a['id'], $a['price']]);
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

// Note: API field is `ref` (matches plan.html DOM expectations);
// DB column is `reference`. We translate at this boundary.
json_success([
    'ref'         => $reference,
    'booking_id'  => $booking_id,
    'tour_name'   => $tour['name'],
    'tour_image'  => $tour['image'],
    'tour_date'   => $date,
    'tour_time'   => $time,
    'guests'      => $guests,
    'subtotal'    => $subtotal,
    'discount'    => $discount,
    'total'       => $total,
    'email'       => $email,
], 'Booking confirmed.');
