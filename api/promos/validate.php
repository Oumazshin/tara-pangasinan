<?php
/**
 * POST /api/promos/validate.php
 * Body: { "code": "TARA10" }
 *
 * Returns the discount type + value if the code is valid and still active.
 * If invalid or expired → 404 with a friendly message.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';

$payload = read_json_body();
require_fields($payload, ['code']);

// Normalize: uppercase, trim, alphanumeric only
$code = strtoupper(trim($payload['code']));
if (!preg_match('/^[A-Z0-9]{3,20}$/', $code)) {
    json_error('Invalid promo code format.', 400);
}

$stmt = $pdo->prepare("
    SELECT code, type, value, label, expires_at
    FROM promos
    WHERE code = ?
      AND is_active = 1
      AND (expires_at IS NULL OR expires_at >= CURDATE())
    LIMIT 1
");
$stmt->execute([$code]);
$promo = $stmt->fetch();

if (!$promo) {
    json_error('Promo code not found or expired.', 404);
}

json_success([
    'code'           => $promo['code'],
    'discount_type'  => $promo['type'],            // 'percent' or 'fixed'
    'discount_value' => (int) $promo['value'],
    'label'          => $promo['label'],
]);
