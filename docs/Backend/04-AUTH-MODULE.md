# 04 — Auth Module

**Goal:** Replace the localStorage-fake-login in `login.html` and `register.html` with real backend authentication. After this phase: real accounts persist in the `users` table, passwords are hashed with bcrypt, and sessions work across page refreshes.

**Time estimate:** 2 hours.

---

## 1. Files Affected

| File | Action |
|---|---|
| `api/auth/register.php`   | **NEW** |
| `api/auth/login.php`      | **NEW** |
| `api/auth/logout.php`     | **NEW** |
| `api/auth/session.php`    | **NEW** (returns current logged-in user) |
| `login.html`              | MODIFIED — replace inline `localStorage.setItem` with AJAX |
| `register.html`           | MODIFIED — same |
| `profile.html`            | MODIFIED — logout button calls API |
| `js/main.js`              | MODIFIED — refresh `tara_session` from `api/auth/session.php` on page load |

---

## 2. Database Tables Used

Only `users`. Recap of the schema:

```sql
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    first_name      VARCHAR(100)  NOT NULL,
    last_name       VARCHAR(100)  NOT NULL,
    phone           VARCHAR(30),
    city            VARCHAR(150),
    bio             TEXT,
    avatar_url      VARCHAR(500),
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 3. API Endpoints

### 3.1 `POST /api/auth/register.php`

**Request body:**

```json
{
  "name":     "Carlo Reyes",
  "email":    "carlo@example.com",
  "password": "secret123"
}
```

**Success response (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "carlo@example.com",
      "first_name": "Carlo",
      "last_name": "Reyes"
    }
  },
  "message": "Account created successfully."
}
```

**Error cases:**

| Code | When |
|---|---|
| `missing_fields` | Name, email, or password not provided |
| `invalid_email` | Email format invalid |
| `weak_password` | Password under 6 characters |
| `email_taken` | A user with this email already exists |

### 3.2 `POST /api/auth/login.php`

**Request body:**

```json
{
  "email":    "carlo@example.com",
  "password": "secret123"
}
```

**Success response:** Same shape as register. Also sets the `PHPSESSID` cookie.

**Error cases:**

| Code | When |
|---|---|
| `missing_fields` | Email or password empty |
| `invalid_credentials` | Email not found OR password mismatch (intentionally vague — security best practice) |

### 3.3 `POST /api/auth/logout.php`

**Request body:** none.

**Success response:**

```json
{ "success": true, "message": "Logged out." }
```

Destroys session.

### 3.4 `GET /api/auth/session.php`

**Request:** none.

**If logged in:**

```json
{
  "success": true,
  "data": {
    "logged_in": true,
    "user": { "id": 1, "email": "...", "first_name": "...", "last_name": "..." }
  }
}
```

**If not:**

```json
{ "success": true, "data": { "logged_in": false } }
```

---

## 4. Backend Code

### 4.1 `api/auth/register.php`

```php
<?php
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
$password = (string)$body['password'];

if (!valid_email($email)) {
    json_error('Please provide a valid email address.', 'invalid_email', 422);
}
if (strlen($password) < 6) {
    json_error('Password must be at least 6 characters.', 'weak_password', 422);
}

// Split full name into first + last (one space rule)
$parts     = preg_split('/\s+/', $fullName, 2);
$firstName = $parts[0] ?? $fullName;
$lastName  = $parts[1] ?? '';

// Email uniqueness check
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_error('That email is already registered.', 'email_taken', 409);
}

// Hash and insert
$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = $pdo->prepare("
    INSERT INTO users (email, password_hash, first_name, last_name)
    VALUES (?, ?, ?, ?)
");
$insert->execute([$email, $hash, $firstName, $lastName]);

$userId = (int)$pdo->lastInsertId();

// Auto-login on registration
$_SESSION['user_id'] = $userId;
session_regenerate_id(true);    // prevent session fixation

json_success([
    'user' => [
        'id'         => $userId,
        'email'      => $email,
        'first_name' => $firstName,
        'last_name'  => $lastName,
    ]
], 'Account created successfully.');
```

### 4.2 `api/auth/login.php`

```php
<?php
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
$password = (string)$body['password'];

if (!valid_email($email)) {
    json_error('Invalid email or password.', 'invalid_credentials', 401);
}

$stmt = $pdo->prepare("
    SELECT id, email, password_hash, first_name, last_name
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Vague error message on purpose (don't reveal which is wrong)
if (!$user || !password_verify($password, $user['password_hash'])) {
    json_error('Invalid email or password.', 'invalid_credentials', 401);
}

// Establish session
$_SESSION['user_id'] = (int)$user['id'];
session_regenerate_id(true);

json_success([
    'user' => [
        'id'         => (int)$user['id'],
        'email'      => $user['email'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
    ]
], 'Welcome back!');
```

