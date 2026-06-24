# CLAUDE.md — Tara Pangasinan

This file orients Claude Code (and any future contributor) to the project quickly. It is the canonical "read first" handoff: what the project is, how to run it, where everything lives, and the conventions that must be preserved.

For human-facing orientation see [README.md](README.md). For the latest status and recent changes see [docs/PROJECT_CURRENT_STATUS.md](docs/PROJECT_CURRENT_STATUS.md).

---

## 1. Project Overview

**Tara Pangasinan** is a tourism web application promoting Pangasinan Province. It is the final project for **COMP-20163 Web Development** at PUP CCIS (Group 5, BSCS).

| Member | Role |
|---|---|
| Bacolor, James Clark | Frontend Lead & UI/UX |
| Soriano, Shouma King | Backend Lead |
| Caole, Stephanie | UI/UX Designer |
| Lituco, Jessica Jhoanne | UI/UX Designer |
| Guarin, Pauline | Documentation Lead |

**Status (as of 2026-06-23):** Feature-complete. All 11 backend modules wired to the frontend. Ready for defense. See [docs/PROJECT_CURRENT_STATUS.md](docs/PROJECT_CURRENT_STATUS.md).

---

## 2. Hard Constraints (Do Not Violate)

This project is graded against a course rubric that mandates the stack. Before suggesting or making any change, confirm it does not break these:

- **No frameworks.** No React, Vue, Tailwind, Bootstrap, Laravel. Vanilla HTML5 + CSS3 + JavaScript (ES5/ES6) on the client; native PHP 8 + PDO on the server.
- **No build step.** No webpack, no SCSS, no `npm install`. Files are served directly by Apache.
- **No package managers.** No `node_modules/`, no Composer, no `vendor/`. Anything imported must be a CDN-free, repo-local file (or a `<script>`/`<link>` you can justify).
- **No REST verbs beyond GET/POST.** Every mutation is `POST`. This keeps the `js/api.js` wrapper simple and matches what the panel saw in class.
- **PDO prepared statements only.** No string concatenation into SQL. No `$pdo->query()` containing user input. The code-audit checklist in [docs/Backend/11-CHECKLIST.md](docs/Backend/11-CHECKLIST.md) explicitly looks for this.
- **`password_hash()` / `password_verify()` only.** Never store, log, or compare plaintext passwords.

When in doubt: read [docs/Backend/00-ARCHITECTURE.md](docs/Backend/00-ARCHITECTURE.md) — it explains *why* each constraint exists.

---

## 3. Local Setup

Prerequisite: **XAMPP** with PHP 8.0+, Apache, and MySQL.

1. Project lives at `C:\xampp\htdocs\tara-pangasinan\` (Windows) or `/Applications/XAMPP/htdocs/tara-pangasinan/` (macOS).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Create the database in phpMyAdmin:
   ```sql
   CREATE DATABASE tara_pangasinan
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```
4. Import the SQL files in this order:
   - `sql/schema.sql` — 14 tables (drops and recreates; safe to re-run during dev, **wipes data**)
   - `sql/seed-lookups.sql` — tours, addons, promos
   - `sql/seed-spots.sql` — the 8 destinations and their child rows
   - `sql/seed-demo.sql` — 5 test users, 13 reviews, sample bookings (optional but recommended for the demo)
5. Adjust `config/database.php` if your local MySQL isn't on XAMPP defaults (`root` / empty password).
6. Open `http://localhost/tara-pangasinan/`.

**Demo credentials:** `demo@panel.test` / `panelpass1` (seeded by `seed-demo.sql`).

---

## 4. Project Structure

