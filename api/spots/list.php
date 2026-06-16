<?php
/**
 * GET /api/spots/list.php
 *
 * Returns all tourist spots from the database.
 * The response shape exactly matches what data/spots.json used to return,
 * so no rendering changes are needed in the frontend.
 *
 * Query parameters (all optional):
 *   category  — filter by: Nature | Beach | Historical | Festival | Food
 *   search    — substring match on title or location (case-insensitive)
 *   sort      — popular (default) | rating | name
 *
 * Response:
 *   { "success": true, "data": [ { id, title, location, category, rating,
 *     reviews, shortDesc, description, image, lat, lng, hours, entrance,
 *     contact, website, activities[], gallery[], tips[], stats[] }, ... ] }
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

// ── Read and validate filter parameters ──────────────────────────────────────

$category = clean_str($_GET['category'] ?? '', 40);
$search   = clean_str($_GET['search']   ?? '', 100);
$sort     = clean_str($_GET['sort']     ?? 'popular', 20);

$allowedCategories = ['Nature', 'Beach', 'Historical', 'Festival', 'Food'];

$where  = [];
$params = [];

if ($category !== '' && in_array($category, $allowedCategories, true)) {
    $where[]  = 'category = ?';
    $params[] = $category;
}

if ($search !== '') {
    $where[]  = '(title LIKE ? OR location LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// ── Build ORDER BY ────────────────────────────────────────────────────────────

$orderBy = 'reviews_count DESC';                              // default: popular
if ($sort === 'rating') $orderBy = 'rating DESC, reviews_count DESC';
if ($sort === 'name')   $orderBy = 'title ASC';

// ── Fetch spots ───────────────────────────────────────────────────────────────

$sql = 'SELECT id, title, location, category, rating, reviews_count,
               short_desc, description, image, lat, lng,
               hours, entrance, contact, website
        FROM spots';

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ' . $orderBy;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$spots = $stmt->fetchAll();

if (empty($spots)) {
    json_success([]);
}

// ── Fetch sub-tables in 4 queries (avoids N+1) ────────────────────────────────
// Without this pattern, listing 8 spots would run 1 + (8×4) = 33 queries.
// With it, exactly 5 queries are run regardless of how many spots exist.

$ids         = array_column($spots, 'id');
$placeholder = implode(',', array_fill(0, count($ids), '?'));

/**
 * Fetches rows from a sub-table and groups them by spot_id.
 */
function fetchSubTable(PDO $pdo, string $sql, array $ids): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $grouped = [];
    foreach ($stmt->fetchAll() as $row) {
        $grouped[$row['spot_id']][] = $row;
    }
    return $grouped;
}

$activities = fetchSubTable($pdo,
    "SELECT spot_id, activity FROM spot_activities WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order",
    $ids);

$gallery = fetchSubTable($pdo,
    "SELECT spot_id, image_url FROM spot_gallery WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order",
    $ids);

$tips = fetchSubTable($pdo,
    "SELECT spot_id, tip FROM spot_tips WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order",
    $ids);

$stats = fetchSubTable($pdo,
    "SELECT spot_id, label, value FROM spot_stats WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order",
    $ids);

// ── Assemble the final response (key names match the old spots.json shape) ────

$out = [];
foreach ($spots as $s) {
    $id = $s['id'];

    $out[] = [
        'id'          => $id,
        'title'       => $s['title'],
        'location'    => $s['location'],
        'category'    => $s['category'],
        'rating'      => (float) $s['rating'],
        'reviews'     => (int)   $s['reviews_count'],  // "reviews" matches spots.json key
        'shortDesc'   => $s['short_desc'],              // camelCase matches spots.json
        'description' => $s['description'],
        'image'       => $s['image'],
        'lat'         => $s['lat']  !== null ? (float) $s['lat']  : null,
        'lng'         => $s['lng']  !== null ? (float) $s['lng']  : null,
        'hours'       => $s['hours'],
        'entrance'    => $s['entrance'],
        'contact'     => $s['contact'],
        'website'     => $s['website'],
        'activities'  => array_column($activities[$id] ?? [], 'activity'),
        'gallery'     => array_column($gallery[$id]    ?? [], 'image_url'),
        'tips'        => array_column($tips[$id]       ?? [], 'tip'),
        'stats'       => array_map(function ($r) {
            return ['label' => $r['label'], 'value' => $r['value']];
        }, $stats[$id] ?? []),
    ];
}

json_success($out);
