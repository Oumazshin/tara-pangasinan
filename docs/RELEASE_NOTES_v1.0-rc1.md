# Release Notes — v1.0-rc1

**Commit:** `25ddfb0`
**Date:** 2026-06-17
**Author:** James Clark Bacolor
**Headline:** Finalize review system, backend APIs, and UI/UX polish

---

## Executive Summary

This release brings the project from "Phase 7 backend half-built" to "Reviews and Bookings fully CRUD-complete, with photo upload and a refactored frontend architecture." Reviews now support drag-and-drop photo upload end-to-end. Bookings gained a real edit-modal flow plus permanent-delete for cancelled records. Twelve HTML pages were refactored to share a single navbar and footer via a JavaScript component loader, and six destination images now ship locally instead of being fetched from Unsplash.

**Net diff:** 41 files changed, 1,364 insertions, 1,227 deletions.

---

## Backend — New Features

### Bookings — full CRUD complete

| File | Lines | Summary |
|---|---|---|
| `api/bookings/update.php` | +108 | Real edit endpoint for upcoming bookings. Server recomputes total. Tour is locked from the existing database row (never read from client). Only status='upcoming' is editable. |
| `api/bookings/delete.php` | +53 | Hard-delete endpoint restricted to status='cancelled'. Ownership check returns 404 on cross-user attempts. Explicit `DELETE FROM booking_addons` before parent delete (in transaction). |

Editable fields in the booking modal: adults, children, infants, contact name, contact phone, special requests. Date, time, tour, addons, and promo code remain immutable post-creation by design.

### Reviews — full CRUD plus photo upload

| File | Δ | Summary |
|---|---|---|
| `api/reviews/create.php` | +38/-4 | Accepts `multipart/form-data`. Photo size cap 5 MB. `finfo` validates real MIME type (defeats fake-extension attacks). Whitelist: JPG, PNG, WEBP. Random filename pattern `rev_{time}_{hex}.{ext}`. |
| `api/reviews/update.php` | +51/-4 | Supports replacing photo (cleans up old file on disk) and explicit removal via `remove_photo=1` flag. Validation runs BEFORE disk operations. |
| `api/reviews/delete.php` | +9/-1 | Unlinks photo file from disk before deleting the review row. |
| `api/reviews/list.php` | +48/-19 | Returns `photo_url` field. Supports rating filter (e.g. show only 5-star reviews). |

### Migration utility

- `migrate_reviews.php` (root, +13) — idempotent script that adds the `photo_url` column to existing databases. Catches duplicate-column error (SQLSTATE 42S21) on re-run.

---

## Frontend — New Features

### Component system

| File | Lines | Summary |
|---|---|---|
| `js/components.js` | +25 | Fetches `components/navbar.html` and `components/footer.html` and injects them into placeholder elements on every page. Re-initializes navbar logic post-injection. |
| `components/navbar.html` | +24 | Shared top navigation extracted from twelve pages. |
| `components/footer.html` | +29 | Shared footer extracted from twelve pages. |

Twelve HTML pages refactored to use placeholders (`<div id="navbar-placeholder"></div>` / `<div id="footer-placeholder"></div>`) instead of inline markup. Net change: about/contact/map/plan/saved.html each shrunk by ~55 lines.

### Reviews UI

- 1-to-5 star filter on `details.html` and `explore.html` — toggling stars triggers an AJAX refetch of `/api/reviews/list.php` without page reload. Animated CSS loading spinner during the request.
- Drag-and-drop photo upload zone on both Create and Edit review forms. Preview rendered client-side. File type and size validated client-side before POST.
- `window.startEditReview`, `window.cancelEditReview`, `window.submitEditReview`, `window.deleteReview` defined as global handlers so HTML onclick attributes can call them. Resolves the prior bug where these onclick handlers threw ReferenceError.
- Layout layering bug fixed: owner controls (Edit/Delete buttons) no longer overlap the active textarea during inline editing.

### Bookings UI

- Edit modal added to `profile.html` with adults / children / infants / contact name / contact phone / requests fields. Pre-fills from existing booking data.
- State-conditional action buttons on each booking card:
  - `upcoming` → Edit + Cancel
  - `completed` → View Details + Leave a Review
  - `cancelled` → Rebook + Delete History
- `window.deleteBooking` handler with confirmation dialog.

### Registration form

- Confirm Password field with client-side equality check before submit.
- Eye-icon visibility toggle on both password fields.
- Fixed overlapping background bug on `login.html` and `register.html` by centralizing background asset in `css/pages/login/main.css`.

### Global polish

- Native CSS transitions on all anchor tags via `base.css`.
- Hover states on navigation menu via `navbar.css`.
- `onerror` JS fallback for broken images — replaces with branded SVG placeholder at `assets/icons/image-fallback.svg`. Prevents layout collapse on missing photos.

---

## Database & Assets

### Schema

| File | Change |
|---|---|
| `sql/schema.sql` | `reviews.photo_url VARCHAR(500) DEFAULT NULL` added. |

### Seed updates

| File | Change |
|---|---|
| `sql/seed-lookups.sql` | Tour image URLs updated from external Unsplash links to local paths under `assets/images/spots/`. |

### Bundled assets

Six destination photos now live in the repository instead of being hot-linked:

- `assets/images/spots/hundred-islands.png` (988 KB)
- `assets/images/spots/patar-beach.png` (826 KB)
- `assets/images/spots/cape-bolinao.png` (1,089 KB)
- `assets/images/spots/enchanted-cave.png` (1,084 KB)
- `assets/images/spots/lingayen-gulf.png` (757 KB)
- `assets/images/spots/manaoag-church.png` (984 KB)

Net effect: the app no longer depends on Unsplash availability or external bandwidth.

