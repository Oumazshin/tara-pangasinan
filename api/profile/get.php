<?php
/**
 * GET /api/profile/get.php
 *
 * Returns the current logged-in user's profile fields.
 * Never returns the password hash.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, email, phone, city, bio, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Session points to a deleted user — destroy session and 401
    session_destroy();
    json_error('Session expired.', 'session_expired', 401);
}

$user['id'] = (int) $user['id'];

json_success(['user' => $user]);
