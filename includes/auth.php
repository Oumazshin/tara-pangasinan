<?php
/**
 * Auth Middleware.
 *
 * Call require_login() at the top of any endpoint that requires authentication.
 * If the user is not logged in, it immediately sends a 401 JSON error and exits.
 * If logged in, it returns the current user_id as an int for convenience.
 *
 * Usage:
 *   require_once __DIR__ . '/../../includes/auth.php';
 *   $userId = require_login();  // aborts before this line if not authenticated
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/response.php';

function require_login(): int
{
    if (!is_logged_in()) {
        json_error('You must be logged in to do that.', 'auth_required', 401);
    }

    return current_user_id();
}
