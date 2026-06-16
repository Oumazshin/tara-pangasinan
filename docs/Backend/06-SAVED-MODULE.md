# 06 — Saved Spots Module

**Goal:** Persist a user's "saved" spots to the `saved_spots` table when logged in, while keeping the existing localStorage-based experience for guests. On login, optionally merge guest saves into the DB.

**Time estimate:** 1 hour.

---

## 1. Files Affected

| File | Action |
|---|---|
| `api/saved/toggle.php` | **NEW** |
| `api/saved/list.php`   | **NEW** |
| `js/main.js`            | MODIFIED — `toggleCardSave()` and `getSavedIds()` become server-aware |
| `saved.html`            | MODIFIED — load saved list from server |
| `details.html`          | MODIFIED — same pattern for the sidebar heart button |

---

## 2. Strategy Recap

```
                ┌──────────────┐
                │  User clicks │
                │   the heart  │
                └──────┬───────┘
                       │
        ┌──────────────┴──────────────┐
        ▼                             ▼
  ╔═══════════╗                 ╔═══════════╗
  ║ Logged in ║                 ║   Guest   ║
  ╚═════╦═════╝                 ╚═════╦═════╝
        │                             │
   AJAX POST                    localStorage
   saved/toggle.php             tara_saved
        │                             │
   saved_spots                   (existing behaviour)
   table updated
```

The same UI element (`btn-heart`) feeds either path. The decision is made in JS by checking `localStorage.tara_session`.

---

## 3. Database Tables Used

`saved_spots` (already created in Phase 2):

```sql
CREATE TABLE saved_spots (
    user_id   INT UNSIGNED NOT NULL,
    spot_id   VARCHAR(80)  NOT NULL,
    saved_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, spot_id),
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE
);
```

The composite PK ensures "save twice = save once" — clean idempotent behaviour.

---

## 4. API Endpoints

### 4.1 `POST /api/saved/toggle.php`

**Auth required.**

**Request body:**

```json
{ "spot_id": "hundred-islands" }
```

**Optional:** `{ "spot_id": "...", "force": "save" }` or `{"force": "remove"}` to override toggle behaviour (used during merge).

**Response (toggled to saved):**

```json
{ "success": true, "data": { "spot_id": "hundred-islands", "saved": true }, "message": "Saved to your list!" }
```

**Response (toggled to removed):**

```json
{ "success": true, "data": { "spot_id": "hundred-islands", "saved": false }, "message": "Removed from saved." }
```

### 4.2 `GET /api/saved/list.php`

**Auth required.**

**Response:**

```json
{
  "success": true,
  "data": {
    "saved_ids": ["hundred-islands", "patar-beach"],
    "count": 2
  }
}
```

(IDs only — the existing frontend already has the full spots list cached in memory from `spots/list.php`.)

---

## 5. Backend Code

### 5.1 `api/saved/toggle.php`

```php
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed.', 'method_not_allowed', 405);
}

$userId = require_login();

$body = read_json_body();
require_fields($body, ['spot_id']);

$spotId = clean_str($body['spot_id'], 80);
$force  = $body['force'] ?? null;        // 'save' | 'remove' | null

if (!valid_slug($spotId)) {
    json_error('Invalid spot id.', 'invalid_id', 422);
}

// Confirm the spot exists (foreign key would catch this, but we want a nicer error)
$exists = $pdo->prepare("SELECT 1 FROM spots WHERE id = ? LIMIT 1");
$exists->execute([$spotId]);
if (!$exists->fetch()) {
    json_error('That destination does not exist.', 'not_found', 404);
}

// Current state
$check = $pdo->prepare("SELECT 1 FROM saved_spots WHERE user_id = ? AND spot_id = ? LIMIT 1");
$check->execute([$userId, $spotId]);
$alreadySaved = (bool)$check->fetch();

// Decide the new state
if ($force === 'save')        $shouldSave = true;
elseif ($force === 'remove')  $shouldSave = false;
else                          $shouldSave = !$alreadySaved;

if ($shouldSave && !$alreadySaved) {
    $ins = $pdo->prepare("INSERT INTO saved_spots (user_id, spot_id) VALUES (?, ?)");
    $ins->execute([$userId, $spotId]);
    json_success(['spot_id' => $spotId, 'saved' => true], 'Saved to your list!');
}

if (!$shouldSave && $alreadySaved) {
    $del = $pdo->prepare("DELETE FROM saved_spots WHERE user_id = ? AND spot_id = ?");
    $del->execute([$userId, $spotId]);
    json_success(['spot_id' => $spotId, 'saved' => false], 'Removed from saved.');
}

// No-op (e.g., force=save but already saved)
json_success(['spot_id' => $spotId, 'saved' => $alreadySaved], 'No change.');
```