### 4.3 `api/auth/logout.php`

```php
<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';

// Clear session data
$_SESSION = [];

// Delete the session cookie
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

session_destroy();

json_success(null, 'Logged out.');
```

### 4.4 `api/auth/session.php`

```php
<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/db.php';

$uid = current_user_id();

if (!$uid) {
    json_success(['logged_in' => false]);
}

$stmt = $pdo->prepare("
    SELECT id, email, first_name, last_name, phone, city, bio, avatar_url
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    // Stale session — user was deleted. Clear it.
    $_SESSION = [];
    session_destroy();
    json_success(['logged_in' => false]);
}

$user['id'] = (int)$user['id'];

json_success([
    'logged_in' => true,
    'user'      => $user,
]);
```

---

## 5. Frontend Integration

### 5.1 Update `login.html`

Find the form (around line 45):

```html
<form action="profile.html" method="GET" id="loginForm" onsubmit="event.preventDefault(); localStorage.setItem('tara_session','1'); window.location.href='home.html';">
```

**Replace the form opening tag with:**

```html
<form id="loginForm">
```

Add an error message container *just above* the submit button (around line 63):

```html
<div id="loginError" class="auth-error" style="display:none;color:#dc2626;font-size:14px;margin-bottom:12px;"></div>

<button type="submit" class="btn-auth" id="loginSubmit">Log In</button>
```

**Just before the closing `</body>` tag, add:**

```html
<script src="js/api.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();

    var email    = document.getElementById('email').value.trim();
    var password = document.getElementById('password').value;
    var errEl    = document.getElementById('loginError');
    var btn      = document.getElementById('loginSubmit');

    errEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Logging in…';

    Api.post('auth/login.php', { email: email, password: password })
        .then(function (data) {
            // Mirror session flag to localStorage so existing UI (main.js line ~75) keeps working
            localStorage.setItem('tara_session', '1');
            localStorage.setItem('tara_user', JSON.stringify(data.user));
            window.location.href = 'home.html';
        })
        .catch(function (err) {
            errEl.textContent = err.message;
            errEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Log In';
        });
});
</script>
```

### 5.2 Update `register.html`

Find the form (around line 45):

```html
<form action="profile.html" method="GET" id="registerForm" onsubmit="event.preventDefault(); localStorage.setItem('tara_session','1'); window.location.href='home.html';">
```

**Replace with:**

```html
<form id="registerForm">
```

Add an error container above the submit button:

```html
<div id="registerError" class="auth-error" style="display:none;color:#dc2626;font-size:14px;margin-bottom:12px;"></div>

<button type="submit" class="btn-auth" id="registerSubmit">Create Account</button>
```

**Add before `</body>`:**

```html
<script src="js/api.js"></script>
<script>
document.getElementById('registerForm').addEventListener('submit', function (e) {
    e.preventDefault();

    var name     = document.getElementById('name').value.trim();
    var email    = document.getElementById('email').value.trim();
    var password = document.getElementById('password').value;
    var errEl    = document.getElementById('registerError');
    var btn      = document.getElementById('registerSubmit');

    errEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Creating account…';

    Api.post('auth/register.php', { name: name, email: email, password: password })
        .then(function (data) {
            localStorage.setItem('tara_session', '1');
            localStorage.setItem('tara_user', JSON.stringify(data.user));
            window.location.href = 'home.html';
        })
        .catch(function (err) {
            errEl.textContent = err.message;
            errEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Create Account';
        });
});
</script>
```

### 5.3 Update `profile.html` Logout

Find the logout handler around line 455. It currently does:

```javascript
localStorage.removeItem('tara_session');
```

**Replace with:**

```javascript
Api.post('auth/logout.php', {})
    .catch(function () { /* ignore — log out client-side regardless */ })
    .then(function () {
        localStorage.removeItem('tara_session');
        localStorage.removeItem('tara_user');
        window.location.href = 'home.html';
    });
```

Also ensure `profile.html` includes `api.js` before its inline script:

```html
<script src="js/api.js"></script>
<!-- existing inline <script>...</script> -->
```

### 5.4 Update `js/main.js` Session Sync

In `main.js`, the navbar profile icon uses `localStorage.getItem('tara_session')` to decide where to link. This still works, but we want the server to be the source of truth. Add this at the very top of the `DOMContentLoaded` handler (around line 44):

