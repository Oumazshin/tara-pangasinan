<?php
/**
 * POST /api/saved/toggle.php
 *
 * Toggles a spot's saved state for the current logged-in user.
 * Auth required.
 *
 * Request body (JSON):
 *   { "spot_id": "hundred-islands" }
 *   Optional: { "spot_id": "...", "force": "save" }  — always save
 *   Optional: { "spot_id": "...", "force": "remove" } — always remove
 *
 * Response (saved):
 *   { "success": true, "data": { "spot_id": "...", "saved": true }, "message": "Saved to your list!" }
 *
 * Response (removed):
 *   { "success": true, "data": { "spot_id": "...", "saved": false }, "message": "Removed from saved." }
 *
 * Error codes: method_not_allowed | missing_fields | invalid_id | not_found | auth_required
 */

require_once __DIR__ . '/../../includes/auth.php';         // starts session + response
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 'method_not_allowed', 405);
}

// Authenticate — aborts with 401 if not logged in
$userId = require_login();

$body   = read_json_body();
require_fields($body, ['spot_id']);

$spotId = clean_str($body['spot_id'], 80);
$force  = isset($body['force']) ? clean_str($body['force'], 10) : null;  // 'save' | 'remove' | null

if (!valid_slug($spotId)) {
    json_error('Invalid spot id.', 'invalid_id', 422);
}

// Confirm the spot actually exists
$exists = $pdo->prepare('SELECT 1 FROM spots WHERE id = ? LIMIT 1');
$exists->execute([$spotId]);
if (!$exists->fetch()) {
    json_error('That destination does not exist.', 'not_found', 404);
}

// Determine current saved state
$check = $pdo->prepare('SELECT 1 FROM saved_spots WHERE user_id = ? AND spot_id = ? LIMIT 1');
$check->execute([$userId, $spotId]);
$alreadySaved = (bool) $check->fetch();

// Resolve the target state
if ($force === 'save')        $shouldSave = true;
elseif ($force === 'remove')  $shouldSave = false;
else                          $shouldSave = !$alreadySaved;  // toggle

if ($shouldSave && !$alreadySaved) {
    $ins = $pdo->prepare('INSERT INTO saved_spots (user_id, spot_id) VALUES (?, ?)');
    $ins->execute([$userId, $spotId]);
    json_success(['spot_id' => $spotId, 'saved' => true], 'Saved to your list!');
}

if (!$shouldSave && $alreadySaved) {
    $del = $pdo->prepare('DELETE FROM saved_spots WHERE user_id = ? AND spot_id = ?');
    $del->execute([$userId, $spotId]);
    json_success(['spot_id' => $spotId, 'saved' => false], 'Removed from saved.');
}

// No-op (e.g. force=save but already saved, or force=remove but not saved)
json_success(['spot_id' => $spotId, 'saved' => $alreadySaved], 'No change.');
