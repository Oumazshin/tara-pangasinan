# 09 — Contact Module

> **Phase 9 of 11** · Owner: _Contact lead_ · Depends on: 02, 03

This is the smallest module in the project — a single endpoint that persists contact-form submissions to the database. It's a good warm-up for anyone unfamiliar with PHP form handling and a clean demonstration of input validation done correctly.

---

## Goal

| Currently | After this phase |
|---|---|
| `contact.html` form's `onsubmit` is `event.preventDefault(); this.reset();` — the message disappears into the void. | Form POSTs to `/api/contact/submit.php`; message is stored in the `contact_messages` table. |
| Logged-in users still type their name and email manually. | If the user is logged in, name + email are prefilled from their session. |
| No basic-bot protection. | Honeypot field + rate limiting per IP (max 5 submissions / hour). |

---

## Files Affected

**New files**
- `api/contact/submit.php`

**Edited files**
- `contact.html` — replace the inline `onsubmit` handler; add a hidden honeypot field; prefill for logged-in users

**Touched tables** — `contact_messages` (already created in Phase 02)

> **Schema column reminders (from `02-DATABASE.md`).** The `contact_messages` table uses `first_name`, `last_name`, `submitted_at`, and `ip_address`. The frontend form's input IDs are `fname` / `lname` — we keep those IDs in the existing HTML (no markup change needed) and translate them to the snake_case DB column names inside the PHP handler.

---

## API Endpoints

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| `POST` | `/api/contact/submit.php` | No | Store one contact message. |

> **Why no `list.php`?** The frontend has no inbox view. The admin will read messages directly in phpMyAdmin during the demo. If you have time, building an `admin/messages.html` page that reads from a `/api/contact/list.php` (auth-gated to a hardcoded admin email) is a great stretch goal.

---

## Backend Code

### File: `api/contact/submit.php`

```php
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
    json_error('First name must be 2–50 characters.', 400);
}
if (mb_strlen($last_name) < 2 || mb_strlen($last_name) > 50) {
    json_error('Last name must be 2–50 characters.', 400);
}
if (!valid_email($email)) {
    json_error('Please enter a valid email address.', 400);
}

$allowed_subjects = ['tour', 'support', 'partnership', 'other'];
if (!in_array($subject, $allowed_subjects, true)) {
    json_error('Invalid subject category.', 400);
}

$msg_len = mb_strlen($message);
if ($msg_len < 10) {
    json_error('Message must be at least 10 characters.', 400);
}
if ($msg_len > 2000) {
    json_error('Message cannot exceed 2000 characters.', 400);
}

// ── Rate limit: max 5 submissions / IP / hour ────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$rate_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM contact_messages
    WHERE ip_address = ? AND submitted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$rate_stmt->execute([$ip]);
if ((int) $rate_stmt->fetchColumn() >= 5) {
    json_error('Too many messages. Please try again in an hour.', 429);
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
```

