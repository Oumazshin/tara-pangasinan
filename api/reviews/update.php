<?php
/**
 * POST /api/reviews/update.php
 * Body: { "review_id": 14, "rating": 4, "body": "Updated text..." }
 *
 * Updates a review owned by the current user. The spot's aggregate rating
 * is recomputed in the same transaction so display stays in sync.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['review_id', 'rating', 'body']);

$review_id = clean_int($payload['review_id'], 1);
$rating    = clean_int($payload['rating'], 1, 5);
$body      = clean_str($payload['body']);
$user_id   = $_SESSION['user_id'];

// ── Validation ───────────────────────────────────────────────
$body_len = mb_strlen($body);
if ($body_len < 10) {
    json_error('Review must be at least 10 characters.', 400);
}
if ($body_len > 1000) {
    json_error('Review cannot exceed 1000 characters.', 400);
}

// ── Verify ownership (404 not 403 to avoid ID enumeration) ──
$check = $pdo->prepare("
    SELECT id, spot_id FROM reviews
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$review_id, $user_id]);
$row = $check->fetch();
if (!$row) {
    json_error('Review not found.', 404);
}
$spot_id = $row['spot_id'];

// ── Update + recompute aggregate, in one transaction ────────
try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare("
        UPDATE reviews
        SET rating = ?, body = ?
        WHERE id = ?
    ");
    $upd->execute([$rating, $body, $review_id]);

    $agg = $pdo->prepare("
        UPDATE spots SET
            rating        = (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE spot_id = ?),
            reviews_count = (SELECT COUNT(*)              FROM reviews WHERE spot_id = ?)
        WHERE id = ?
    ");
    $agg->execute([$spot_id, $spot_id, $spot_id]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

$new = $pdo->prepare('SELECT rating, reviews_count FROM spots WHERE id = ?');
$new->execute([$spot_id]);
$updated = $new->fetch();

json_success([
    'review_id'     => $review_id,
    'new_rating'    => (float) $updated['rating'],
    'reviews_count' => (int)   $updated['reviews_count'],
], 'Review updated.');