```
tara-pangasinan/
├── api/                          PHP endpoints — all return JSON
│   ├── auth/         register.php, login.php, logout.php, session.php
│   ├── bookings/     create.php, list.php, update.php, cancel.php, delete.php
│   ├── contact/      submit.php
│   ├── profile/      get.php, update.php
│   ├── promos/       validate.php
│   ├── reviews/      list.php, create.php, update.php, delete.php
│   ├── saved/        toggle.php, list.php
│   └── spots/        list.php, get.php, tours.php
├── assets/
│   ├── icons/                    Category, social, UI icons + image-fallback.svg
│   └── images/
│       ├── spots/                Bundled destination photos (~6 MB total)
│       └── reviews/              User-uploaded review photos (created at runtime)
├── components/
│   ├── navbar.html               Shared navbar, injected by js/components.js
│   └── footer.html               Shared footer
├── config/
│   └── database.php              DB credentials — gitignored locally
├── css/                          Stylesheets (global/ + pages/)
├── data/
│   └── spots.json                Original seed source — NO LONGER fetched by the browser
├── docs/                         Documentation (see § 9)
├── includes/                     Shared PHP helpers — required by every endpoint
│   ├── auth.php                  require_login() — 401 if no session
│   ├── db.php                    PDO singleton ($pdo)
│   ├── response.php              json_success(), json_error(), read_json_body()
│   ├── session.php               session_start() with HttpOnly + SameSite=Lax
│   └── validation.php            clean_str(), clean_int(), valid_email(), valid_slug(), require_fields()
├── js/
│   ├── api.js                    AJAX wrapper — Api.get / Api.post, ApiError class
│   ├── components.js             Injects navbar + footer into placeholders
│   ├── main.js                   Page-level controllers (initExplorePage, initDetailsPage, ...)
│   └── update-reviews.js         Reviews-specific helpers
├── sql/
│   ├── schema.sql                14-table schema, FKs, indexes, ENUMs, CHECKs
│   ├── seed-lookups.sql          Tours, addons, promos
│   ├── seed-spots.sql            8 destinations + child rows
│   ├── seed-demo.sql             Demo users, reviews, bookings
│   └── migrate_reviews.php       Idempotent migration: adds reviews.photo_url
├── *.html                        12 pages (index, home, explore, details, map, plan,
│                                 saved, profile, login, register, contact, about)
├── .htaccess                     Apache config (DirectoryIndex, CORS, error logging)
├── README.md                     Human-facing orientation
└── CLAUDE.md                     This file
```

---

## 5. The Request Pipeline (The One Pattern)

Every backend feature in this project follows the same shape. Once you understand this, every endpoint is a variation:

```
Browser click
  → js/main.js handler
  → Api.get / Api.post (js/api.js)
  → Fetch JSON to /api/<module>/<action>.php
  → includes/session.php   (resume session)
  → includes/auth.php      (require_login if needed)
  → includes/validation.php (clean inputs, require_fields)
  → endpoint logic with PDO prepared statements
  → includes/response.php  (json_success / json_error)
  → JS updates the DOM
```

Endpoint skeleton (copy this when adding new endpoints):

```php
<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';        // omit if endpoint is public
require_once __DIR__ . '/../../includes/validation.php';

require_login();                                          // 401 if not authenticated

$body = read_json_body();
require_fields($body, ['some_field']);
$some_field = clean_str($body['some_field']);

// PDO prepared statement only — no concatenation
$stmt = $pdo->prepare('SELECT ... FROM ... WHERE id = ?');
$stmt->execute([$some_field]);

json_success($stmt->fetch(), 'Optional message.');
```

---

## 6. API Conventions

### Response envelope (every endpoint)

```json
// Success
{ "success": true, "data": { ... }, "message": "Optional human note" }

// Error
{ "success": false, "error": "Human-readable", "code": "machine_code", "details": null }
```

### `json_error()` is polymorphic

The signature is `json_error(string $error, $code = 'error', int $status = 400, $extra = null)`. **If the second arg is an `int`**, it is treated as the HTTP status code and `$code` falls back to `'error'`. Both of these work:

```php
json_error('Spot not found.', 'not_found', 404);   // explicit
json_error('Spot not found.', 404);                // shorthand — int promotes to status
```

This was a deliberate fix to support both call styles across ~35 sites. Do not "tidy" it back to a strict signature.

### Authentication

