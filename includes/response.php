<?php
/**
 * Standardized JSON response helpers.
 *
 * Every API endpoint must use json_success() or json_error() — never echo raw JSON.
 * These functions call exit(), so no code runs after them.
 */

/**
 * Sends a JSON response with the given HTTP status code, then exits.
 */
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send a success envelope.
 *
 * @param mixed  $data    The payload to put in the "data" key (omitted if null)
 * @param string $message Optional human-readable message
 */
function json_success($data = null, string $message = ''): void
{
    $payload = ['success' => true];
    if ($data !== null)  $payload['data']    = $data;
    if ($message !== '') $payload['message'] = $message;
    json_response($payload, 200);
}

/**
 * Send an error envelope.
 *
 * @param string $error  Human-readable error description
 * @param string $code   Machine-readable error code (e.g. 'invalid_email')
 * @param int    $status HTTP status code (400, 401, 404, 422, 409, 500 …)
 * @param mixed  $extra  Optional extra detail (e.g. array of missing fields)
 */
function json_error(string $error, $code = 'error', int $status = 400, $extra = null): void
{
    if (is_int($code)) {
        $status = $code;
        $code = 'error';
    }
    $payload = [
        'success' => false,
        'error'   => $error,
        'code'    => $code,
    ];
    if ($extra !== null) $payload['details'] = $extra;
    json_response($payload, $status);
}

/**
 * Reads and parses the raw JSON body from a POST request.
 * Falls back to $_POST for form-encoded submissions.
 *
 * @return array  Parsed request body as an associative array
 */
function read_json_body(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === '' || $raw === false) {
        return $_POST;
    }

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        json_error('Request body is not valid JSON.', 'invalid_json', 400);
    }

    return $data;
}
