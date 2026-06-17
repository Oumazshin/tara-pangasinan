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

$payload = $_POST;
require_fields($payload, ['review_id', 'rating', 'body']);

$review_id = clean_int($payload['review_id'], 1);
$rating    = clean_int($payload['rating'], 1, 5);
$body      = clean_str($payload['body']);
$remove_photo = isset($payload['remove_photo']) && $payload['remove_photo'] == '1';
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
    SELECT id, spot_id, photo_url FROM reviews
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$review_id, $user_id]);
$row = $check->fetch();
if (!$row) {
    json_error('Review not found.', 404);
}
$spot_id = $row['spot_id'];
$old_photo_url = $row['photo_url'];

// ── Photo Upload ──────────────────────────────────────────────
$new_photo_url = $old_photo_url;

if ($remove_photo) {
    $new_photo_url = null;
    if ($old_photo_url) {
        $file_path = __DIR__ . '/../../' . $old_photo_url;
        if (file_exists($file_path)) unlink($file_path);
    }
} elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
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
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $target_path = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        json_error('Failed to save uploaded photo.', 500);
    }
    $new_photo_url = 'assets/images/reviews/' . $filename;
    
    // Delete old photo if it exists
    if ($old_photo_url) {
        $file_path = __DIR__ . '/../../' . $old_photo_url;
        if (file_exists($file_path)) unlink($file_path);
    }
}

// ── Update + recompute aggregate, in one transaction ────────
try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare("
        UPDATE reviews
        SET rating = ?, body = ?, photo_url = ?
        WHERE id = ?
    ");
    $upd->execute([$rating, $body, $new_photo_url, $review_id]);

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