- `require_login()` at the top of any endpoint that needs a session. Returns the `user_id` or aborts 401.
- Public reads (`spots/list`, `spots/get`, `reviews/list`, `promos/validate`, `bookings/create`) deliberately do **not** require login. Guests can browse and book.
- Sessions are HttpOnly + SameSite=Lax. `localStorage.tara_session` is a UI mirror only — never trusted server-side.

### Ownership checks return 404, not 403

If a logged-in user tries to update or delete a review/booking they don't own, the endpoint returns **404 `not_found`**, not 403. This prevents ID enumeration. Preserve this when adding new ownership-restricted endpoints.

### Server-side recomputation

- **Reviews:** After insert/update/delete, the spot's `rating` and `reviews_count` are recomputed inside the same transaction. Do not let the client send aggregates.
- **Bookings:** `total` is always recomputed server-side from `tours.price`, `addons.price`, and the validated `promo.value`. Any client-supplied `total` is ignored.
- **Bookings update:** The `tour_id` is read from the existing DB row, never from the client payload. Changing the tour is effectively a new booking.

---

## 7. Database

14 tables, MySQL 5.7+ / MariaDB 10.3+, InnoDB, utf8mb4_unicode_ci.

Full schema and design rationale: [docs/Backend/02-DATABASE.md](docs/Backend/02-DATABASE.md).

### Naming

- Tables: `snake_case`, plural (`users`, `saved_spots`, `booking_addons`)
- Columns: `snake_case` (`first_name`, `created_at`, `photo_url`)
- Primary keys: `INT UNSIGNED AUTO_INCREMENT` *except* `spots.id` and `tours.id` which are `VARCHAR(80)` slugs (preserves the `details.html?id=hundred-islands` URL scheme without a translation layer).

### Key relationships

```
users (1) ─< (N) saved_spots     (N) >─ (1) spots
users (1) ─< (N) reviews         (N) >─ (1) spots          UNIQUE(user_id, spot_id)
users (1) ─< (N) bookings        (N) >─ (1) tours          user_id is NULLable (guest bookings)
bookings (1) ─< (N) booking_addons (N) >─ (1) addons
spots (1) ─< (N) spot_activities | spot_gallery | spot_tips | spot_stats
```

### Constraints worth knowing about

- `users.email` UNIQUE — relied on by the registration race-condition story.
- `reviews(user_id, spot_id)` is effectively unique (enforced in code + duplicate-key catch on `SQLSTATE 23000`); one review per user per spot.
- `reviews.rating` has `CHECK (rating BETWEEN 1 AND 5)`.
- `spots.category` is an ENUM of `('Nature','Beach','Historical','Festival','Food')`. Don't add new categories without updating the ENUM and the frontend filter.
- `bookings.status` ENUM `('upcoming','completed','cancelled')`. **Hard-delete is restricted to `status='cancelled'`** — `upcoming` and `completed` are audit records.

---

## 8. Frontend Conventions

### Script load order (matters)

Every page that hits the backend must load scripts in this order:

```html
<script src="js/api.js"></script>        <!-- defines window.Api -->
<script src="js/components.js"></script> <!-- injects navbar/footer -->
<script src="js/main.js"></script>       <!-- page controllers -->
```

A past bug (`saved.html`) was caused by an inline `<script>` running before `api.js` had loaded. If you add new pages, mirror this order exactly.

### Components

`components/navbar.html` and `components/footer.html` are the **single source of truth** for the top nav and footer. Every page has:

```html
<div id="navbar-placeholder"></div>
...
<div id="footer-placeholder"></div>
```

`js/components.js` fetches the HTML and injects it after DOMContentLoaded. Do not duplicate navbar markup inline — twelve pages were refactored away from that pattern in v1.0-rc1.

### Data shape — snake_case from the server

The frontend expects DB column names verbatim: `short_desc`, `reviews_count`, `photo_url`, `created_at`. An earlier bug shipped because old camelCase (`shortDesc`, `reviews`) was carried over from `spots.json`. Do not reintroduce a camelCase-translation layer; just use snake_case end to end.

