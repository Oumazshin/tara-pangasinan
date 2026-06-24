<?php
/**
 * POST /api/contact/submit.php
 *
 * Body (frontend uses fname/lname for backwards compat with form IDs):
 * {
 *   "fname":   "John",
 *   "lname":   "Doe",
 *   "email":   "john@example.com",
 *   "subject": "tour" | "support" | "partnership" | "other",
 *   "message": "How can we help you?",
 *   "website": ""              // ← honeypot. If filled, it's a bot.
 * }
 *
 * - No auth required (public form).
 * - IP-rate-limited: max 5 submissions per IP per hour.
 * - Honeypot field silently rejects spam bots without telling them why.
 *
 * Note: The DB columns are `first_name` / `last_name` / `submitted_at` (snake_case).
 * We accept the camel-shortened `fname` / `lname` from the form for compatibility
 * with the existing HTML input IDs, and translate at this boundary.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 'method_not_allowed', 405);
}

$payload = read_json_body();

// ── HONEYPOT ─────────────────────────────────────────────────
// Real users never see this field (CSS-hidden in the form).
// Bots that fill every input get a fake success and walk away happy.
if (!empty($payload['website'])) {
    json_success(['stored' => false], 'Thanks for your message.');
}

// ── Required fields ──────────────────────────────────────────
require_fields($payload, ['fname', 'lname', 'email', 'subject', 'message']);

$first_name = clean_str($payload['fname']);
$last_name  = clean_str($payload['lname']);
$email      = clean_str($payload['email']);
$subject    = clean_str($payload['subject']);
$message    = clean_str($payload['message']);

// ── Validation ───────────────────────────────────────────────
if (mb_strlen($first_name) < 2 || mb_strlen($first_name) > 50) {
    json_error('First name must be 2–50 characters.', 'validation_error', 400);
}
if (mb_strlen($last_name) < 2 || mb_strlen($last_name) > 50) {
    json_error('Last name must be 2–50 characters.', 'validation_error', 400);
}
if (!valid_email($email)) {
    json_error('Please enter a valid email address.', 'validation_error', 400);
}

$allowed_subjects = ['tour', 'support', 'partnership', 'other'];
if (!in_array($subject, $allowed_subjects, true)) {
    json_error('Invalid subject category.', 'validation_error', 400);
}

$msg_len = mb_strlen($message);
if ($msg_len < 10) {
    json_error('Message must be at least 10 characters.', 'validation_error', 400);
}
if ($msg_len > 2000) {
    json_error('Message cannot exceed 2000 characters.', 'validation_error', 400);
}

// ── Rate limit: max 5 submissions / IP / hour ────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$rate_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM contact_messages
    WHERE ip_address = ? AND submitted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$rate_stmt->execute([$ip]);
if ((int) $rate_stmt->fetchColumn() >= 5) {
    json_error('Too many messages. Please try again in an hour.', 'rate_limited', 429);
}

// ── Insert ───────────────────────────────────────────────────
$ins = $pdo->prepare("
    INSERT INTO contact_messages
        (first_name, last_name, email, subject, message, ip_address, submitted_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$ins->execute([$first_name, $last_name, $email, $subject, $message, $ip]);

json_success([
    'stored' => true,
    'id'     => (int) $pdo->lastInsertId(),
], 'Message received. Our team will respond within 24 hours.');
