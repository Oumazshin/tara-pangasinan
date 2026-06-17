<?php
/**
 * POST /api/reviews/create.php
 * Body: { "spot_id": "hundred-islands", "rating": 5, "body": "..." }
 *
 * Requires login. Inserts one review and recomputes spot's avg rating + count.
 * If user already reviewed this spot, returns 409 Conflict (UNIQUE constraint).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login(); // 401 if not authenticated

$payload = $_POST;
require_fields($payload, ['spot_id', 'rating', 'body']);

$spot_id = clean_str($payload['spot_id']);
$rating  = clean_int($payload['rating'], 1, 5);
$body    = clean_str($payload['body']);
$user_id = $_SESSION['user_id'];

// ── Photo Upload ──────────────────────────────────────────────
$photo_url = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['photo'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        json_error('Error uploading photo.', 400);
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        json_error('Photo must be less than 5MB.', 400);
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed_mimes[$mime])) {
        json_error('Photo must be a JPG, PNG, or WEBP image.', 400);
    }
    
    $ext = $allowed_mimes[$mime];
    $filename = 'rev_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    
    $upload_dir = __DIR__ . '/../../assets/images/reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $target_path = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        json_error('Failed to save uploaded photo.', 500);
    }
    $photo_url = 'assets/images/reviews/' . $filename;
}

// ── Validation ────────────────────────────────────────────────
if (!valid_slug($spot_id)) {
    json_error('Invalid spot_id.', 400);
}
$body_len = mb_strlen($body);
if ($body_len < 10) {
    json_error('Review must be at least 10 characters.', 400);
}
if ($body_len > 1000) {
    json_error('Review cannot exceed 1000 characters.', 400);
}

// ── Verify spot exists ───────────────────────────────────────
$exists = $pdo->prepare('SELECT 1 FROM spots WHERE id = ? LIMIT 1');
$exists->execute([$spot_id]);
if (!$exists->fetchColumn()) {
    json_error('Spot not found.', 404);
}

// ── Insert + recompute, wrapped in a transaction ─────────────
try {
    $pdo->beginTransaction();

    // Insert review (UNIQUE(user_id, spot_id) will throw if duplicate)
    $ins = $pdo->prepare("
        INSERT INTO reviews (user_id, spot_id, rating, body, photo_url, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $ins->execute([$user_id, $spot_id, $rating, $body, $photo_url]);
    $review_id = (int) $pdo->lastInsertId();

    // Recompute aggregate stats on the spot
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

    // 23000 = integrity constraint violation; 1062 = duplicate entry
    if ($e->getCode() === '23000') {
        json_error('You have already reviewed this spot.', 409);
    }
    throw $e; // re-throw; handled by global handler in db.php
}

// Fetch new totals so frontend can update stars without re-fetching
$new = $pdo->prepare('SELECT rating, reviews_count FROM spots WHERE id = ?');
$new->execute([$spot_id]);
$updated = $new->fetch();

json_success([
    'review_id'     => $review_id,
    'new_rating'    => (float) $updated['rating'],
    'reviews_count' => (int)   $updated['reviews_count'],
], 'Review posted successfully.');
