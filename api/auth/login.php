<?php
/**
 * POST /api/auth/login.php
 *
 * Authenticates an existing user and creates a PHP session.
 *
 * Request body (JSON):
 *   { "email": "carlo@example.com", "password": "secret123" }
 *
 * Success response:
 *   { "success": true, "data": { "user": { id, email, first_name, last_name } }, "message": "Welcome back!" }
 *
 * Error codes: missing_fields | invalid_credentials
 *
 * Security note: "invalid_credentials" is intentionally vague — it does NOT
 * reveal whether the email exists (prevents user enumeration attacks).
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 'method_not_allowed', 405);
}

$body = read_json_body();
require_fields($body, ['email', 'password']);

$email    = strtolower(clean_str($body['email'], 255));
$password = (string) $body['password'];

// Basic email sanity check (same vague error to avoid enumeration)
if (!valid_email($email)) {
    json_error('Invalid email or password.', 'invalid_credentials', 401);
}

// Fetch the user row
$stmt = $pdo->prepare('
    SELECT id, email, password_hash, first_name, last_name
    FROM users
    WHERE email = ?
    LIMIT 1
');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Intentionally vague: same error whether email not found or password wrong
if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('Invalid email or password.', 'invalid_credentials', 401);
}

// Establish session
$_SESSION['user_id'] = (int) $user['id'];
session_regenerate_id(true);  // rotate session ID on privilege escalation

json_success([
    'user' => [
        'id'         => (int) $user['id'],
        'email'      => $user['email'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
    ]
], 'Welcome back!');
