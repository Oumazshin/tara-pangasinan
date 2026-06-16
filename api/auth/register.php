<?php
/**
 * POST /api/auth/register.php
 *
 * Creates a new user account and auto-logs them in.
 *
 * Request body (JSON):
 *   { "name": "Carlo Reyes", "email": "carlo@example.com", "password": "secret123" }
 *
 * Success response:
 *   { "success": true, "data": { "user": { id, email, first_name, last_name } }, "message": "..." }
 *
 * Error codes: missing_fields | invalid_email | weak_password | email_taken
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 'method_not_allowed', 405);
}

$body = read_json_body();
require_fields($body, ['name', 'email', 'password']);

$fullName = clean_str($body['name'], 200);
$email    = strtolower(clean_str($body['email'], 255));
$password = (string) $body['password'];

// Validate email format
if (!valid_email($email)) {
    json_error('Please provide a valid email address.', 'invalid_email', 422);
}

// Enforce minimum password length
if (strlen($password) < 6) {
    json_error('Password must be at least 6 characters.', 'weak_password', 422);
}

// Split full name into first / last (split on first space)
$parts     = preg_split('/\s+/', trim($fullName), 2);
$firstName = $parts[0] ?? $fullName;
$lastName  = $parts[1] ?? '';

// Check email uniqueness
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_error('That email is already registered.', 'email_taken', 409);
}

// Hash password and insert
$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = $pdo->prepare('
    INSERT INTO users (email, password_hash, first_name, last_name)
    VALUES (?, ?, ?, ?)
');
$insert->execute([$email, $hash, $firstName, $lastName]);
$userId = (int) $pdo->lastInsertId();

// Auto-login: set session immediately after registration
$_SESSION['user_id'] = $userId;
session_regenerate_id(true);  // prevent session fixation

json_success([
    'user' => [
        'id'         => $userId,
        'email'      => $email,
        'first_name' => $firstName,
        'last_name'  => $lastName,
    ]
], 'Account created successfully.');
