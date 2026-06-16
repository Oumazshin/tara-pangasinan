<?php
/**
 * GET /api/spots/tours.php
 *
 * Returns active tours, add-ons, and fixed time slots in one payload.
 * Plan.html renders the wizard from this response.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';

// Tours
$tours_stmt = $pdo->query("
    SELECT id, name, location, duration, price, image,
           badge, badge_color, rating, reviews_count,
           meeting_point, includes
    FROM tours
    ORDER BY price ASC
");
$tours = $tours_stmt->fetchAll();

// Decode the `includes` JSON text column for each tour
foreach ($tours as &$t) {
    $t['price']         = (int)   $t['price'];
    $t['rating']        = (float) $t['rating'];
    $t['reviews_count'] = (int)   $t['reviews_count'];
    $t['includes']      = json_decode($t['includes'] ?? '[]', true) ?: [];
}
unset($t);

// Add-ons
$addons_stmt = $pdo->query("
    SELECT id, name, description, price
    FROM addons
    ORDER BY price ASC
");
$addons = $addons_stmt->fetchAll();
foreach ($addons as &$a) {
    $a['price'] = (int) $a['price'];
}
unset($a);

// Time slots — small enough to ship as a static config block
$time_slots = [
    ['value' => '6:00 AM', 'label' => 'Sunrise Tour',   'icon' => '🌅'],
    ['value' => '8:00 AM', 'label' => 'Morning Tour',   'icon' => '☀️'],
    ['value' => '1:00 PM', 'label' => 'Afternoon Tour', 'icon' => '🌤️'],
];

json_success([
    'tours'      => $tours,
    'addons'     => $addons,
    'time_slots' => $time_slots,
]);
