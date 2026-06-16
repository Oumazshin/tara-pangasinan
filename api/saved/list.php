<?php
/**
 * GET /api/saved/list.php
 *
 * Returns the list of spot IDs saved by the current logged-in user.
 * Auth required.
 *
 * Response:
 *   {
 *     "success": true,
 *     "data": {
 *       "saved_ids": ["hundred-islands", "patar-beach"],
 *       "count": 2
 *     }
 *   }
 *
 * Note: Returns IDs only (not full spot objects). The frontend already has
 * the full spots list in memory from api/spots/list.php, so it just needs
 * the IDs to know which hearts to paint red.
 *
 * Error codes: auth_required (401)
 */

require_once __DIR__ . '/../../includes/auth.php';  // starts session + response
require_once __DIR__ . '/../../includes/db.php';

// Authenticate — aborts with 401 if not logged in
$userId = require_login();

$stmt = $pdo->prepare('
    SELECT spot_id
    FROM saved_spots
    WHERE user_id = ?
    ORDER BY saved_at DESC
');
$stmt->execute([$userId]);

$ids = array_column($stmt->fetchAll(), 'spot_id');

json_success([
    'saved_ids' => $ids,
    'count'     => count($ids),
]);
