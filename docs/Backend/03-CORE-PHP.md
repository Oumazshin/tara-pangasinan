# 03 — Core PHP Bootstrap + AJAX Layer

**Goal:** Create the shared backend foundation — DB connection, JSON helpers, session bootstrap, auth middleware, input validators — and the frontend AJAX wrapper. Every subsequent module reuses these.

**Time estimate:** 1.5 hours. Once done, every module phase becomes mostly copy-paste-and-adapt.

---

## 1. Files Created in This Phase

| File | Purpose |
|---|---|
| `config/database.php` | DB credentials (gitignored) |
| `includes/db.php` | PDO instance |
| `includes/response.php` | `json_success()`, `json_error()` |
| `includes/session.php` | Session start with safe cookie params |
| `includes/auth.php` | `require_login()` middleware |
| `includes/validation.php` | Common input sanitizers |
| `api/_test.php` | Smoke test endpoint |
| `js/api.js` | Browser-side AJAX wrapper |

---

## 2. Database Credentials File

**File:** `config/database.php`

```php
<?php
/**
 * Database credentials for the local XAMPP environment.
 * Each developer keeps a local copy; this file is gitignored.
 */
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'tara_pangasinan',
    'user'     => 'root',            // change to 'tara_user' if you created one in Phase 01
    'password' => '',                // change to your password if applicable
    'charset'  => 'utf8mb4',
];
```

**Add `.gitignore` entry** so teammates don't overwrite each other:

```
config/database.php
```

> If you don't yet have a `.gitignore`, create one at the project root with at least those two lines.

---

## 3. PDO Connection

**File:** `includes/db.php`

```php
<?php
/**
 * Provides a single shared $pdo instance.
 * include this file anywhere you need to talk to the database.
 */

if (!isset($pdo)) {

    $cfg = require __DIR__ . '/../config/database.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        $cfg['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw on SQL errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // return associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
    ];

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], $options);
    } catch (PDOException $e) {
        // Don't leak DB internals to the client
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Database connection failed.',
            'code'    => 'db_connection_failed',
        ]);
        // Log the real error for debugging (visible in XAMPP's Apache error log)
        error_log('PDO connection error: ' . $e->getMessage());
        exit;
    }
}
```

---

## 4. JSON Response Helpers

**File:** `includes/response.php`

```php
<?php
/**
 * Standardized JSON response helpers.
 * Always exit immediately after sending — no further output.
 */

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');                       // never cache API responses
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_success($data = null, string $message = ''): void {
    $payload = ['success' => true];
    if ($data !== null)              $payload['data']    = $data;
    if ($message !== '')             $payload['message'] = $message;
    json_response($payload, 200);
}

function json_error(string $error, string $code = 'error', int $status = 400, $extra = null): void {
    $payload = [
        'success' => false,
        'error'   => $error,
        'code'    => $code,
    ];
    if ($extra !== null) $payload['details'] = $extra;
    json_response($payload, $status);
}

/**
 * Reads the JSON body from POST requests. Returns an array.
 * Falls back to $_POST for x-www-form-urlencoded submissions.
 */
function read_json_body(): array {
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
```

---

## 5. Session Bootstrap

**File:** `includes/session.php`

```php
<?php
/**
 * Starts a PHP session with safe cookie parameters.
 * Must be included BEFORE any output.
 */

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 7,    // 7 days
        'path'     => '/',
        'secure'   => false,                // set true in production over HTTPS
        'httponly' => true,                 // JS cannot read the cookie
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Convenience accessors
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function is_logged_in(): bool {
    return current_user_id() !== null;
}
```

---

## 6. Auth Middleware

**File:** `includes/auth.php`

```php
<?php
/**
 * Aborts the request with 401 if the user is not logged in.
 * Call at the top of any endpoint that requires authentication.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/response.php';

function require_login(): int {
    if (!is_logged_in()) {
        json_error('You must be logged in to do that.', 'auth_required', 401);
    }
    return current_user_id();
}
```

Usage at the top of, say, `api/bookings/create.php`:

```php
require_once __DIR__ . '/../../includes/auth.php';
$userId = require_login();   // aborts before this line if not authenticated
```

---

## 7. Input Validation