### 5.2 `api/saved/list.php`

```php
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

$userId = require_login();

$stmt = $pdo->prepare("
    SELECT spot_id
    FROM saved_spots
    WHERE user_id = ?
    ORDER BY saved_at DESC
");
$stmt->execute([$userId]);

$ids = array_column($stmt->fetchAll(), 'spot_id');

json_success([
    'saved_ids' => $ids,
    'count'     => count($ids),
]);
```

---

## 6. Frontend Integration

### 6.1 Strategy: A Single Toggle Function That Knows About Login

Update `js/main.js`. Find the existing global helpers (lines 3–4):

```javascript
function getSavedIds() { return JSON.parse(localStorage.getItem('tara_saved') || '[]'); }
function setSavedIds(s) { localStorage.setItem('tara_saved', JSON.stringify(s)); }
```

**Replace with:**

```javascript
function getSavedIds() { return JSON.parse(localStorage.getItem('tara_saved') || '[]'); }
function setSavedIds(s) { localStorage.setItem('tara_saved', JSON.stringify(s)); }

function isLoggedIn() { return !!localStorage.getItem('tara_session'); }

/**
 * Hydrate the localStorage cache from the server when logged in.
 * Call once on every page load — silent failure if not logged in.
 */
function syncSavedFromServer() {
    if (!isLoggedIn() || typeof Api === 'undefined') return Promise.resolve();
    return Api.get('saved/list.php')
        .then(function (data) { setSavedIds(data.saved_ids || []); })
        .catch(function () { /* offline — keep cached */ });
}
```

### 6.2 Update `toggleCardSave`

Around `js/main.js` line 21, replace the whole function:

```javascript
function toggleCardSave(e, id) {
    e.preventDefault();
    e.stopPropagation();

    var s = getSavedIds();
    var idx = s.indexOf(id);
    var removing = idx > -1;

    // Optimistic UI update — flip immediately
    if (removing) s.splice(idx, 1); else s.push(id);
    setSavedIds(s);
    paintHeartButtons(id, !removing);
    showToast(removing ? 'Removed from saved' : 'Saved to your list!', removing ? 'info' : 'success');

    // If logged in, also persist to server
    if (isLoggedIn() && typeof Api !== 'undefined') {
        Api.post('saved/toggle.php', {
            spot_id: id,
            force:   removing ? 'remove' : 'save'
        }).catch(function (err) {
            // Roll back UI if server rejected
            var rolled = getSavedIds();
            var i = rolled.indexOf(id);
            if (removing && i === -1)       { rolled.push(id);    setSavedIds(rolled); }
            else if (!removing && i !== -1) { rolled.splice(i,1); setSavedIds(rolled); }
            paintHeartButtons(id, removing);
            showToast('Could not save: ' + err.message, 'info');
        });
    }
}

/**
 * Repaint every heart button on the page for a given spot id.
 */
function paintHeartButtons(id, isSaved) {
    document.querySelectorAll('[data-save-id="' + id + '"]').forEach(function (btn) {
        btn.classList.toggle('saved', isSaved);
        btn.title = isSaved ? 'Remove from saved' : 'Save to list';
        var svg = btn.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill',   isSaved ? '#e63946' : 'none');
            svg.setAttribute('stroke', isSaved ? '#e63946' : 'currentColor');
        }
    });
}
```

> The pattern here is **optimistic UI**: update the screen first, then sync to the server. If the server rejects, roll back. This makes the heart click feel instant — no perceptible latency.

### 6.3 Hydrate on Page Load

Inside the `DOMContentLoaded` handler in `js/main.js`, right after the session sync added in Phase 04:

```javascript
// ─── SAVED SPOTS HYDRATE ─────────────────────────────────
syncSavedFromServer().then(function () {
    // Repaint hearts on the page (cards may have rendered already with stale state)
    var savedSet = getSavedIds();
    savedSet.forEach(function (id) { paintHeartButtons(id, true); });
});
```

