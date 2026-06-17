<?php
/**
 * GET /api/reviews/list.php?spot_id=hundred-islands&limit=10&offset=0
 *
 * Returns reviews for a spot, newest first, paginated.
 * No auth required — reviews are public.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/session.php';   // bootstrap session so we can read $_SESSION['user_id']
require_once __DIR__ . '/../../includes/validation.php';

$spot_id = clean_str($_GET['spot_id'] ?? '');
$limit   = clean_int($_GET['limit']  ?? 10, 1, 50);
$offset  = clean_int($_GET['offset'] ?? 0, 0, 10000);
$rating  = isset($_GET['rating']) ? clean_int($_GET['rating'], 1, 5) : 0;

if (!valid_slug($spot_id)) {
    json_error('Invalid spot_id.', 400);
}

// Confirm spot exists
$exists = $pdo->prepare('SELECT 1 FROM spots WHERE id = ? LIMIT 1');
$exists->execute([$spot_id]);
if (!$exists->fetchColumn()) {
    json_error('Spot not found.', 404);
}

// Total count
if ($rating > 0) {
    $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE spot_id = ? AND rating = ?');
    $count_stmt->execute([$spot_id, $rating]);
} else {
    $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE spot_id = ?');
    $count_stmt->execute([$spot_id]);
}
$total = (int) $count_stmt->fetchColumn();

// Page of reviews — JOIN users for name; flag owner rows so frontend can show edit/delete
$current_user_id = $_SESSION['user_id'] ?? 0;

if ($rating > 0) {
    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.user_id,
            r.rating,
            r.body,
            r.photo_url,
            r.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS user_name
        FROM reviews r
        INNER JOIN users u ON u.id = r.user_id
        WHERE r.spot_id = ? AND r.rating = ?
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $spot_id, PDO::PARAM_STR);
    $stmt->bindValue(2, $rating,  PDO::PARAM_INT);
    $stmt->bindValue(3, $limit,   PDO::PARAM_INT);
    $stmt->bindValue(4, $offset,  PDO::PARAM_INT);
} else {
    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.user_id,
            r.rating,
            r.body,
            r.photo_url,
            r.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS user_name
        FROM reviews r
        INNER JOIN users u ON u.id = r.user_id
        WHERE r.spot_id = ?
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $spot_id, PDO::PARAM_STR);
    $stmt->bindValue(2, $limit,   PDO::PARAM_INT);
    $stmt->bindValue(3, $offset,  PDO::PARAM_INT);
}
$stmt->execute();
$reviews = $stmt->fetchAll();

// Add human-readable date label ("May 2026") + owner flag
foreach ($reviews as &$r) {
    $r['rating']     = (int) $r['rating'];
    $r['is_owner']   = ((int) $r['user_id'] === $current_user_id);
    $r['date_label'] = date('F Y', strtotime($r['created_at']));
    unset($r['user_id']); // don't leak user IDs to the client
}
unset($r);

json_success([
    'reviews'  => $reviews,
    'total'    => $total,
    'has_more' => ($offset + $limit) < $total,
]);
