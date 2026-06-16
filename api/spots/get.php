<?php
/**
 * GET /api/spots/get.php?id=<slug>
 *
 * Returns a single tourist spot by its slug ID.
 * Used by details.html when it needs server-fetched spot data.
 *
 * Query parameter:
 *   id  — the spot slug, e.g. "hundred-islands"
 *
 * Success response:
 *   { "success": true, "data": { id, title, ..., activities[], gallery[], tips[], stats[] } }
 *
 * Error codes: invalid_id (422) | not_found (404)
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

$id = clean_str($_GET['id'] ?? '', 80);

if ($id === '' || !valid_slug($id)) {
    json_error('A valid spot id is required.', 'invalid_id', 422);
}

// ── Fetch the spot ────────────────────────────────────────────────────────────

$stmt = $pdo->prepare('
    SELECT id, title, location, category, rating, reviews_count,
           short_desc, description, image, lat, lng,
           hours, entrance, contact, website
    FROM spots
    WHERE id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    json_error('Destination not found.', 'not_found', 404);
}

// ── Fetch sub-tables for this single spot ─────────────────────────────────────

function fetchForSpot(PDO $pdo, string $sql, string $id): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

$activities = array_column(
    fetchForSpot($pdo, 'SELECT activity FROM spot_activities WHERE spot_id = ? ORDER BY sort_order', $id),
    'activity'
);
$gallery = array_column(
    fetchForSpot($pdo, 'SELECT image_url FROM spot_gallery WHERE spot_id = ? ORDER BY sort_order', $id),
    'image_url'
);
$tips = array_column(
    fetchForSpot($pdo, 'SELECT tip FROM spot_tips WHERE spot_id = ? ORDER BY sort_order', $id),
    'tip'
);
$stats = array_map(
    function ($r) { return ['label' => $r['label'], 'value' => $r['value']]; },
    fetchForSpot($pdo, 'SELECT label, value FROM spot_stats WHERE spot_id = ? ORDER BY sort_order', $id)
);

// ── Build response (same key shape as spots.json and list.php) ────────────────

json_success([
    'id'          => $s['id'],
    'title'       => $s['title'],
    'location'    => $s['location'],
    'category'    => $s['category'],
    'rating'      => (float) $s['rating'],
    'reviews'     => (int)   $s['reviews_count'],
    'shortDesc'   => $s['short_desc'],
    'description' => $s['description'],
    'image'       => $s['image'],
    'lat'         => $s['lat']  !== null ? (float) $s['lat']  : null,
    'lng'         => $s['lng']  !== null ? (float) $s['lng']  : null,
    'hours'       => $s['hours'],
    'entrance'    => $s['entrance'],
    'contact'     => $s['contact'],
    'website'     => $s['website'],
    'activities'  => $activities,
    'gallery'     => $gallery,
    'tips'        => $tips,
    'stats'       => $stats,
]);
