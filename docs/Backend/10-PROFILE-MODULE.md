# 10 — Profile Module

> **Phase 10 of 11** · Owner: _Profile + Account lead_ · Depends on: 02, 03, 04

This phase wires the profile settings form on `profile.html` to the database. User details (name, email, phone, city, bio) and password changes persist server-side, not in `localStorage`.

---

## Goal

| Currently | After this phase |
|---|---|
| `loadProfileSettings()` (profile.html line 282) reads from `localStorage.tara_profile`. | Reads from `/api/profile/get.php` using the logged-in session. |
| `handleSaveProfile()` (line 307) writes to `localStorage`. | POSTs to `/api/profile/update.php`. |
| Change Password fields exist in the markup but do nothing. | If current password matches and new password is valid, hash is updated in DB. |
| Profile is local to one browser. | Profile follows the user across devices via session. |

---

## Files Affected

**New files**
- `api/profile/get.php`
- `api/profile/update.php`

**Edited files**
- `profile.html` — replace `loadProfileSettings()`, `handleSaveProfile()`, remove `localStorage` paths

**Touched tables** — `users` (all fields editable by user); password column updates require current-password verification

> **Schema column reminders (from `02-DATABASE.md` and `04-AUTH-MODULE.md`).** The `users` table uses `first_name` / `last_name` / `password_hash` (snake_case). The form's HTML input IDs are `pf-firstname` / `pf-lastname` (no underscore) and we **do not change those IDs**. The mapping happens in the JS — `data.user.first_name` flows into the field with ID `pf-firstname`, and vice versa on submit.

---

## API Endpoints

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| `GET`  | `/api/profile/get.php`    | Yes | Return current user's profile. |
| `POST` | `/api/profile/update.php` | Yes | Update profile fields. Optional password change in same call. |

> **Why merge profile + password into one endpoint?** Two reasons: (1) the UI is one form with one Save button, so atomic update matches the user's mental model; (2) a single transaction keeps the data consistent if the password change fails mid-update.

---

## Backend Code

### File 1: `api/profile/get.php`

```php
<?php
/**
 * GET /api/profile/get.php
 *
 * Returns the current logged-in user's profile fields.
 * Never returns the password hash.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, email, phone, city, bio, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Session points to a deleted user — destroy session and 401
    session_destroy();
    json_error('Session expired.', 401);
}

$user['id'] = (int) $user['id'];

json_success(['user' => $user]);
```

---

### File 2: `api/profile/update.php`

```php
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

$payload = read_json_body();
$user_id = $_SESSION['user_id'];

// ── Clean basic fields ───────────────────────────────────────
$first_name = clean_str($payload['first_name'] ?? '');
$last_name  = clean_str($payload['last_name']  ?? '');
$email      = clean_str($payload['email']      ?? '');
$phone      = clean_str($payload['phone']      ?? '');
$city       = clean_str($payload['city']       ?? '');
$bio        = clean_str($payload['bio']        ?? '');

// ── Validation ───────────────────────────────────────────────
if (mb_strlen($first_name) < 2 || mb_strlen($first_name) > 50) {
    json_error('First name must be 2–50 characters.', 400);
}
if (mb_strlen($last_name) < 2 || mb_strlen($last_name) > 50) {
    json_error('Last name must be 2–50 characters.', 400);
}
if (!valid_email($email)) {
    json_error('Invalid email address.', 400);
}
if ($phone !== '' && !preg_match('/^[\d\s()+\-]{7,25}$/', $phone)) {
    json_error('Phone number contains invalid characters.', 400);
}
if (mb_strlen($city) > 100) {
    json_error('City name is too long.', 400);
}
if (mb_strlen($bio) > 500) {
    json_error('Bio cannot exceed 500 characters.', 400);
}

// ── Email uniqueness (excluding own record) ──────────────────
$dup = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND id != ? LIMIT 1");
$dup->execute([$email, $user_id]);
if ($dup->fetchColumn()) {
    json_error('This email is already used by another account.', 409);
}

// ── Optional password change ─────────────────────────────────
$current_pw = $payload['current_password'] ?? '';
$new_pw     = $payload['new_password']     ?? '';
$password_changing = ($current_pw !== '' || $new_pw !== '');
$new_hash = null;

if ($password_changing) {
    if ($current_pw === '' || $new_pw === '') {
        json_error('Both current and new passwords are required to change password.', 400);
    }
    if (strlen($new_pw) < 8) {
        json_error('New password must be at least 8 characters.', 400);
    }
    if ($new_pw === $current_pw) {
        json_error('New password must differ from current password.', 400);
    }

    // Verify current password
    $pw_stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
    $pw_stmt->execute([$user_id]);
    $row = $pw_stmt->fetch();
    if (!$row || !password_verify($current_pw, $row['password_hash'])) {
        json_error('Current password is incorrect.', 401);
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
```

