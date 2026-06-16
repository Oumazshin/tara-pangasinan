<?php
/**
 * Input Validators and Sanitizers.
 *
 * Always validate on the server — never trust JavaScript-only validation.
 * These functions are small and composable; use them freely inside endpoints.
 *
 * NOTE: json_error() (from response.php) is called inside require_fields().
 * Always include response.php before this file.
 */

/**
 * Trims and truncates a string value. Returns empty string for non-scalar input.
 *
 * @param mixed $value  Raw input value
 * @param int   $max    Maximum allowed length in characters (default 500)
 */
function clean_str($value, int $max = 500): string
{
    if (!is_scalar($value)) return '';
    $value = trim((string) $value);
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }
    return $value;
}

/**
 * Coerces a value to an integer, clamped between $min and $max.
 * Returns $min if the value is not a valid integer.
 */
function clean_int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
{
    $n = filter_var($value, FILTER_VALIDATE_INT);
    if ($n === false) return $min;
    if ($n < $min)   return $min;
    if ($n > $max)   return $max;
    return $n;
}

/**
 * Returns true if the email address passes PHP's built-in email filter.
 */
function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Returns true if the string looks like a valid URL slug.
 * Pattern: lowercase letters, digits, hyphens; 1–80 characters.
 */
function valid_slug(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $slug);
}

/**
 * Asserts that all listed keys are present and non-empty in $body.
 * Calls json_error() and exits if any field is missing.
 *
 * @param array    $body     Parsed request body
 * @param string[] $required List of required field names
 * @return array  Returns $body unchanged on success
 */
function require_fields(array $body, array $required): array
{
    $missing = [];

    foreach ($required as $field) {
        $val = $body[$field] ?? null;
        if ($val === null || $val === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        json_error(
            'Missing required fields: ' . implode(', ', $missing),
            'missing_fields',
            422,
            $missing
        );
    }

    return $body;
}