### XSS-safe rendering

User-supplied text (reviews, names, contact messages) is rendered via `.textContent` or via templates where the dynamic value goes through `escapeHtml()`. Do not `.innerHTML = userInput` anywhere.

### `Api.post` accepts `FormData` for file uploads

The reviews create/update endpoints accept `multipart/form-data` (for photo uploads). The wrapper detects `FormData` and skips the `Content-Type` header so the browser can add the boundary. Pattern:

```javascript
const fd = new FormData();
fd.append('spot_id', spotId);
fd.append('rating', rating);
fd.append('body', text);
if (photoFile) fd.append('photo', photoFile);
await Api.post('reviews/create.php', fd);
```

---

## 9. Documentation Map

| File | Purpose |
|---|---|
| [README.md](README.md) | Human-facing orientation + setup |
| [CLAUDE.md](CLAUDE.md) | This file — Claude/contributor handoff |
| [docs/PROJECT_CURRENT_STATUS.md](docs/PROJECT_CURRENT_STATUS.md) | **Latest status** — read for current state of the project |
| [docs/RELEASE_NOTES_v1.0-rc1.md](docs/RELEASE_NOTES_v1.0-rc1.md) | Detailed changelog for the v1.0-rc1 cut |
| [docs/Project-Alignment-Checklist.md](docs/Project-Alignment-Checklist.md) | Rubric self-assessment |
| [docs/Frontend/FRONTEND_DOCUMENTATION.md](docs/Frontend/FRONTEND_DOCUMENTATION.md) | Frontend architecture and design system |
| [docs/Frontend/James_PrePush_Updates.md](docs/Frontend/James_PrePush_Updates.md) | Frontend lead's pre-push changelog |
| [docs/Backend/README.md](docs/Backend/README.md) | Backend module roadmap (rubric-aligned) |
| [docs/Backend/00-ARCHITECTURE.md](docs/Backend/00-ARCHITECTURE.md) | System shape and request lifecycle |
| [docs/Backend/01-SETUP.md](docs/Backend/01-SETUP.md) | XAMPP setup walkthrough |
| [docs/Backend/02-DATABASE.md](docs/Backend/02-DATABASE.md) | Full schema + seeding strategy |
| [docs/Backend/03-CORE-PHP.md](docs/Backend/03-CORE-PHP.md) | Includes, helpers, AJAX wrapper |
| [docs/Backend/04-AUTH-MODULE.md](docs/Backend/04-AUTH-MODULE.md) | Auth endpoints |
| [docs/Backend/05-SPOTS-MODULE.md](docs/Backend/05-SPOTS-MODULE.md) | Spots listing/detail |
| [docs/Backend/06-SAVED-MODULE.md](docs/Backend/06-SAVED-MODULE.md) | Save / unsave |
| [docs/Backend/07-REVIEWS-MODULE.md](docs/Backend/07-REVIEWS-MODULE.md) | Reviews full CRUD + photo upload |
| [docs/Backend/08-BOOKINGS-MODULE.md](docs/Backend/08-BOOKINGS-MODULE.md) | Booking wizard, server-side total, edit/cancel/delete |
| [docs/Backend/09-CONTACT-MODULE.md](docs/Backend/09-CONTACT-MODULE.md) | Honeypot + rate-limited contact form |
| [docs/Backend/10-PROFILE-MODULE.md](docs/Backend/10-PROFILE-MODULE.md) | Profile get/update + password change |
| [docs/Backend/11-CHECKLIST.md](docs/Backend/11-CHECKLIST.md) | Pre-demo checklist, demo script, panel Q&A |
| [docs/Backend/12-DOCUMENTATION.md](docs/Backend/12-DOCUMENTATION.md) | **Formal 13-section deliverable required by the rubric** |

---

## 10. Security Baseline (Preserve)

