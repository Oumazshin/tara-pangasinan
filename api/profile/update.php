<?php
/**
 * POST /api/profile/update.php
 *
 * Body: {
 *   "first_name":        "Carlo",
 *   "last_name":         "Reyes",
 *   "email":             "carlo@example.com",
 *   "phone":             "+63 917 234 5678",
 *   "city":              "Dagupan City, Pangasinan",
 *   "bio":               "Proud Pangasinanon…",
 *
 *   // Optional — only sent if user is changing password
 *   "current_password":  "old_pw",
 *   "new_password":      "new_pw"
 * }
 *
 * Validates each field, then runs everything in one transaction.
 * Password change requires `current_password` to match the stored hash.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 'method_not_allowed', 405);
}

$payload = read_json_body();
$user_id = $_SESSION['user_id'];

// ── Clean basic fields ───────────────────────────────────────
$first_name = clean_str($payload['first_name'] ?? '');
$last_name  = clean_str($payload['last_name']  ?? '');
$email      = clean_str($payload['email']      ?? '');
$phone      = clean_str($payload['phone']      ?? '');
$city       = clean_str($payload['city']       ?? '');
$bio        = clean_str($payload['bio']        ?? '', 500);

// ── Validation ───────────────────────────────────────────────
if (mb_strlen($first_name) < 2 || mb_strlen($first_name) > 50) {
    json_error('First name must be 2–50 characters.', 'validation_error', 400);
}
if (mb_strlen($last_name) < 2 || mb_strlen($last_name) > 50) {
    json_error('Last name must be 2–50 characters.', 'validation_error', 400);
}
if (!valid_email($email)) {
    json_error('Invalid email address.', 'validation_error', 400);
}
if ($phone !== '' && !preg_match('/^[\d\s()+\-]{7,25}$/', $phone)) {
    json_error('Phone number contains invalid characters.', 'validation_error', 400);
}
if (mb_strlen($city) > 100) {
    json_error('City name is too long.', 'validation_error', 400);
}
if (mb_strlen($bio) > 500) {
    json_error('Bio cannot exceed 500 characters.', 'validation_error', 400);
}

// ── Email uniqueness (excluding own record) ──────────────────
$dup = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND id != ? LIMIT 1");
$dup->execute([$email, $user_id]);
if ($dup->fetchColumn()) {
    json_error('This email is already used by another account.', 'email_taken', 409);
}

// ── Optional password change ─────────────────────────────────
$current_pw = $payload['current_password'] ?? '';
$new_pw     = $payload['new_password']     ?? '';
$password_changing = ($current_pw !== '' || $new_pw !== '');
$new_hash = null;

if ($password_changing) {
    if ($current_pw === '' || $new_pw === '') {
        json_error('Both current and new passwords are required to change password.', 'validation_error', 400);
    }
    if (strlen($new_pw) < 8) {
        json_error('New password must be at least 8 characters.', 'validation_error', 400);
    }
    if ($new_pw === $current_pw) {
        json_error('New password must differ from current password.', 'validation_error', 400);
    }

    // Verify current password using constant-time comparison
    $pw_stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
    $pw_stmt->execute([$user_id]);
    $row = $pw_stmt->fetch();
    if (!$row || !password_verify($current_pw, $row['password_hash'])) {
        json_error('Current password is incorrect.', 'wrong_password', 401);
    }

    $new_hash = password_hash($new_pw, PASSWORD_DEFAULT);
}

// ── Single atomic update ─────────────────────────────────────
try {
    $pdo->beginTransaction();

    $sql = "
        UPDATE users SET
            first_name = ?,
            last_name  = ?,
            email      = ?,
            phone      = ?,
            city       = ?,
            bio        = ?
    ";
    $params = [$first_name, $last_name, $email, $phone, $city, $bio];

    if ($new_hash !== null) {
        $sql .= ", password_hash = ?";
        $params[] = $new_hash;
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $pdo->prepare($sql)->execute($params);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

// ── Refresh session display fields ───────────────────────────
$_SESSION['user_first_name'] = $first_name;
$_SESSION['user_email']      = $email;

json_success([
    'user' => [
        'id'         => (int) $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'phone'      => $phone,
        'city'       => $city,
        'bio'        => $bio,
    ],
    'password_changed' => $password_changing,
], $password_changing ? 'Profile and password updated.' : 'Profile updated.');