**File:** `includes/validation.php`

```php
<?php
/**
 * Light-weight input validators / sanitizers.
 * Always validate on the server even if you already validate in JS.
 */

function clean_str($value, int $max = 500): string {
    if (!is_scalar($value)) return '';
    $value = trim((string)$value);
    if (mb_strlen($value) > $max) $value = mb_substr($value, 0, $max);
    return $value;
}

function clean_int($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int {
    $n = filter_var($value, FILTER_VALIDATE_INT);
    if ($n === false) return $min;
    if ($n < $min) $n = $min;
    if ($n > $max) $n = $max;
    return $n;
}

function valid_email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valid_slug(string $slug): bool {
    return (bool)preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $slug);
}

function require_fields(array $body, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!array_key_exists($field, $body) || $body[$field] === '' || $body[$field] === null) {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        json_error('Missing required fields: ' . implode(', ', $missing), 'missing_fields', 422, $missing);
    }
    return $body;
}
```

> Note `json_error` is called inside `require_fields()`. That works because the endpoint file always includes `response.php` before `validation.php`. Just include in the right order:
>
> ```php
> require_once __DIR__ . '/../../includes/response.php';
> require_once __DIR__ . '/../../includes/validation.php';
> ```

---

## 8. Smoke-Test Endpoint

This is a temporary endpoint to confirm the whole pipeline works. You'll delete it after testing.

**File:** `api/_test.php`

```php
<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';

$rows = $pdo->query("SELECT COUNT(*) AS spot_count FROM spots")->fetch();

json_success([
    'php_version' => PHP_VERSION,
    'db_connected' => true,
    'spot_count'   => (int)$rows['spot_count'],
    'session_id'   => session_id(),
    'logged_in'    => is_logged_in(),
]);
```

Visit <http://localhost/tara-pangasinan/api/_test.php>. You should see:

```json
{
  "success": true,
  "data": {
    "php_version": "8.2.4",
    "db_connected": true,
    "spot_count": 0,
    "session_id": "abc123...",
    "logged_in": false
  }
}
```

**If you see this**, everything is wired correctly. Now run the seeder from Phase 2:

1. Visit <http://localhost/tara-pangasinan/sql/seed.php>.
2. Re-visit `_test.php` — `spot_count` should now be **8**.
3. **Delete `sql/seed.php` and `api/_test.php`** before pushing to your repo.

---

## 9. The AJAX Wrapper (`js/api.js`)

This is the single point through which **all** frontend code talks to the backend. Build it once; reuse everywhere.

**File:** `js/api.js`

```javascript
/* ─────────────────────────────────────────────────────────────
 * Tara Pangasinan — AJAX wrapper
 * Single source of truth for backend communication.
 * Every page that needs backend data includes this BEFORE main.js:
 *   <script src="js/api.js"></script>
 * ─────────────────────────────────────────────────────────── */
(function (global) {
    'use strict';

    // Auto-detect the API base from the current page location.
    // Works whether the project is served from root or a subfolder.
    var BASE = (function () {
        var path = window.location.pathname;
        // Strip trailing filename to get the project root
        var root = path.substring(0, path.lastIndexOf('/') + 1);
        return root + 'api/';
    })();

    /**
     * Generic request runner.
     * @param {string} method  'GET' or 'POST'
     * @param {string} path    Path relative to /api/ (e.g. 'spots/list.php')
     * @param {object} body    Payload (object). For GET, becomes querystring.
     * @returns {Promise<object>} The parsed JSON `data` field on success.
     *                            Throws an Error on failure.
     */
    function request(method, path, body) {
        var url = BASE + path;
        var init = {
            method: method,
            credentials: 'same-origin',           // send session cookie
            headers: { 'Accept': 'application/json' }
        };

        if (method === 'GET' && body) {
            var qs = new URLSearchParams(body).toString();
            url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
        } else if (method !== 'GET') {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(body || {});
        }

        return fetch(url, init)
            .then(function (res) {
                return res.json().catch(function () {
                    throw new ApiError('Server returned a non-JSON response.', 'invalid_response', res.status);
                }).then(function (json) {
                    if (!json.success) {
                        throw new ApiError(json.error || 'Request failed.', json.code || 'unknown', res.status, json.details);
                    }
                    return json.data !== undefined ? json.data : null;
                });
            });
    }

    /**
     * Custom error class with structured fields.
     */
    function ApiError(message, code, status, details) {
        this.name = 'ApiError';
        this.message = message;
        this.code = code;
        this.status = status;
        this.details = details;
    }
    ApiError.prototype = Object.create(Error.prototype);
    ApiError.prototype.constructor = ApiError;

    // Public API
    global.Api = {
        get:  function (path, query) { return request('GET',  path, query); },
        post: function (path, body)  { return request('POST', path, body);  },
        ApiError: ApiError,
        BASE: BASE
    };

})(window);
```