> **Defense talking point — Honeypot vs CAPTCHA.** CAPTCHAs (reCAPTCHA, hCaptcha) require third-party network calls — disallowed in this project. A honeypot is a CSS-hidden form field that real users never see, never tab into, and never fill. Bots blindly fill *every* field. If the honeypot has content, we silently accept-and-discard (returning success so the bot doesn't try harder). This catches >95% of dumb spam without any external dependency.
>
> **Defense talking point — Why rate-limit by IP, not by email?** Email is trivially fakeable on a public form. IP is harder to rotate without infrastructure. It's not bulletproof (corporate NATs, mobile networks), but it raises the cost of abuse without requiring login. The `idx_ip_submitted` index on `(ip_address, submitted_at)` makes the lookup `O(log n)` even with millions of rows.

---

## Frontend Integration

### Step 1 — Add honeypot field to `contact.html`

**Find** the `<form id="contactForm">` opening tag (around line 74). **Insert** this hidden field as the first child inside the form:

```html
<!-- Honeypot — must stay empty. Real users never see this. -->
<div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
    <label for="website-trap">Website (leave blank)</label>
    <input type="text" id="website-trap" name="website" tabindex="-1" autocomplete="off">
</div>
```

> **Why `position:absolute;left:-9999px`?** A common bot bypass is to skip fields styled `display:none` (since some bots are smart enough to detect that). Moving the field off-screen but keeping it in the DOM bypasses that heuristic. `tabindex="-1"` also keeps the field out of keyboard navigation for users who turn off CSS.

### Step 2 — Replace the inline submit handler in `contact.html`

**Find** this line (around line 74):

```html
<form id="contactForm" onsubmit="event.preventDefault(); document.getElementById('contactSuccess').style.display='flex'; this.reset();">
```

**Replace with:**

```html
<form id="contactForm">
```

(Strip the inline handler — we're moving logic to a script block.)

### Step 3 — Add the submit script

**Add** this `<script>` block at the bottom of `contact.html` (just before `</body>`, after `<script src="js/api.js"></script>`):

```html
<script src="js/api.js"></script>
<script>
(function () {
    var form    = document.getElementById('contactForm');
    var success = document.getElementById('contactSuccess');

    // Prefill name + email if user is logged in.
    // Auth API returns first_name / last_name (snake_case) — map to form IDs.
    var session = localStorage.getItem('tara_session');
    if (session) {
        Api.get('auth/session.php').then(function (data) {
            if (data.user) {
                document.getElementById('fname').value = data.user.first_name || '';
                document.getElementById('lname').value = data.user.last_name  || '';
                document.getElementById('email').value = data.user.email      || '';
            }
        }).catch(function () { /* not logged in; ignore */ });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var btn = form.querySelector('button[type="submit"]');
        var originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Sending…';

        // Hide any previous success/error alerts
        success.style.display = 'none';
        var oldErr = document.getElementById('contact-error');
        if (oldErr) oldErr.remove();

        var payload = {
            fname:   document.getElementById('fname').value.trim(),
            lname:   document.getElementById('lname').value.trim(),
            email:   document.getElementById('email').value.trim(),
            subject: document.getElementById('subject').value,
            message: document.getElementById('message').value.trim(),
            website: document.getElementById('website-trap').value   // honeypot
        };

        Api.post('contact/submit.php', payload)
            .then(function (data) {
                success.style.display = 'flex';
                success.innerHTML =
                    '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' +
                    ' ' + (data.message || 'Your message has been sent successfully!');
                form.reset();
                // Restore prefilled values for next message
                if (session) {
                    Api.get('auth/session.php').then(function (d) {
                        if (d.user) {
                            document.getElementById('fname').value = d.user.first_name || '';
                            document.getElementById('lname').value = d.user.last_name  || '';
                            document.getElementById('email').value = d.user.email      || '';
                        }
                    });
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(function (err) {
                var errEl = document.createElement('div');
                errEl.id = 'contact-error';
                errEl.style.cssText = 'background:#fef2f2;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:8px;';
                errEl.innerHTML = '<strong>✗</strong> ' + err.message;
                form.parentNode.insertBefore(errEl, form);
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = originalText;
            });
    });
})();
</script>
```

### Step 4 — Ensure `js/api.js` is included

The script above depends on `Api` from `js/api.js`. Make sure `contact.html` has:

```html
<script src="js/api.js"></script>
```

…immediately before the inline script block.

---

## Testing

### Test 1 — Happy path
1. Open `contact.html` (logged out).
2. Fill: John, Doe, john@test.com, subject "Tour Inquiry", message "How long is the Hundred Islands tour?"
3. Click Send Message.
4. **Expect:** Green success alert. Form clears. Scroll to top.
5. Verify in MySQL: `SELECT first_name, last_name, email, subject, ip_address, submitted_at FROM contact_messages ORDER BY id DESC LIMIT 1;` — row exists.

### Test 2 — Validation (short message)
1. Type a 5-character message.
2. **Expect:** Red error: "Message must be at least 10 characters."
3. Form is NOT cleared (user keeps their text).

### Test 3 — Validation (bad email)
1. Type `not-an-email` in the email field.
2. The browser's native `type="email"` validation may catch it first — that's fine.
3. If you bypass via DevTools (set `type="text"`), the server rejects with 400.

### Test 4 — Honeypot defeats bots
1. Open DevTools console. Run:
   ```js
   fetch('api/contact/submit.php', {
     method: 'POST',
     headers: {'Content-Type': 'application/json'},
     body: JSON.stringify({fname:'B', lname:'O', email:'b@o.io', subject:'tour', message:'spam spam spam', website:'http://spam.io'})
   }).then(r => r.json()).then(console.log);
   ```
2. **Expect:** `{success: true, data: {stored: false}, message: 'Thanks for your message.'}`
3. Verify in MySQL: no new row. Bot was fooled.

### Test 5 — Rate limiting
1. Submit 5 valid messages in a row from the same browser.
2. On the 6th attempt within the hour, **expect** HTTP 429: *"Too many messages. Please try again in an hour."*
3. Verify in MySQL: `SELECT COUNT(*) FROM contact_messages WHERE ip_address = '127.0.0.1' AND submitted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR);` returns 5.

### Test 6 — Prefill when logged in
1. Log in.
2. Open `contact.html`.
3. **Expect:** First name / last name / email fields are populated from the session.

---

## File Structure After Phase 9

```
api/
├── auth/                    (Phase 04)
├── bookings/                (Phase 08)
├── contact/
│   └── submit.php           ✓ NEW
├── promos/                  (Phase 08)
├── reviews/                 (Phase 07)
├── saved/                   (Phase 06)
└── spots/                   (Phase 05/08)
contact.html                 (form rewired; honeypot added)
```

---

## Done When

- [ ] Submitting the contact form inserts a row in `contact_messages` with `first_name`, `last_name`, and `ip_address` populated.
- [ ] Form shows a green success alert on success, red error on failure.
- [ ] Honeypot blocks bots without showing them an error.
- [ ] Rate limit kicks in after 5 submissions per IP per hour.
- [ ] Logged-in users see their name + email prefilled.
- [ ] No JavaScript errors in the console.

Continue to **[`10-PROFILE-MODULE.md`](./10-PROFILE-MODULE.md)** to make profile editing persistent.
