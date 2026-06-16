<?php
/**
 * GET /api/auth/session.php
 *
 * Returns the current session state. Used by main.js on every page load
 * to sync the client's localStorage with the server's authoritative session.
 *
 * Not logged in:
 *   { "success": true, "data": { "logged_in": false } }
 *
 * Logged in:
 *   { "success": true, "data": { "logged_in": true, "user": { id, email, first_name, last_name, phone, city, bio, avatar_url } } }
 *
 * Stale session (user deleted):
 *   Session is cleared and response is { "logged_in": false }
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/db.php';

$uid = current_user_id();

// Not logged in
if (!$uid) {
    json_success(['logged_in' => false]);
}

// Fetch fresh user data from DB (the session only stores the ID)
$stmt = $pdo->prepare('
    SELECT id, email, first_name, last_name, phone, city, bio, avatar_url
    FROM users
    WHERE id = ?
    LIMIT 1
');
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Handle stale session (e.g. user was deleted from DB while still logged in)
if (!$user) {
    $_SESSION = [];
    session_destroy();
    json_success(['logged_in' => false]);
}

// Cast id to int (PDO returns it as string by default)
$user['id'] = (int) $user['id'];

json_success([
    'logged_in' => true,
    'user'      => $user,
]);