### How to Use

In a page-level `<script>` or in `main.js`:

```javascript
// Simple GET
Api.get('spots/list.php')
    .then(function (spots) {
        console.log(spots);     // array of spot objects
    })
    .catch(function (err) {
        console.error(err.code, err.message);
    });

// POST with body
Api.post('saved/toggle.php', { spot_id: 'hundred-islands' })
    .then(function (result) {
        console.log(result.saved ? 'Saved!' : 'Removed.');
    });

// GET with query string
Api.get('reviews/list.php', { spot_id: 'hundred-islands', limit: 10 })
    .then(function (reviews) {
        // ... render
    });
```

### Include Order in HTML

```html
<!-- Always before any page-level script that uses Api.* -->
<script src="js/api.js"></script>
<script src="js/main.js"></script>
```

For pages with inline scripts (like `plan.html`), put `api.js` in the `<head>` or just above the inline script block.

---

## 10. Test Round-Trip from the Browser

Open <http://localhost/tara-pangasinan/index.html>. In DevTools Console:

```javascript
Api.get('_test.php').then(d => console.log(d));
```

Expected console output:

```
{ php_version: "8.x", db_connected: true, spot_count: 8, ... }
```

If you see this, the **HTML → JS → AJAX → PHP → MySQL → JSON → JS** loop is complete. Every subsequent module is built on this foundation.

---

## 11. Common Mistakes Already Handled

| Mistake | How this setup prevents it |
|---|---|
| SQL injection | PDO prepared statements throughout. |
| XSS via API responses | `json_encode` escapes all special chars; clients write to `.textContent`, not `.innerHTML`. |
| Session fixation | `session_start()` with `httponly` cookie, `SameSite=Lax`. |
| CSRF on POST | `SameSite=Lax` cookie blocks cross-site POSTs; we serve same-origin only. (For production, add an explicit CSRF token — out of scope here.) |
| Leaking DB errors | `db.php` catches `PDOException`, logs the real message via `error_log`, sends a generic JSON error. |
| Inconsistent response shape | Every endpoint must use `json_success` / `json_error`. The wrapper `Api.*` relies on this. |

---

## 12. File Tree After This Phase

```
tara-pangasinan/
├── api/
│   └── _test.php              ← created, used, then deleted
├── config/
│   └── database.php           ✓
├── includes/
│   ├── auth.php               ✓
│   ├── db.php                 ✓
│   ├── response.php           ✓
│   ├── session.php            ✓
│   └── validation.php         ✓
├── js/
│   ├── api.js                 ✓ NEW
│   └── main.js                (unchanged in this phase)
├── sql/
│   ├── schema.sql             (from Phase 02)
│   ├── seed-lookups.sql       (from Phase 02)
│   └── seed.php               ← run once via browser, then delete
```

---

## Done When

- [ ] All 5 `includes/*.php` files exist and contain the code above.
- [ ] `config/database.php` returns your local credentials.
- [ ] `js/api.js` exists.
- [ ] Visiting `api/_test.php` returns valid JSON with `spot_count: 0` (before seed) or `8` (after seed).
- [ ] Running `sql/seed.php` once succeeded and you've **deleted** `sql/seed.php`.
- [ ] `Api.get('_test.php')` in browser console returns the expected JSON.
- [ ] `_test.php` is **deleted** (you've confirmed it works, no longer need it).

Continue to **[`04-AUTH-MODULE.md`](./04-AUTH-MODULE.md)** to wire up registration and login.