> **Defense talking point — `password_verify()` and constant-time comparison.** PHP's `password_verify()` does a constant-time comparison of the stored hash against the supplied plaintext. Constant-time is important: a naive `===` comparison can leak the correct password byte-by-byte through timing attacks. Using built-in functions for password operations is the security industry's gold standard — never roll your own.
>
> **Defense talking point — Email-already-taken check.** The `users.email` column has a `UNIQUE` constraint (Phase 02). Even without our `dup` check, MySQL would reject the insert with a 23000 error. But returning a clean 409 with a friendly message gives users an instant, understandable response instead of a generic 500.

---

## Frontend Integration

### Step 1 — Replace profile load + save in `profile.html`

**Find** the entire block from lines 274 to 328 — the `PROFILE_DEFAULTS`, `loadProfileSettings`, `handleSaveProfile`, `discardProfile` functions and the bottom `loadProfileSettings()` call.

**Replace with:**

```javascript
// Keep a local snapshot so "Discard" works without re-fetching
let cachedProfile = null;

function loadProfileSettings() {
    Api.get('profile/get.php')
        .then(function (data) {
            cachedProfile = data.user;
            applyProfileToForm(cachedProfile);
            updateSidebarDisplay(cachedProfile);
        })
        .catch(function (err) {
            if (err.code === 'AUTH_REQUIRED') {
                window.location.href = 'login.html';
                return;
            }
            console.error('Could not load profile:', err);
            alert('Could not load your profile. Please try again.');
        });
}

// API uses snake_case (first_name); DOM IDs use no separator (pf-firstname).
// This function bridges the two naming conventions.
function applyProfileToForm(data) {
    document.getElementById('pf-firstname').value = data.first_name || '';
    document.getElementById('pf-lastname').value  = data.last_name  || '';
    document.getElementById('pf-email').value     = data.email      || '';
    document.getElementById('pf-phone').value     = data.phone      || '';
    document.getElementById('pf-city').value      = data.city       || '';
    document.getElementById('pf-bio').value       = data.bio        || '';

    // Always clear password fields after a (re)load
    document.getElementById('pf-current-pw').value = '';
    document.getElementById('pf-new-pw').value     = '';
    document.getElementById('pf-confirm-pw').value = '';
}

function updateSidebarDisplay(data) {
    const nameEl  = document.querySelector('.sidebar-card h3');
    const emailEl = document.querySelector('.sidebar-email');
    const locEl   = document.querySelector('.sidebar-location');
    if (nameEl)  nameEl.textContent  = (data.first_name + ' ' + data.last_name).trim();
    if (emailEl) emailEl.textContent = data.email;
    if (locEl) {
        const textNodes = [...locEl.childNodes].filter(n => n.nodeType === 3);
        const txt = textNodes[textNodes.length - 1];
        if (txt) txt.textContent = ' ' + (data.city || '');
    }
}

function handleSaveProfile(e) {
    e.preventDefault();

    const currentPw = document.getElementById('pf-current-pw').value;
    const newPw     = document.getElementById('pf-new-pw').value;
    const confirmPw = document.getElementById('pf-confirm-pw').value;

    // Client-side password sanity check before the round-trip
    if (newPw || currentPw || confirmPw) {
        if (newPw !== confirmPw) {
            alert('New password and confirmation do not match.');
            return;
        }
    }

    // Translate DOM IDs (pf-firstname) → API field names (first_name)
    const payload = {
        first_name: document.getElementById('pf-firstname').value.trim(),
        last_name:  document.getElementById('pf-lastname').value.trim(),
        email:      document.getElementById('pf-email').value.trim(),
        phone:      document.getElementById('pf-phone').value.trim(),
        city:       document.getElementById('pf-city').value.trim(),
        bio:        document.getElementById('pf-bio').value.trim()
    };

    if (currentPw && newPw) {
        payload.current_password = currentPw;
        payload.new_password     = newPw;
    }

    const btn = e.target.querySelector('.btn-save-profile');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Saving…';

    Api.post('profile/update.php', payload)
        .then(function (data) {
            cachedProfile = data.user;
            applyProfileToForm(cachedProfile);
            updateSidebarDisplay(cachedProfile);

            const msg = document.getElementById('profile-save-msg');
            if (msg) {
                msg.style.display = 'flex';
                const span = msg.querySelector('span');
                if (span) {
                    span.textContent = data.password_changed
                        ? 'Profile and password updated.'
                        : 'Profile updated.';
                }
                setTimeout(function () { msg.style.display = 'none'; }, 3500);
            }
        })
        .catch(function (err) {
            alert('Could not save: ' + err.message);
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
}

function discardProfile() {
    if (cachedProfile) {
        applyProfileToForm(cachedProfile);
    } else {
        loadProfileSettings();
    }
}

// Kick off
loadProfileSettings();
```

