<?php
/**
 * POST /api/reviews/delete.php
 * Body: { "review_id": 14 }
 *
 * Deletes a review owned by the current user. The spot's aggregate rating
 * is recomputed afterward so display stays in sync.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['review_id']);

$review_id = clean_int($payload['review_id'], 1);
$user_id   = $_SESSION['user_id'];

// ── Verify ownership ────────────────────────────────────────
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

// ── Delete + recompute, in one transaction ──────────────────
try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$review_id]);

    // After deletion, the spot may have 0 reviews — handle that case
    $agg = $pdo->prepare("
        UPDATE spots SET
            rating        = COALESCE(
                (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE spot_id = ?),
                0.0
            ),
            reviews_count = (SELECT COUNT(*) FROM reviews WHERE spot_id = ?)
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
], 'Review deleted.');