### 6.4 Update `saved.html`

The current `saved.html` reads `tara_saved` directly. Since we're now hydrating that from the server on every page load (via `main.js`), no changes are strictly needed.

**However**, if the user lands directly on `saved.html` without visiting another page first, `tara_saved` may be empty. Add a hydration call inside the existing inline script. Near **line 71**, where it starts with `const CAT_COLORS_SAVED`, add at the very top of the script:

```javascript
// Hydrate saved IDs from server before rendering
if (localStorage.getItem('tara_session') && typeof Api !== 'undefined') {
    Api.get('saved/list.php')
       .then(function (data) {
            localStorage.setItem('tara_saved', JSON.stringify(data.saved_ids || []));
            if (window._spotsCache) renderSaved(window._spotsCache);
       })
       .catch(function () { /* fall back to cached */ });
}
```

Also ensure `<script src="js/api.js"></script>` loads before the inline script (already done in Phase 05).

### 6.5 (Optional) Merge Guest Saves On Login

Best UX: a guest who saved 3 spots, then signed up, should not lose them. Add this to the login success handler in `login.html` (built in Phase 04):

```javascript
Api.post('auth/login.php', { email: email, password: password })
    .then(function (data) {
        localStorage.setItem('tara_session', '1');
        localStorage.setItem('tara_user', JSON.stringify(data.user));

        // Merge any guest saves into the server
        var guestSaves = JSON.parse(localStorage.getItem('tara_saved') || '[]');
        var merges = guestSaves.map(function (id) {
            return Api.post('saved/toggle.php', { spot_id: id, force: 'save' })
                      .catch(function () { /* ignore per-item */ });
        });

        Promise.all(merges).then(function () {
            window.location.href = 'home.html';
        });
    })
```

Apply the same in `register.html`'s submit handler (after `auth/register.php` succeeds).

---

## 7. Testing

### 7.1 Guest Flow

1. Log out (clear `tara_session` or use a fresh browser).
2. Click any heart on `explore.html` → toast appears, heart turns red.
3. Reload page → heart still red (localStorage persists).
4. Open `saved.html` → spot listed.

### 7.2 Logged-In Flow

1. Log in (via Phase 04's flows).
2. DevTools → Network → click an unsaved heart → confirm `POST saved/toggle.php` returns `{saved: true}`.
3. phpMyAdmin → `saved_spots` table → confirm the row exists with your `user_id`.
4. Log out, then log back in. Reload `explore.html`. The same heart should still be red — proves the server is the source of truth.

### 7.3 Merge Flow (If Implemented)

1. Log out. Save 2 spots as guest (both go into localStorage).
2. Log in. Network tab shows 2 `saved/toggle.php` calls with `force=save`.
3. `saved_spots` table now has those 2 rows under your user_id.

---

## 8. Common Pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Heart flips back after a moment | Server rejected the toggle (often 401 auth failure). Check Network → response. | Confirm `localStorage.tara_session` is `'1'` AND server session is alive. The fallback session check (Phase 04) should keep these in sync. |
| `saved_spots` row not created but UI says saved | API call failed silently. | Open DevTools Console → check for `ApiError`. |
| Spot ID `null` sent | The `data-save-id` attribute is missing or empty | Check the `cardHTML` template renders `data-save-id="..."` with the real id. |

---

## 9. File Tree After This Phase

```
api/
├── auth/                 (Phase 04)
├── saved/
│   ├── toggle.php        ✓ NEW
│   └── list.php          ✓ NEW
└── spots/                (Phase 05)
js/
└── main.js               (toggleCardSave + getSavedIds + syncSavedFromServer)
```

---

## Done When

- [ ] `api/saved/toggle.php` and `api/saved/list.php` exist and respond correctly.
- [ ] Logged-in users see their saves persist across logouts.
- [ ] Guests still get the localStorage experience (no auth required for save UI to work).
- [ ] (Optional) Guest saves are merged into the server on login.
- [ ] Heart UI updates feel instant — no perceptible latency.

Continue to **[`07-REVIEWS-MODULE.md`](./07-REVIEWS-MODULE.md)** to make `details.html` reviews real.