> **Why we cache the profile.** The Discard button should restore what the user last saw — not what's in the database right this second (the server data might have changed in another tab, which would surprise the user). The cached snapshot is the right semantic.

### Step 2 — Remove old `tara_profile` localStorage paths

Search `profile.html` for any remaining references to `tara_profile`:

```bash
grep -n "tara_profile" profile.html
```

If anything remains outside the block we just replaced, delete those lines. The localStorage key should appear zero times in the file by the end of this phase.

### Step 3 — Confirm sidebar logout still works

Phase 04 already wired the logout button to `Api.post('auth/logout.php')` and `window.location.href = 'login.html'`. Sanity-check that this still works after your edits — you'll want it during the demo.

---

## Testing

### Test 1 — Load profile from DB
1. Register `carlo@test.com` (Phase 04). Log in.
2. Open `profile.html`.
3. **Expect:** Fields populate from DB (not from hardcoded `PROFILE_DEFAULTS` — those should be deleted).
4. Verify sidebar greeting matches.
5. Check DevTools Network: GET to `profile/get.php` returns `first_name` and `last_name` (snake_case).

### Test 2 — Save profile changes
1. Change First Name to "James".
2. Click Save Changes.
3. **Expect:** Green save banner appears for ~3.5 seconds.
4. Refresh page. **Expect:** First Name still "James" (persisted).
5. Verify: `SELECT first_name FROM users WHERE email='carlo@test.com';` → `James`.

### Test 3 — Discard reverts UI to last-loaded state
1. Type something into Bio.
2. Click Discard.
3. **Expect:** Bio field reverts to last-loaded value.

### Test 4 — Email conflict
1. Have a second account `anna@test.com` exist in `users`.
2. Logged in as Carlo, change Email to `anna@test.com`. Click Save.
3. **Expect:** Alert: *"This email is already used by another account."*

### Test 5 — Password change (happy path)
1. Logged in as Carlo. Fill: Current="passw0rd", New="newpassw0rd", Confirm="newpassw0rd".
2. Click Save Changes.
3. **Expect:** Success banner mentions "Profile and password updated."
4. Log out. Try logging in with old password → fails. With new → succeeds.

### Test 6 — Password change (wrong current password)
1. Fill: Current="WRONG", New="newpassw0rd", Confirm="newpassw0rd".
2. Click Save.
3. **Expect:** Alert: *"Current password is incorrect."* — profile fields are NOT updated.

### Test 7 — Password change (mismatched confirm)
1. Fill: Current="ok", New="newpassw0rd", Confirm="different".
2. Click Save.
3. **Expect:** Alert client-side: *"New password and confirmation do not match."* — no API call sent.

### Test 8 — Validation
1. Try a 1-character first name → 400.
2. Try a 600-character bio → 400.
3. Try phone with letters → 400.

### Test 9 — Unauthenticated access
1. Log out. Manually navigate to `profile.html`.
2. **Expect:** Redirects to `login.html` (because the `loadProfileSettings()` call gets `AUTH_REQUIRED`).

---

## File Structure After Phase 10

```
api/
├── auth/                    (Phase 04)
├── bookings/                (Phase 08)
├── contact/                 (Phase 09)
├── profile/
│   ├── get.php              ✓ NEW
│   └── update.php           ✓ NEW
├── promos/                  (Phase 08)
├── reviews/                 (Phase 07)
├── saved/                   (Phase 06)
└── spots/                   (Phase 05/08)
profile.html                 (localStorage path removed; reads/writes DB)
```

---

## Done When

- [ ] Profile fields load from `/api/profile/get.php` on every page open.
- [ ] Saving updates the `users` row (`first_name`, `last_name`, etc.) and reflects in the sidebar immediately.
- [ ] Discard restores the last-loaded values without a server round-trip.
- [ ] Password change requires current password to match; uses `password_verify()`.
- [ ] No references to `tara_profile` remain in `profile.html`.
- [ ] Email uniqueness is enforced server-side with a friendly 409 message.
- [ ] DOM IDs (`pf-firstname`) → API fields (`first_name`) mapping is correct on both load and save.

Continue to **[`11-CHECKLIST.md`](./11-CHECKLIST.md)** for the end-to-end test plan and demo script.
