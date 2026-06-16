<?php
/**
 * Session Bootstrap.
 *
 * Starts a PHP session with safe, consistent cookie parameters.
 * Must be included BEFORE any output is sent to the browser.
 * The guard prevents double-starting if included more than once.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 7,  // 7 days
        'path'     => '/',
        'secure'   => false,              // set to true in production (HTTPS only)
        'httponly' => true,               // JS cannot read the PHPSESSID cookie
        'samesite' => 'Lax',             // blocks cross-site POST abuse
    ]);

    session_start();
}

/**
 * Returns the currently logged-in user's ID, or null if not logged in.
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Returns true if a user session is currently active.
 */
function is_logged_in(): bool
{
    return current_user_id() !== null;
}
