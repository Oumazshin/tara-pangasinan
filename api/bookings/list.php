<?php
/**
 * GET /api/bookings/list.php
 *
 * Returns all bookings for the current user, newest first.
 * Includes tour info + add-ons for each.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$user_id = $_SESSION['user_id'];

// Main query — bookings + tour info JOIN
// API field names use the friendly aliases (`ref`, `created_at`) that the
// existing frontend expects; DB columns are `reference` and `booked_at`.
$b_stmt = $pdo->prepare("
    SELECT
        b.id,
        b.reference AS ref,
        b.tour_id, b.tour_date, b.tour_time,
        b.adults, b.children, b.infants,
        b.promo_code, b.discount, b.total, b.status,
        b.contact_name  AS name,
        b.contact_email AS email,
        b.contact_phone AS phone,
        b.requests,
        b.booked_at     AS created_at,
        b.cancelled_at,
        t.name     AS tour_name,
        t.image    AS tour_image,
        t.location AS tour_location
    FROM bookings b
    INNER JOIN tours t ON t.id = b.tour_id
    WHERE b.user_id = ?
    ORDER BY b.booked_at DESC
");
$b_stmt->execute([$user_id]);
$bookings = $b_stmt->fetchAll();

if (!$bookings) {
    json_success(['bookings' => []]);
}

// Hydrate add-ons (N+1 prevented with IN clause + JOIN for current name)
$ids = array_map(fn($b) => $b['id'], $bookings);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$a_stmt = $pdo->prepare("
    SELECT ba.booking_id, ba.addon_id, a.name, ba.price_charged AS price
    FROM booking_addons ba
    INNER JOIN addons a ON a.id = ba.addon_id
    WHERE ba.booking_id IN ($placeholders)
");
$a_stmt->execute($ids);
$addons_by_booking = [];
foreach ($a_stmt->fetchAll() as $a) {
    $a['price'] = (int) $a['price'];
    $addons_by_booking[$a['booking_id']][] = $a;
}

// Stitch together + coerce numeric types
foreach ($bookings as &$b) {
    $b['adults']   = (int) $b['adults'];
    $b['children'] = (int) $b['children'];
    $b['infants']  = (int) $b['infants'];
    $b['discount'] = (int) $b['discount'];
    $b['total']    = (int) $b['total'];
    $b['addons']   = $addons_by_booking[$b['id']] ?? [];
}
unset($b);

json_success(['bookings' => $bookings]);