---

## Refactoring & Architecture

- `js/api.js` rewritten (+63/-65) to support `FormData` payloads in addition to JSON. Both content types automatically negotiated based on payload shape. ApiError class unchanged.
- `js/main.js` (+278/-31) — large expansion to host the new review handlers, filter logic, photo upload flow, and image-error fallback. Code organized into named functions on `window` for onclick compatibility.
- All twelve HTML pages no longer carry duplicated navbar/footer markup. Single source of truth in `components/`.

---

## Documentation

### Restructured

- `docs/PROJECT_OVERVIEW.md` removed (legacy).
- `docs/DESIGN_GUIDELINES.md` removed (legacy).
- `docs/FRONTEND_DOCUMENTATION.md` added (+82) — replaces both above with current architecture description.
- `docs/Project-Alignment-Checklist.md` added (+87) — running checklist for cross-team coordination.
- `docs/James_PrePush_Updates.md` added (+65) — personal changelog of pre-push edits.

### Minor

- `docs/Backend/12-DOCUMENTATION.md` minor edits (+5/-5).
- `README.md` housekeeping (+6/-7).

---

## Known Issues — Carried Over

The following items were flagged in the prior audit and remain unaddressed in this commit:

### 1. `json_error()` arity mismatch — backend-wide

**File:** `includes/response.php`
**Affects:** ~35 call sites across `api/reviews/`, `api/bookings/`, `api/promos/`

The function is defined as `json_error(string $error, string $code, int $status, $extra)` but most modules call it as `json_error('msg', 404)`. PHP coerces the int into `$code` and the HTTP status falls back to the default 400. Every 404, 409, 401, and 500 response currently returns HTTP 400.

**Fix:** make `json_error` polymorphic with a 6-line edit. No call-site changes needed.

```php
function json_error(string $error, $code = 'error', int $status = 400, $extra = null): void
{
    if (is_int($code)) { $status = $code; $code = 'error'; }
    // ... rest unchanged
}
```

### 2. No `sql/seed-spots.sql`

`schema.sql` creates the `spots`, `spot_activities`, `spot_gallery`, `spot_tips`, and `spot_stats` tables but no SQL file populates them. A fresh database boot leaves the entire destination catalog empty, breaking `home.html`, `explore.html`, `details.html`, `map.html`, and any review or save flow that depends on a valid `spot_id`.

**Fix:** phpMyAdmin → Export → select `spots*` tables → data-only → save to `sql/seed-spots.sql`.

### 3. Photo orphans in `reviews/create.php`

Photo upload happens before review body validation. If the body fails the 10-character minimum, the spot doesn't exist, or the UNIQUE constraint fires, the photo is already on disk with no database row pointing to it.

**Fix:** reorder validation above the upload block, or unlink the file in the failure paths. (Note: `reviews/update.php` already does this correctly.)

### 4. `migrate_reviews.php` location

Currently at project root, publicly accessible at `/migrate_reviews.php`. Idempotent so impact is low, but it doesn't belong in the web root.

**Fix:** move to `sql/migrate_reviews.php` and document as a one-shot script in the backend README.

---

## Pending Work

| Phase | Status |
|---|---|
| 09 — Contact module (honeypot + rate limit) | Not started |
| 10 — Profile module (get + update endpoints) | Not started |
| 11 — Full testing pass | Not started |
| 12 — Final documentation deliverable | Not started |

---

## File Index — All Changes

```
Backend (PHP)
  + api/bookings/delete.php                 53 lines
  + api/bookings/update.php                108 lines
  M api/reviews/create.php                +38 / -4
  M api/reviews/delete.php                 +9 / -1
  M api/reviews/list.php                  +48 / -19
  M api/reviews/update.php                +51 / -4
  + migrate_reviews.php                    13 lines

Database
  M sql/schema.sql                         +1
  M sql/seed-lookups.sql                  +12 / -0

Frontend (JS)
  M js/api.js                             +63 / -65
  + js/components.js                       25 lines
  M js/main.js                           +278 / -31

Frontend (HTML)
  M about.html                             +3 / -58   (navbar/footer extraction)
  M contact.html                           +3 / -58   (navbar/footer extraction)
  + components/footer.html                 29 lines
  + components/navbar.html                 24 lines
  M details.html                          +45 / -74
  M explore.html                          +85 / -62
  M home.html                              +7 / -69
  M map.html                               +3 / -58   (navbar/footer extraction)
  M plan.html                              +3 / -58   (navbar/footer extraction)
  M profile.html                         +111 / -62
  M register.html                         +49 / -7
  M saved.html                             +3 / -58   (navbar/footer extraction)

Styles
  M css/global/base.css                   +11 / -0
  M css/global/navbar.css                 +12 / -9
  M css/pages/login/main.css              +32 / -1

Assets
  + assets/icons/image-fallback.svg
  + assets/images/spots/cape-bolinao.png     (1,089 KB)
  + assets/images/spots/enchanted-cave.png   (1,084 KB)
  + assets/images/spots/hundred-islands.png  (988 KB)
  + assets/images/spots/lingayen-gulf.png    (757 KB)
  + assets/images/spots/manaoag-church.png   (984 KB)
  + assets/images/spots/patar-beach.png      (826 KB)

Documentation
  M README.md                              +6 / -7
  - docs/DESIGN_GUIDELINES.md             -369
  - docs/PROJECT_OVERVIEW.md              -142
  + docs/FRONTEND_DOCUMENTATION.md         82 lines
  + docs/James_PrePush_Updates.md          65 lines
  + docs/Project-Alignment-Checklist.md    87 lines
  M docs/Backend/12-DOCUMENTATION.md       +5 / -5

Legend: + new   M modified   - deleted
```

---

*End of release notes.*