- **No plaintext passwords.** `password_hash($pw, PASSWORD_DEFAULT)` on registration; `password_verify()` on login. Constant-time comparison — no timing leaks.
- **PDO prepared statements everywhere.** Grep `\$pdo->query(` and confirm no user input is concatenated.
- **Honeypot + rate limiting** on the contact form (5/hour per IP via `idx_ip_submitted`).
- **Honeypot on registration** (hidden `website` field — bots fill it, humans don't).
- **Cross-user 404, not 403** on ownership-restricted endpoints.
- **Server-side total enforcement** on bookings. Client `total` is ignored.
- **File upload validation:** `finfo` real MIME-type check, size cap (5 MB), whitelist (JPG/PNG/WEBP), random filename. Orphaned files are unlinked on transaction rollback.
- **HttpOnly + SameSite=Lax** session cookie.

---

## 11. Common Pitfalls Already Hit

When changing this codebase, watch for these — they have been root-caused before:

1. **Schema column drift.** `firstname` vs `first_name`, `ref` vs `reference`. Canon is **snake_case**. If you see camelCase column references in PHP/JS, that is a bug.
2. **`localStorage` regressions.** Profile, bookings, and saved spots all moved off `localStorage`. Do not reintroduce `tara_profile` or `tara_bookings` keys. `tara_session` is the only acceptable `localStorage` key, and it's a UI mirror only.
3. **Script load order.** `api.js` → `components.js` → `main.js`, then any inline `<script>`. Inline scripts that call `Api.*` before `api.js` loads will throw `ReferenceError`.
4. **Photo orphan on failed insert.** `api/reviews/create.php` unlinks the uploaded file on DB failure paths (including UNIQUE collisions). Keep this — losing it leaks files into `assets/images/reviews/`.
5. **`migrate_reviews.php` location.** Lives under `sql/`. It must not sit at the project root (web-exposed). The git history shows the move; don't undo it.
6. **`data/spots.json` is the seed source, not a runtime fetch.** The browser hits `/api/spots/list.php`. If a page starts fetching `spots.json` directly, that is a regression.

---

## 12. Coding Style

- **PHP:** snake_case file and function names. One responsibility per endpoint. Top-of-file `require_once` for `db.php`, `response.php`, `auth.php`, `validation.php` in that order. Use transactions for any multi-statement mutation.
- **SQL:** Use placeholders (`?` positional or `:named`). Always.
- **JS:** Modern (ES6+) where it doesn't compromise compatibility. `const`/`let`, arrow functions, `async/await`. Page controllers are named `init<Page>Page()` and dispatched from `main.js` based on `document.body` or pathname.
- **CSS:** BEM-inspired naming (`.explore-card`, `.explore-card__title`). Mobile-first with breakpoints at 768 and 1024. CSS custom properties in `css/global/base.css`.
- **Comments:** Top-of-file docblock for every PHP endpoint summarizing the route, method, body, and behavior. Inline comments only where the *why* is non-obvious.

---

## 13. When in Doubt

- **Backend question?** Start with [docs/Backend/00-ARCHITECTURE.md](docs/Backend/00-ARCHITECTURE.md), then the module file 04–10 that matches the area.
- **Frontend question?** [docs/Frontend/FRONTEND_DOCUMENTATION.md](docs/Frontend/FRONTEND_DOCUMENTATION.md).
- **Schema question?** [docs/Backend/02-DATABASE.md](docs/Backend/02-DATABASE.md).
- **What changed recently?** [docs/PROJECT_CURRENT_STATUS.md](docs/PROJECT_CURRENT_STATUS.md) (latest) and [docs/RELEASE_NOTES_v1.0-rc1.md](docs/RELEASE_NOTES_v1.0-rc1.md) (v1.0-rc1 cut).
- **Rubric / grading concerns?** [docs/Project-Alignment-Checklist.md](docs/Project-Alignment-Checklist.md) and [docs/Backend/11-CHECKLIST.md](docs/Backend/11-CHECKLIST.md).
- **Formal deliverable?** [docs/Backend/12-DOCUMENTATION.md](docs/Backend/12-DOCUMENTATION.md).