```javascript
// ─── SESSION SYNC (server is source of truth) ────────────
if (typeof Api !== 'undefined') {
    Api.get('auth/session.php').then(function (data) {
        if (data.logged_in) {
            localStorage.setItem('tara_session', '1');
            localStorage.setItem('tara_user', JSON.stringify(data.user));
        } else {
            localStorage.removeItem('tara_session');
            localStorage.removeItem('tara_user');
        }
        // Refresh profile icon links now that we know the truth
        var loggedIn = data.logged_in;
        document.querySelectorAll('.nav-profile-icon').forEach(function (icon) {
            icon.href  = loggedIn ? 'profile.html' : 'login.html';
            icon.title = loggedIn ? 'My Profile' : 'Sign In';
        });
    }).catch(function () { /* offline / first run — leave UI as-is */ });
}
```

### 5.5 Include `api.js` Everywhere `main.js` Is Used

Search across all `.html` files for `<script src="js/main.js"></script>` and add `api.js` immediately before it:

```html
<script src="js/api.js"></script>
<script src="js/main.js"></script>
```

Affected files (based on the current codebase):

- `index.html`
- `home.html`
- `explore.html`
- `details.html`
- `map.html`
- `about.html`
- `contact.html`
- `plan.html`
- `profile.html`
- `saved.html`

---

## 6. Testing

### 6.1 Browser DevTools Smoke Test

In any page's console:

```javascript
// Should return logged_in: false initially
Api.get('auth/session.php').then(d => console.log(d));

// Register a test user
Api.post('auth/register.php', { name: 'Test User', email: 'test@test.com', password: '123456' })
   .then(d => console.log('Registered:', d));

// Confirm session is now active
Api.get('auth/session.php').then(d => console.log(d));

// Log out
Api.post('auth/logout.php', {}).then(d => console.log(d));

// Log back in
Api.post('auth/login.php', { email: 'test@test.com', password: '123456' })
   .then(d => console.log('Logged in:', d));
```

### 6.2 phpMyAdmin Verification

After registering, browse the `users` table:

- One row exists with `email = test@test.com`.
- `password_hash` starts with `$2y$10$...` (bcrypt format).
- `first_name = Test`, `last_name = User`.

### 6.3 UI Walk-Through

1. From <http://localhost/tara-pangasinan/>, click profile icon → land on `login.html` (because `tara_session` was cleared by the test logout above).
2. Click **Sign up for free** → `register.html`.
3. Fill the form: Name=`Maria Cruz`, Email=`maria@cruz.ph`, Password=`pangasinan2026`.
4. Submit → redirect to `home.html`.
5. Profile icon now links to `profile.html` (verify by hovering).
6. Open a new incognito window and confirm `home.html` shows the logged-out state — session is per-browser-profile.

### 6.4 Error Path Tests

- Register with the same email twice → see "That email is already registered."
- Register with `password=abc` → see "Password must be at least 6 characters."
- Login with wrong password → see "Invalid email or password."

---

## 7. Demo Talking Points

When showing this to the panel:

1. **"Passwords are never stored in plaintext."** Open phpMyAdmin, show the `password_hash` column — it's a bcrypt string. Mention that `password_hash()` and `password_verify()` are PHP built-ins designed exactly for this.
2. **"All inputs are validated server-side."** Show `validation.php`'s `require_fields`, `valid_email`, and the length checks in `register.php`.
3. **"SQL is parameterized."** Point to the `?` placeholders and `$stmt->execute([...])` calls — explain that this is what makes the code immune to SQL injection.
4. **"Sessions are server-side."** Open DevTools → Application → Cookies → show `PHPSESSID`. Explain the server holds the actual session state; the cookie is just an identifier.

---

## Done When

- [ ] All 4 endpoints (`register.php`, `login.php`, `logout.php`, `session.php`) exist and return valid JSON.
- [ ] `login.html` and `register.html` use AJAX submission (no `localStorage.setItem('tara_session','1')` in the HTML).
- [ ] `profile.html` logout calls `api/auth/logout.php`.
- [ ] `js/main.js` syncs session state on every page load.
- [ ] All HTML pages that use `main.js` also include `api.js` before it.
- [ ] You can register, log out, and log back in without page errors.
- [ ] Wrong password message is generic ("Invalid email or password") — not "user not found".
- [ ] The `users` table in phpMyAdmin shows real bcrypt hashes.

Continue to **[`05-SPOTS-MODULE.md`](./05-SPOTS-MODULE.md)** to replace `data/spots.json` with a database-backed API.
