<?php
/**
 * POST /api/auth/logout.php
 *
 * Destroys the current PHP session and clears the session cookie.
 * Always succeeds — even if the user is not logged in.
 *
 * Success response:
 *   { "success": true, "message": "Logged out." }
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';

// Wipe all session variables
$_SESSION = [];

// Delete the browser-side session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the server-side session data
session_destroy();

json_success(null, 'Logged out.');
