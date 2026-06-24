# 08 — Bookings Module

> **Phase 8 of 11** · Owner: _Bookings + Plan-page lead_ · Depends on: 02, 03, 04, 07

This phase wires the tour booking wizard on `plan.html` to the database. Bookings become real records the user can see, cancel, and re-visit from their profile page. Tours, add-ons, and promo codes — currently hardcoded JavaScript arrays inside `plan.html` — are now seeded into MySQL and read from the API.

---

## Goal

| Currently | After this phase |
|---|---|
| `submitBooking()` (plan.html line 940) writes to `localStorage.tara_bookings`. | Writes to the `bookings` table via `POST /api/bookings/create.php`. |
| `TOURS`, `ADDONS`, `PROMOS` are JS constants embedded in `plan.html`. | Fetched from `/api/spots/tours.php` and `/api/promos/validate.php`. |
| Booking reference (`TPG-XXXXXX`) is generated client-side. | Generated server-side and returned in the response. |
| Profile page reads `tara_bookings` localStorage array. | Reads `/api/bookings/list.php` for the logged-in user. |
| No way to cancel a booking. | `POST /api/bookings/cancel.php` updates status to `cancelled` and stamps `cancelled_at`. |
| No way to edit a booking after submission. | `POST /api/bookings/update.php` lets users change date, time, guests, addons, and promo before the tour date. |
| Cancelled bookings sit in history forever. | `POST /api/bookings/delete.php` hard-deletes cancelled bookings (the user's choice to remove them). |
| Promo codes work because the JS knows them. | Validated server-side — secret discount values are never sent to the client. |

> **CRUD completeness.** With these additions, the Bookings resource demonstrates the full CRUD lifecycle on a complex, multi-table entity: **C**reate (`create.php`), **R**ead (`list.php`), **U**pdate (real edit via `update.php`, plus soft-update via `cancel.php`), **D**elete (`delete.php` for cancelled rows). Combined with the Reviews resource (Phase 07), the project demonstrates CRUD on two resources of distinct complexity profiles — Reviews is simple user-generated content; Bookings carries business rules, server-side pricing, and multi-table state.

---

## Files Affected

**New files**
- `api/bookings/create.php`
- `api/bookings/list.php`
- `api/bookings/cancel.php`
- `api/bookings/update.php`         — NEW (real edit)
- `api/bookings/delete.php`         — NEW (hard delete for cancelled)
- `api/promos/validate.php`
- `api/spots/tours.php`             — list available tours + add-ons

**Edited files**
- `plan.html` — replace `TOURS`/`ADDONS`/`PROMOS` arrays with API calls; rewrite `submitBooking()` to POST
- `profile.html` — replace localStorage booking history with API-driven list; add **Edit** modal + **Remove from history** controls

**Touched tables** — `tours`, `addons`, `promos`, `bookings`, `booking_addons`

> **Schema column reminders (from `02-DATABASE.md`).** This module touches several tables with non-obvious names:
> - `bookings.reference` (not `ref`) is the unique `TPG-XXXXXX` string column.
> - `bookings.contact_name`, `contact_email`, `contact_phone` (not just `name`/`email`/`phone`).
> - `bookings.booked_at` (not `created_at`).
> - `bookings.cancelled_at` is nullable; set it when cancelling.
> - `booking_addons.price_charged` (single column) — no name snapshot; we JOIN to `addons` for the name.
> - `promos.type` and `promos.value` (not `discount_type`/`discount_value`).
> - `tours.includes` is a JSON-array text column (no `_json` suffix).
>
> The JSON we send to / from the **frontend** can keep friendly names (`ref`, `name`, `email`) — but the SQL must use the canonical column names above.

---

## API Endpoints

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| `GET`  | `/api/spots/tours.php`              | No  | List active tours + add-ons + time slots. |
| `POST` | `/api/promos/validate.php`          | No  | Check promo code and return discount info. |
| `POST` | `/api/bookings/create.php`          | No* | **Create** — make a booking. * Guest bookings allowed; `user_id` is nullable. |
| `GET`  | `/api/bookings/list.php`            | Yes | **Read** — list current user's bookings (upcoming + past). |
| `POST` | `/api/bookings/update.php`          | Yes | **Update** — edit an upcoming booking (date/time/guests/addons/promo). |
| `POST` | `/api/bookings/cancel.php`          | Yes | Soft-update — flip status to `cancelled`, stamp `cancelled_at`. |
| `POST` | `/api/bookings/delete.php`          | Yes | **Delete** — hard-delete a cancelled booking (purge from history). |

> **Why allow guest bookings?** The current frontend never gates `plan.html` behind login. Forcing auth at submit would break UX. The schema uses `user_id NULL`, so guests get a confirmation reference but no profile history.

---

## Backend Code

### File 1: `api/spots/tours.php`

```php
<?php
/**
 * GET /api/spots/tours.php
 *
 * Returns active tours, add-ons, and fixed time slots in one payload.
 * Plan.html renders the wizard from this response.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';

// Tours
$tours_stmt = $pdo->query("
    SELECT id, name, location, duration, price, image,
           badge, badge_color, rating, reviews_count,
           meeting_point, includes
    FROM tours
    ORDER BY price ASC
");
$tours = $tours_stmt->fetchAll();

// Decode the `includes` JSON text column for each tour
foreach ($tours as &$t) {
    $t['price']         = (int)   $t['price'];
    $t['rating']        = (float) $t['rating'];
    $t['reviews_count'] = (int)   $t['reviews_count'];
    $t['includes']      = json_decode($t['includes'] ?? '[]', true) ?: [];
}
unset($t);

// Add-ons
$addons_stmt = $pdo->query("
    SELECT id, name, description, price
    FROM addons
    ORDER BY price ASC
");
$addons = $addons_stmt->fetchAll();
foreach ($addons as &$a) {
    $a['price'] = (int) $a['price'];
}
unset($a);

// Time slots — small enough to ship as a static config block
$time_slots = [
    ['value' => '6:00 AM', 'label' => 'Sunrise Tour',   'icon' => '🌅'],
    ['value' => '8:00 AM', 'label' => 'Morning Tour',   'icon' => '☀️'],
    ['value' => '1:00 PM', 'label' => 'Afternoon Tour', 'icon' => '🌤️'],
];

json_success([
    'tours'      => $tours,
    'addons'     => $addons,
    'time_slots' => $time_slots,
]);
```

> **Why time slots stay static.** They're not user-content; they're a business policy ("we operate 3 daily departures"). Modeling them as a DB table is over-engineering for a project of this scope. If your panel asks "why is this hardcoded?", the honest answer is: *it doesn't change per request, and pushing it to the DB adds latency without value*.

---

### File 2: `api/promos/validate.php`

```php
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
    'discount_type'  => $promo['type'],            // 'percent' or 'fixed' — kept as `discount_type` in API for frontend clarity
    'discount_value' => (int) $promo['value'],
    'label'          => $promo['label'],
]);
```

> **Defense talking point — Server-side promo validation.** In the original code, the panel could open DevTools, read the `PROMOS` object, and learn every secret code in one click. By moving validation to the server, codes become trade secrets: the client only learns the value of codes it actually tries. This is the same logic e-commerce sites use.

---

### File 3: `api/bookings/create.php`

```php
<?php
/**
 * POST /api/bookings/create.php
 *
 * Body:
 * {
 *   "tour_id":   "hundred-islands",
 *   "date":      "2026-07-15",
 *   "time":      "8:00 AM",
 *   "adults":    2,
 *   "children":  1,
 *   "infants":   0,
 *   "addon_ids": ["snorkel", "lunch"],
 *   "promo_code": "TARA10" | null,
 *   "name":      "Carlo Reyes",
 *   "email":     "carlo@example.com",
 *   "phone":     "+63 917 234 5678",
 *   "requests":  "Vegetarian meals please."
 * }
 *
 * Server recomputes the total — never trusts client-side math.
 * Returns the booking reference + full record.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/validation.php';

$payload = read_json_body();
require_fields($payload, ['tour_id', 'date', 'time', 'adults', 'name', 'email']);

// ── Parse + clean inputs ─────────────────────────────────────
$tour_id   = clean_str($payload['tour_id']);
$date      = clean_str($payload['date']);
$time      = clean_str($payload['time']);
$adults    = clean_int($payload['adults'],   1, 20);
$children  = clean_int($payload['children'] ?? 0, 0, 20);
$infants   = clean_int($payload['infants']  ?? 0, 0, 20);
$addon_ids = is_array($payload['addon_ids'] ?? null) ? $payload['addon_ids'] : [];
$promo     = isset($payload['promo_code']) ? strtoupper(trim($payload['promo_code'])) : '';
$name      = clean_str($payload['name']);
$email     = clean_str($payload['email']);
$phone     = clean_str($payload['phone'] ?? '');
$requests  = clean_str($payload['requests'] ?? '');

// User may be a guest (no session)
$user_id = $_SESSION['user_id'] ?? null;

// ── Validation ───────────────────────────────────────────────
if (!valid_slug($tour_id))           json_error('Invalid tour.', 400);
if (!valid_email($email))            json_error('Invalid email address.', 400);
if (strlen($name) < 2)               json_error('Name is too short.', 400);

// Date must be YYYY-MM-DD and at least tomorrow
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_error('Invalid date format.', 400);
}
if (strtotime($date) < strtotime('+1 day midnight')) {
    json_error('Tours must be booked at least one day in advance.', 400);
}

// Time slot must be one of the allowed values
$allowed_times = ['6:00 AM', '8:00 AM', '1:00 PM'];
if (!in_array($time, $allowed_times, true)) {
    json_error('Invalid time slot.', 400);
}

// ── Look up tour ─────────────────────────────────────────────
$tour_stmt = $pdo->prepare("
    SELECT id, name, price, image, location
    FROM tours
    WHERE id = ?
    LIMIT 1
");
$tour_stmt->execute([$tour_id]);
$tour = $tour_stmt->fetch();
if (!$tour) {
    json_error('Tour not found.', 404);
}

// ── Look up add-ons (validates each id is real) ──────────────
$addon_rows = [];
if ($addon_ids) {
    $addon_ids = array_filter(array_map('clean_str', $addon_ids), 'valid_slug');
    if ($addon_ids) {
        $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));
        $a_stmt = $pdo->prepare("
            SELECT id, name, price
            FROM addons
            WHERE id IN ($placeholders)
        ");
        $a_stmt->execute($addon_ids);
        $addon_rows = $a_stmt->fetchAll();
    }
}

// ── Validate promo (if provided) ─────────────────────────────
$promo_row = null;
if ($promo !== '') {
    $p_stmt = $pdo->prepare("
        SELECT code, type, value
        FROM promos
        WHERE code = ?
          AND is_active = 1
          AND (expires_at IS NULL OR expires_at >= CURDATE())
        LIMIT 1
    ");
    $p_stmt->execute([$promo]);
    $promo_row = $p_stmt->fetch();
    // Silently drop invalid promos rather than fail the whole booking;
    // user already passed the validation step in the UI.
}

// ── Compute pricing server-side ──────────────────────────────
$tour_price = (int) $tour['price'];
$guests     = $adults + $children;  // infants don't pay (matches frontend logic)

$subtotal = $tour_price * $guests;
foreach ($addon_rows as $a) {
    $subtotal += (int) $a['price'] * $guests;
}

$discount = 0;
if ($promo_row) {
    if ($promo_row['type'] === 'percent') {
        $discount = (int) round($subtotal * ((int) $promo_row['value']) / 100);
    } else { // fixed
        $discount = min((int) $promo_row['value'], $subtotal);
    }
}
$total = max(0, $subtotal - $discount);

// ── Generate reference + insert ──────────────────────────────
$reference = 'TPG-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

try {
    $pdo->beginTransaction();

    $ins = $pdo->prepare("
        INSERT INTO bookings (
            reference, user_id, tour_id, tour_date, tour_time,
            adults, children, infants,
            promo_code, discount, total, status,
            contact_name, contact_email, contact_phone, requests, booked_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming', ?, ?, ?, ?, NOW())
    ");
    $ins->execute([
        $reference, $user_id, $tour_id, $date, $time,
        $adults, $children, $infants,
        $promo_row['code'] ?? null, $discount, $total,
        $name, $email, $phone, $requests
    ]);
    $booking_id = (int) $pdo->lastInsertId();

    // Link add-ons with the price locked in at booking time
    if ($addon_rows) {
        $ba = $pdo->prepare("
            INSERT INTO booking_addons (booking_id, addon_id, price_charged)
            VALUES (?, ?, ?)
        ");
        foreach ($addon_rows as $a) {
            $ba->execute([$booking_id, $a['id'], $a['price']]);
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

// Note: API field is `ref` (matches plan.html DOM expectations);
// DB column is `reference`. We translate at this boundary.
json_success([
    'ref'         => $reference,
    'booking_id'  => $booking_id,
    'tour_name'   => $tour['name'],
    'tour_image'  => $tour['image'],
    'tour_date'   => $date,
    'tour_time'   => $time,
    'guests'      => $guests,
    'subtotal'    => $subtotal,
    'discount'    => $discount,
    'total'       => $total,
    'email'       => $email,
], 'Booking confirmed.');
```

> **Defense talking point — Why recompute the total server-side?** A malicious client could send `total: 1` for a ₱10,000 tour. By computing pricing on the server using DB values, the client's `total` field is ignored entirely. This is a textbook example of *never trust the client*.
>
> **Defense talking point — `booking_addons.price_charged` locked at booking time.** Even though we don't snapshot the addon name (we JOIN for that in `list.php`), the *price* is frozen with `price_charged`. If the admin later changes "Seafood Lunch Pack" from ₱450 to ₱600, *past bookings still show ₱450*. The name will display as whatever the addon is currently named, which is acceptable since names change rarely.

---

### File 4: `api/bookings/list.php`

```php
<?php
/**
 * GET /api/bookings/list.php
 *
 * Returns all bookings for the current user, newest first.
 * Includes tour info + add-ons for each.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';

require_login();

$user_id = $_SESSION['user_id'];

// Main query — bookings + tour info JOIN
// API field names use the friendly aliases (`ref`, `created_at`) that the
// existing frontend expects; DB columns are `reference` and `booked_at`.
$b_stmt = $pdo->prepare("
    SELECT
        b.id,
        b.reference AS ref,
        b.tour_id, b.tour_date, b.tour_time,
        b.adults, b.children, b.infants,
        b.promo_code, b.discount, b.total, b.status,
        b.contact_name  AS name,
        b.contact_email AS email,
        b.contact_phone AS phone,
        b.requests,
        b.booked_at     AS created_at,
        b.cancelled_at,
        t.name     AS tour_name,
        t.image    AS tour_image,
        t.location AS tour_location
    FROM bookings b
    INNER JOIN tours t ON t.id = b.tour_id
    WHERE b.user_id = ?
    ORDER BY b.booked_at DESC
");
$b_stmt->execute([$user_id]);
$bookings = $b_stmt->fetchAll();

if (!$bookings) {
    json_success(['bookings' => []]);
}

// Hydrate add-ons (N+1 prevented with IN clause + JOIN for current name)
$ids = array_map(fn($b) => $b['id'], $bookings);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$a_stmt = $pdo->prepare("
    SELECT ba.booking_id, ba.addon_id, a.name, ba.price_charged AS price
    FROM booking_addons ba
    INNER JOIN addons a ON a.id = ba.addon_id
    WHERE ba.booking_id IN ($placeholders)
");
$a_stmt->execute($ids);
$addons_by_booking = [];
foreach ($a_stmt->fetchAll() as $a) {
    $a['price'] = (int) $a['price'];
    $addons_by_booking[$a['booking_id']][] = $a;
}

// Stitch together + coerce numeric types
foreach ($bookings as &$b) {
    $b['adults']   = (int) $b['adults'];
    $b['children'] = (int) $b['children'];
    $b['infants']  = (int) $b['infants'];
    $b['discount'] = (int) $b['discount'];
    $b['total']    = (int) $b['total'];
    $b['addons']   = $addons_by_booking[$b['id']] ?? [];
}
unset($b);

json_success(['bookings' => $bookings]);
```

---

### File 5: `api/bookings/cancel.php`

```php
<?php
/**
 * POST /api/bookings/cancel.php
 * Body: { "booking_id": 42 }
 *
 * Sets a booking's status to 'cancelled' and stamps cancelled_at.
 * User can only cancel their own bookings.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['booking_id']);

$booking_id = clean_int($payload['booking_id'], 1);
$user_id    = $_SESSION['user_id'];

// Confirm booking belongs to this user (otherwise: 404, not 403,
// to avoid leaking that the ID exists for someone else)
$check = $pdo->prepare("
    SELECT id, status FROM bookings
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$booking_id, $user_id]);
$row = $check->fetch();

if (!$row) {
    json_error('Booking not found.', 404);
}
if ($row['status'] === 'cancelled') {
    json_error('Booking already cancelled.', 409);
}
if ($row['status'] === 'completed') {
    json_error('Cannot cancel a completed tour.', 400);
}

$pdo->prepare("
    UPDATE bookings
    SET status = 'cancelled', cancelled_at = NOW()
    WHERE id = ?
")->execute([$booking_id]);

json_success(['booking_id' => $booking_id], 'Booking cancelled.');
```

---

### File 6: `api/bookings/update.php`

```php
<?php
/**
 * POST /api/bookings/update.php
 *
 * Body: {
 *   "booking_id": 42,
 *   "date":       "2026-07-20",
 *   "time":       "8:00 AM",
 *   "adults":     3,
 *   "children":   1,
 *   "infants":    0,
 *   "addon_ids":  ["snorkel"],
 *   "promo_code": "TARA10" | null,
 *   "phone":      "+63 917 xxx xxxx",
 *   "requests":   "Updated dietary notes..."
 * }
 *
 * Edits an UPCOMING booking owned by the current user. The price is
 * recomputed server-side using the same logic as create.php. Addons are
 * replaced wholesale inside a transaction. The tour itself cannot be
 * changed (that's effectively a new booking).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['booking_id', 'date', 'time', 'adults']);

$booking_id = clean_int($payload['booking_id'], 1);
$user_id    = $_SESSION['user_id'];

// ── Verify ownership + editable status ───────────────────────
$check = $pdo->prepare("
    SELECT id, tour_id, status, tour_date
    FROM bookings
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$booking_id, $user_id]);
$row = $check->fetch();

if (!$row) {
    json_error('Booking not found.', 404);
}
if ($row['status'] !== 'upcoming') {
    json_error('Only upcoming bookings can be edited.', 400);
}

$tour_id = $row['tour_id']; // tour is locked; not editable

// ── Parse + clean inputs ─────────────────────────────────────
$date      = clean_str($payload['date']);
$time      = clean_str($payload['time']);
$adults    = clean_int($payload['adults'],   1, 20);
$children  = clean_int($payload['children'] ?? 0, 0, 20);
$infants   = clean_int($payload['infants']  ?? 0, 0, 20);
$addon_ids = is_array($payload['addon_ids'] ?? null) ? $payload['addon_ids'] : [];
$promo     = isset($payload['promo_code']) ? strtoupper(trim($payload['promo_code'])) : '';
$phone     = clean_str($payload['phone']    ?? '');
$requests  = clean_str($payload['requests'] ?? '');

// ── Validation (same rules as create.php) ────────────────────
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_error('Invalid date format.', 400);
}
if (strtotime($date) < strtotime('+1 day midnight')) {
    json_error('Tours must be at least one day in the future.', 400);
}

$allowed_times = ['6:00 AM', '8:00 AM', '1:00 PM'];
if (!in_array($time, $allowed_times, true)) {
    json_error('Invalid time slot.', 400);
}

// ── Look up tour (locked from original booking) ──────────────
$tour_stmt = $pdo->prepare("SELECT id, price FROM tours WHERE id = ? LIMIT 1");
$tour_stmt->execute([$tour_id]);
$tour = $tour_stmt->fetch();
if (!$tour) {
    // Tour was deleted since booking — should never happen given ON DELETE RESTRICT,
    // but guard anyway.
    json_error('Original tour no longer available.', 410);
}

// ── Look up new addons ──────────────────────────────────────
$addon_rows = [];
if ($addon_ids) {
    $addon_ids = array_filter(array_map('clean_str', $addon_ids), 'valid_slug');
    if ($addon_ids) {
        $placeholders = implode(',', array_fill(0, count($addon_ids), '?'));
        $a_stmt = $pdo->prepare("SELECT id, price FROM addons WHERE id IN ($placeholders)");
        $a_stmt->execute($addon_ids);
        $addon_rows = $a_stmt->fetchAll();
    }
}

// ── Validate (possibly new) promo ────────────────────────────
$promo_row = null;
if ($promo !== '') {
    $p_stmt = $pdo->prepare("
        SELECT code, type, value
        FROM promos
        WHERE code = ?
          AND is_active = 1
          AND (expires_at IS NULL OR expires_at >= CURDATE())
        LIMIT 1
    ");
    $p_stmt->execute([$promo]);
    $promo_row = $p_stmt->fetch();
}

// ── Recompute pricing server-side ────────────────────────────
$tour_price = (int) $tour['price'];
$guests     = $adults + $children;

$subtotal = $tour_price * $guests;
foreach ($addon_rows as $a) {
    $subtotal += (int) $a['price'] * $guests;
}

$discount = 0;
if ($promo_row) {
    if ($promo_row['type'] === 'percent') {
        $discount = (int) round($subtotal * ((int) $promo_row['value']) / 100);
    } else {
        $discount = min((int) $promo_row['value'], $subtotal);
    }
}
$total = max(0, $subtotal - $discount);

// ── Atomic update: booking row + replace addons ──────────────
try {
    $pdo->beginTransaction();

    // Update the booking row
    $upd = $pdo->prepare("
        UPDATE bookings SET
            tour_date     = ?,
            tour_time     = ?,
            adults        = ?,
            children      = ?,
            infants       = ?,
            promo_code    = ?,
            discount      = ?,
            total         = ?,
            contact_phone = ?,
            requests      = ?
        WHERE id = ?
    ");
    $upd->execute([
        $date, $time, $adults, $children, $infants,
        $promo_row['code'] ?? null, $discount, $total,
        $phone, $requests,
        $booking_id
    ]);

    // Replace addons: delete old, insert new
    $pdo->prepare("DELETE FROM booking_addons WHERE booking_id = ?")
        ->execute([$booking_id]);

    if ($addon_rows) {
        $ba = $pdo->prepare("
            INSERT INTO booking_addons (booking_id, addon_id, price_charged)
            VALUES (?, ?, ?)
        ");
        foreach ($addon_rows as $a) {
            $ba->execute([$booking_id, $a['id'], $a['price']]);
        }
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

json_success([
    'booking_id' => $booking_id,
    'tour_date'  => $date,
    'tour_time'  => $time,
    'guests'     => $guests,
    'subtotal'   => $subtotal,
    'discount'   => $discount,
    'total'      => $total,
], 'Booking updated.');
```

> **Defense talking point — Tour locking.** The endpoint accepts `booking_id` but never accepts `tour_id` from the client. We read the existing `tour_id` from the database and use that. This prevents a user from "editing" their booking to a different tour (which would effectively bypass any tour-availability checks at the create step). Changing tours is a new booking, not an edit.
>
> **Defense talking point — Wholesale addon replacement.** Inside the transaction we `DELETE FROM booking_addons WHERE booking_id = ?` and then re-insert the new selection. This is simpler and more correct than diffing old vs. new — there are at most five addons per booking, so the delete+insert pattern is cheap. Wrapped in a transaction, it's also safe: if the new INSERTs fail, the rollback restores the original addons.

---

### File 7: `api/bookings/delete.php`

```php
<?php
/**
 * POST /api/bookings/delete.php
 * Body: { "booking_id": 42 }
 *
 * Hard-deletes a CANCELLED booking. Upcoming and completed bookings cannot
 * be deleted — they're business records. The cascade on booking_addons
 * cleans up child rows automatically.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['booking_id']);

$booking_id = clean_int($payload['booking_id'], 1);
$user_id    = $_SESSION['user_id'];

// Confirm ownership + correct status
$check = $pdo->prepare("
    SELECT id, status FROM bookings
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$booking_id, $user_id]);
$row = $check->fetch();

if (!$row) {
    json_error('Booking not found.', 404);
}
if ($row['status'] !== 'cancelled') {
    json_error('Only cancelled bookings can be removed from history.', 400);
}

// booking_addons has ON DELETE CASCADE → child rows clean themselves up
$pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$booking_id]);

json_success(['deleted_id' => $booking_id], 'Booking removed from history.');
```

> **Defense talking point — Why hard-delete is restricted to cancelled.** Upcoming bookings are commitments to a future date; deleting one silently would lose the operations team's visibility. Completed bookings are revenue records; deleting one would break financial audit. By restricting `delete.php` to `status = 'cancelled'`, we preserve the audit trail while giving users the agency to clean up their own history.
>
> **Defense talking point — Cascade discipline.** The `booking_addons` FK is declared `ON DELETE CASCADE`. We don't have to manually `DELETE FROM booking_addons WHERE booking_id = ?` before deleting the parent — the database does it for us. This is exactly why we use real foreign keys instead of leaving cleanup to PHP.

---

## Frontend Integration

### Step 1 — Replace JS constants in `plan.html`

**Find** lines 453–558 (the `TOURS`, `ADDONS`, `TIME_SLOTS`, `PROMOS` constant declarations). **Replace with:**

```javascript
// Loaded from API on page init
let TOURS      = [];
let ADDONS     = [];
let TIME_SLOTS = [];
// PROMOS is no longer a client-side object — validated via API
```

**Find** the IIFE that bootstraps the page (search for the first `renderTourCards()` call near the bottom of `plan.html`). **Replace with:**

```javascript
// Bootstrap from API
Api.get('spots/tours.php')
    .then(function (data) {
        TOURS      = data.tours;
        ADDONS     = data.addons;
        TIME_SLOTS = data.time_slots;

        // Now safe to render the wizard
        const urlTour = new URLSearchParams(window.location.search).get('tour');
        if (urlTour && TOURS.some(t => t.id === urlTour)) {
            state.tourId = urlTour;
        }
        renderTourCards();
        renderTimeSlots();
        renderGuestCounters();
        renderAddonCards();
        updateSummary();
    })
    .catch(function (err) {
        console.error('Failed to load tour data:', err);
        document.querySelector('.wizard-container').innerHTML =
            '<p style="text-align:center;padding:64px;color:#dc2626;">' +
            'Could not load tour options. Please refresh the page.</p>';
    });
```

### Step 2 — Rewrite promo validation in `plan.html`

**Find** the function that handles the "Apply" button for promo codes (around line 729). **Replace its body with:**

```javascript
function applyPromo() {
    const codeEl = document.getElementById('promo-code');
    const msgEl  = document.getElementById('promo-msg');
    const code   = codeEl.value.trim().toUpperCase();

    if (!code) {
        msgEl.textContent = '';
        state.promoCode = '';
        state.promoDiscount = 0;
        updateSummary();
        return;
    }

    msgEl.textContent = 'Checking…';
    msgEl.style.color = '#6b7280';

    Api.post('promos/validate.php', { code: code })
        .then(function (data) {
            state.promoCode      = data.code;
            state.promoDiscount  = { type: data.discount_type, value: data.discount_value };
            msgEl.textContent    = '✓ Code applied: ' + data.label;
            msgEl.style.color    = 'var(--brand-green)';
            updateSummary();
        })
        .catch(function (err) {
            state.promoCode = '';
            state.promoDiscount = 0;
            msgEl.textContent = '✗ ' + err.message;
            msgEl.style.color = '#dc2626';
            updateSummary();
        });
}
```

And update `computeTotal()` so it uses the new `state.promoDiscount` shape:

```javascript
function computeTotal() {
    const tour = TOURS.find(t => t.id === state.tourId);
    if (!tour) return { subtotal: 0, discount: 0, total: 0 };

    const guests = state.adults + state.children;
    let subtotal = tour.price * guests;

    state.addons.forEach(id => {
        const a = ADDONS.find(x => x.id === id);
        if (a) subtotal += a.price * guests;
    });

    let discount = 0;
    if (state.promoCode && state.promoDiscount) {
        if (state.promoDiscount.type === 'percent') {
            discount = Math.round(subtotal * state.promoDiscount.value / 100);
        } else {
            discount = Math.min(state.promoDiscount.value, subtotal);
        }
    }

    return { subtotal, discount, total: Math.max(0, subtotal - discount) };
}
```

### Step 3 — Rewrite `submitBooking()` in `plan.html`

**Find** the entire `submitBooking()` function (line 940–983). **Replace with:**

```javascript
function submitBooking() {
    if (!document.getElementById('terms-checkbox').checked) {
        showError(4); return;
    }

    const btn = document.getElementById('btn-confirm');
    btn.disabled = true;
    btn.textContent = 'Booking…';

    const payload = {
        tour_id:    state.tourId,
        date:       state.date,
        time:       state.time,
        adults:     state.adults,
        children:   state.children,
        infants:    state.infants,
        addon_ids:  [...state.addons],
        promo_code: state.promoCode || null,
        name:       state.name,
        email:      state.email,
        phone:      state.phone,
        requests:   state.requests
    };

    Api.post('bookings/create.php', payload)
        .then(function (data) {
            document.getElementById('modal-ref').textContent = data.ref;
            document.getElementById('modal-desc').textContent =
                data.tour_name + ' on ' + formatDate(data.tour_date) +
                ' at ' + data.tour_time + '. Confirmation sent to ' + data.email + '.';
            document.getElementById('successModal').classList.add('active');

            btn.disabled = false;
            btn.textContent = 'Confirm Booking';
        })
        .catch(function (err) {
            alert('Booking failed: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Confirm Booking';
        });
}
```

> **Notice we deleted the entire `localStorage.setItem('tara_bookings', ...)` block.** The DB is now the single source of truth. Booking history will be re-fetched on the profile page in the next step.

### Step 4 — Rewrite booking history in `profile.html`

**Find** the helpers around lines 341–345 (`getBookings()` / `saveBookings()` reading localStorage). **Replace with:**

```javascript
function loadBookings() {
    var listEl = document.getElementById('bookings-list');
    if (!listEl) return;

    listEl.innerHTML = '<p style="text-align:center;padding:24px;color:#6b7280;">Loading bookings…</p>';

    Api.get('bookings/list.php')
        .then(function (data) {
            if (!data.bookings.length) {
                listEl.innerHTML = '<p style="text-align:center;padding:48px;color:#6b7280;">No bookings yet. <a href="plan.html" style="color:var(--brand-green);">Plan your first tour →</a></p>';
                return;
            }

            listEl.innerHTML = data.bookings.map(renderBookingCard).join('');
        })
        .catch(function (err) {
            if (err.code === 'AUTH_REQUIRED') {
                window.location.href = 'login.html';
                return;
            }
            listEl.innerHTML = '<p style="color:#dc2626;text-align:center;padding:24px;">Could not load bookings: ' + err.message + '</p>';
        });
}

function renderBookingCard(b) {
    var statusColor = { upcoming:'#1D9E75', completed:'#6b7280', cancelled:'#dc2626' }[b.status] || '#6b7280';
    var addonsList  = b.addons.length
        ? '<div style="font-size:0.85rem;color:#6b7280;margin-top:6px;">+ ' + b.addons.map(function(a){ return a.name; }).join(', ') + '</div>'
        : '';

    // Status-conditional controls
    var controls = '';
    if (b.status === 'upcoming') {
        controls =
            '<div style="margin-left:auto;display:flex;flex-direction:column;gap:6px;">' +
                '<button class="btn-primary"  onclick=\'openEditModal(' + JSON.stringify(b).replace(/'/g, "&#39;") + ')\' style="padding:8px 16px;border-radius:8px;cursor:pointer;">Edit</button>' +
                '<button class="btn-outline"  onclick="cancelBooking(' + b.id + ')" style="padding:8px 16px;border-radius:8px;cursor:pointer;background:white;">Cancel</button>' +
            '</div>';
    } else if (b.status === 'cancelled') {
        controls =
            '<button class="btn-outline" onclick="deleteBooking(' + b.id + ')" ' +
            'style="margin-left:auto;padding:8px 16px;border-radius:8px;cursor:pointer;background:white;color:#dc2626;border-color:#dc2626;">Remove from history</button>';
    }

    return '<div class="booking-card" style="border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:12px;display:flex;gap:16px;">' +
        '<img src="' + b.tour_image + '" style="width:100px;height:100px;border-radius:8px;object-fit:cover;flex-shrink:0;">' +
        '<div style="flex:1;">' +
            '<div style="display:flex;align-items:center;gap:8px;">' +
                '<strong>' + b.tour_name + '</strong>' +
                '<span style="padding:2px 8px;background:' + statusColor + '22;color:' + statusColor + ';border-radius:99px;font-size:0.75rem;text-transform:uppercase;">' + b.status + '</span>' +
            '</div>' +
            '<div style="color:#6b7280;font-size:0.9rem;margin-top:4px;">' + b.tour_date + ' · ' + b.tour_time + ' · ' + (b.adults + b.children) + ' guests</div>' +
            '<div style="font-size:0.85rem;margin-top:4px;">Ref: <code>' + b.ref + '</code> · ₱' + b.total.toLocaleString() + '</div>' +
            addonsList +
        '</div>' +
        controls +
    '</div>';
}

function cancelBooking(bookingId) {
    if (!confirm('Cancel this booking? This will mark it as cancelled but keep it in your history.')) return;

    Api.post('bookings/cancel.php', { booking_id: bookingId })
        .then(function () {
            loadBookings(); // refresh
        })
        .catch(function (err) {
            alert('Could not cancel: ' + err.message);
        });
}

function deleteBooking(bookingId) {
    if (!confirm('Permanently remove this cancelled booking from your history? This cannot be undone.')) return;

    Api.post('bookings/delete.php', { booking_id: bookingId })
        .then(function () {
            loadBookings(); // refresh
        })
        .catch(function (err) {
            alert('Could not remove: ' + err.message);
        });
}

// Auto-load on page load
loadBookings();
```

> **Note:** Your `profile.html` should have a `<div id="bookings-list"></div>` already, or one of the existing booking tab containers — search the existing markup for `tara_bookings` usage. Adjust the ID to match the container you find.

---

### Step 5 — Add the Edit Modal markup to `profile.html`

Append this modal markup somewhere near the end of `profile.html`, just before the closing `</body>` tag. It's hidden by default and shown by `openEditModal()`.

```html
<!-- Edit Booking Modal -->
<div id="editBookingModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:white;border-radius:16px;max-width:560px;width:100%;max-height:90vh;overflow-y:auto;padding:28px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Edit Booking</h2>
            <button onclick="closeEditModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#6b7280;">×</button>
        </div>

        <div id="edit-booking-tour-info" style="background:#f9fafb;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:0.9rem;color:#6b7280;">
            <!-- tour name + ref shown here, locked -->
        </div>

        <form id="edit-booking-form" onsubmit="submitEditBooking(event)">
            <input type="hidden" id="edit-booking-id">

            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label for="edit-date">Date</label>
                    <input type="date" id="edit-date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit-time">Time</label>
                    <select id="edit-time" class="form-control" required>
                        <option value="6:00 AM">6:00 AM — Sunrise</option>
                        <option value="8:00 AM">8:00 AM — Morning</option>
                        <option value="1:00 PM">1:00 PM — Afternoon</option>
                    </select>
                </div>
            </div>

            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label for="edit-adults">Adults</label>
                    <input type="number" id="edit-adults" class="form-control" min="1" max="20" required>
                </div>
                <div class="form-group">
                    <label for="edit-children">Children</label>
                    <input type="number" id="edit-children" class="form-control" min="0" max="20">
                </div>
                <div class="form-group">
                    <label for="edit-infants">Infants</label>
                    <input type="number" id="edit-infants" class="form-control" min="0" max="20">
                </div>
            </div>

            <div class="form-group">
                <label>Add-ons</label>
                <div id="edit-addons" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:0.9rem;">
                    <!-- addon checkboxes inserted by JS -->
                </div>
            </div>

            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label for="edit-promo">Promo Code</label>
                    <input type="text" id="edit-promo" class="form-control" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label for="edit-phone">Contact Phone</label>
                    <input type="tel" id="edit-phone" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="edit-requests">Special Requests</label>
                <textarea id="edit-requests" class="form-control" rows="2"></textarea>
            </div>

            <div id="edit-msg" style="margin-bottom:12px;font-size:0.9rem;"></div>

            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn-outline"  onclick="closeEditModal()" style="padding:10px 20px;border-radius:8px;background:white;cursor:pointer;">Discard</button>
                <button type="submit" class="btn-primary" style="padding:10px 20px;border-radius:8px;">Save Changes</button>
            </div>
        </form>
    </div>
</div>
```

### Step 6 — Add the Edit Modal handlers to `profile.html`

Add these functions to the script block in `profile.html` (alongside `loadBookings` from Step 4). They use the addon list cached on `window.ALL_ADDONS` — populated from a one-time fetch of `/api/spots/tours.php`.

```javascript
// One-time fetch of available addons (cached on window for the modal)
Api.get('spots/tours.php').then(function (data) {
    window.ALL_ADDONS = data.addons;
}).catch(function () { window.ALL_ADDONS = []; });

function openEditModal(booking) {
    document.getElementById('edit-booking-id').value = booking.id;
    document.getElementById('edit-booking-tour-info').innerHTML =
        '<strong>' + booking.tour_name + '</strong> · Ref <code>' + booking.ref + '</code><br>' +
        '<span style="color:#6b7280;">Tour cannot be changed. Cancel and rebook to switch tours.</span>';

    document.getElementById('edit-date').value     = booking.tour_date;
    document.getElementById('edit-time').value     = booking.tour_time;
    document.getElementById('edit-adults').value   = booking.adults;
    document.getElementById('edit-children').value = booking.children;
    document.getElementById('edit-infants').value  = booking.infants;
    document.getElementById('edit-promo').value    = booking.promo_code || '';
    document.getElementById('edit-phone').value    = booking.phone || '';
    document.getElementById('edit-requests').value = booking.requests || '';

    // Render addon checkboxes with current booking's addons pre-checked
    var bookedAddonIds = booking.addons.map(function (a) { return a.addon_id; });
    var addonsHtml = (window.ALL_ADDONS || []).map(function (a) {
        var checked = bookedAddonIds.indexOf(a.id) >= 0 ? 'checked' : '';
        return '<label style="display:flex;align-items:center;gap:6px;padding:6px;border:1px solid #e5e7eb;border-radius:8px;cursor:pointer;">' +
                  '<input type="checkbox" value="' + a.id + '" class="edit-addon-cb" ' + checked + '>' +
                  '<span>' + a.name + ' (₱' + a.price + ')</span>' +
               '</label>';
    }).join('');
    document.getElementById('edit-addons').innerHTML = addonsHtml;

    // Set min date to tomorrow
    var tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('edit-date').min = tomorrow.toISOString().split('T')[0];

    document.getElementById('edit-msg').textContent = '';
    document.getElementById('editBookingModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editBookingModal').style.display = 'none';
}

function submitEditBooking(e) {
    e.preventDefault();
    var msgEl = document.getElementById('edit-msg');
    var btn   = e.target.querySelector('button[type="submit"]');

    var checkedAddonIds = Array.prototype.slice
        .call(document.querySelectorAll('.edit-addon-cb:checked'))
        .map(function (cb) { return cb.value; });

    var payload = {
        booking_id: parseInt(document.getElementById('edit-booking-id').value, 10),
        date:       document.getElementById('edit-date').value,
        time:       document.getElementById('edit-time').value,
        adults:     parseInt(document.getElementById('edit-adults').value, 10),
        children:   parseInt(document.getElementById('edit-children').value, 10) || 0,
        infants:    parseInt(document.getElementById('edit-infants').value, 10) || 0,
        addon_ids:  checkedAddonIds,
        promo_code: document.getElementById('edit-promo').value.trim() || null,
        phone:      document.getElementById('edit-phone').value.trim(),
        requests:   document.getElementById('edit-requests').value.trim()
    };

    btn.disabled = true;
    btn.textContent = 'Saving…';

    Api.post('bookings/update.php', payload)
        .then(function (data) {
            msgEl.textContent = '✓ Updated. New total: ₱' + data.total.toLocaleString();
            msgEl.style.color = 'var(--brand-green)';
            setTimeout(function () {
                closeEditModal();
                loadBookings();
            }, 900);
        })
        .catch(function (err) {
            msgEl.textContent = '✗ ' + err.message;
            msgEl.style.color = '#dc2626';
            btn.disabled = false;
            btn.textContent = 'Save Changes';
        });
}
```

> **Why a modal instead of reusing the wizard?** The booking wizard in `plan.html` has its own state machine and progress UI. Re-using it for edits would require splitting the wizard's logic into "create mode" and "edit mode" paths — high complexity, high bug risk, and the wizard's progressive disclosure doesn't fit the "I just want to change my guest count" use case. A dedicated modal on `profile.html` is much smaller, much easier to test, and matches the user's intent (a quick edit, not a re-entered booking).
>
> **Why include `promo_code` in the modal?** Users sometimes forget to enter their promo at original booking time. Edit is the natural place to add one. The server re-validates regardless of whether it changed.

---

## Testing

### Test 1 — Tour list loads from DB
1. Open `plan.html`.
2. Open DevTools → Network. Confirm `spots/tours.php` returns 6 tours.
3. **Expect:** tour cards render identically to the original hardcoded version.

### Test 2 — Promo validation (happy path)
1. Pick a tour, fill all steps until promo input is visible.
2. Type `TARA10`, click Apply.
3. **Expect:** ✓ Code applied: 10% off. Summary recalculates.

### Test 3 — Promo validation (invalid)
1. Type `FAKE123`, click Apply.
2. **Expect:** ✗ Promo code not found or expired.
3. Total reverts to subtotal.

### Test 4 — Guest booking (no login)
1. Log out. Complete the wizard with a real email.
2. Click Confirm Booking.
3. **Expect:** Success modal with `TPG-XXXXXX` ref.
4. Verify in MySQL: `SELECT * FROM bookings ORDER BY id DESC LIMIT 1;` — row exists, `user_id` is `NULL`, `reference` matches.

### Test 5 — Authenticated booking + history
1. Log in. Complete wizard. Confirm.
2. Open `profile.html` → booking history.
3. **Expect:** New booking appears at top with "upcoming" status.

### Test 6 — Server-side total enforcement
1. Open DevTools → Network. Click Confirm. Find the `create.php` request.
2. **Right-click → Copy as fetch.** Paste in console, edit `total: 1`, re-send.
3. **Expect:** Backend ignores the client `total` (it isn't read). DB total matches the recomputed value.

### Test 7 — Cancel booking
1. From booking history, click Cancel on an upcoming booking.
2. Confirm the prompt.
3. **Expect:** Status badge changes to "cancelled". Reloading the page shows the same state.
4. In MySQL: `cancelled_at` column has a timestamp.
5. (Edge case) Try cancelling the same booking again via Postman → **expect 409**.

### Test 8 — Cross-user privacy
1. Log in as User A. Note one of their booking IDs (say `42`).
2. Log out, log in as User B.
3. POST to `/api/bookings/cancel.php` with `{ booking_id: 42 }`.
4. **Expect:** 404 (not 403) — User B cannot enumerate User A's bookings.

### Test 9 — Update booking (happy path)
1. From booking history, click **Edit** on an upcoming booking.
2. **Expect:** Modal opens, fields pre-filled with the current values, tour name shown but not editable.
3. Change adults from 2 to 4, add the snorkel addon if not already present, apply promo `TARA10`. Click **Save Changes**.
4. **Expect:** Success message with new total. Modal closes after ~1s. Card refreshes with new totals.
5. Verify in MySQL: `bookings.adults = 4`, `bookings.discount > 0`, `booking_addons` has one row per checked addon.

### Test 10 — Update booking (validation)
1. In the Edit modal, set the date to today or yesterday.
2. **Expect:** Backend returns 400 *"Tours must be at least one day in the future."*
3. Try `adults: 0` → 400.
4. Try changing the date to a string like `"hello"` → 400 *"Invalid date format."*

### Test 11 — Update only allowed on upcoming
1. After cancelling a booking, find its ID.
2. POST to `/api/bookings/update.php` with that ID via Postman.
3. **Expect:** 400 *"Only upcoming bookings can be edited."*

### Test 12 — Tour ID cannot be changed
1. From DevTools → Network, capture an `update.php` request.
2. Modify the payload to inject `"tour_id": "different-tour-id"` and re-send.
3. **Expect:** Backend ignores the field entirely (it never reads `tour_id` from the payload). The booking still references its original tour in the database.

### Test 13 — Delete cancelled booking (happy path)
1. Cancel a booking first (Test 7).
2. On the cancelled card, click **Remove from history**. Confirm the prompt.
3. **Expect:** Card disappears from the list. Reloading shows the same.
4. Verify in MySQL: `SELECT * FROM bookings WHERE id = ?;` returns no row. `SELECT * FROM booking_addons WHERE booking_id = ?;` also empty (ON DELETE CASCADE).

### Test 14 — Delete restricted to cancelled
1. POST to `/api/bookings/delete.php` with the ID of an upcoming booking.
2. **Expect:** 400 *"Only cancelled bookings can be removed from history."*
3. Same for a completed booking → 400.

### Test 15 — Cross-user delete
1. Log in as User A, cancel a booking. Note its ID.
2. Log in as User B. POST `delete.php` with User A's booking_id.
3. **Expect:** 404 — same enumeration-prevention pattern.

---

## File Structure After Phase 8

```
api/
├── auth/                    (Phase 04)
├── bookings/
│   ├── create.php           ✓ NEW (Create)
│   ├── list.php             ✓ NEW (Read)
│   ├── update.php           ✓ NEW (Update — real edit)
│   ├── cancel.php           ✓ NEW (soft-update: status flip)
│   └── delete.php           ✓ NEW (Delete — hard delete cancelled rows)
├── promos/
│   └── validate.php         ✓ NEW
├── reviews/                 (Phase 07)
├── saved/                   (Phase 06)
└── spots/
    ├── get.php              (Phase 05)
    ├── list.php             (Phase 05)
    └── tours.php            ✓ NEW
plan.html                    (TOURS/ADDONS/PROMOS replaced with API calls)
profile.html                 (history + Edit modal + Remove-from-history buttons)
```

---

## Done When

- [ ] `plan.html` no longer contains hardcoded `TOURS`/`ADDONS`/`PROMOS` arrays.
- [ ] **Create:** Successful booking inserts a row in `bookings` (`reference` populated, `booked_at` stamped) and matching rows in `booking_addons` (with `price_charged`).
- [ ] Booking reference `TPG-XXXXXX` is server-generated.
- [ ] **Read:** Profile page shows real bookings from DB; localStorage path is dead code.
- [ ] **Update (real):** Users can edit upcoming bookings via the modal — date, time, guests, addons, promo. Total recomputes server-side. Tour ID is locked.
- [ ] **Update (soft):** Cancel button on upcoming bookings stamps `cancelled_at` and flips status.
- [ ] **Delete:** Cancelled bookings can be permanently removed via Remove-from-history button. Upcoming and completed bookings cannot be deleted (400 returned).
- [ ] Promo codes validated server-side; invalid codes cannot leak through the DOM.
- [ ] Total is recomputed server-side; client-supplied prices are ignored.
- [ ] Users cannot edit, cancel, or delete other users' bookings (404 returned).
- [ ] `booking_addons` cleans up via `ON DELETE CASCADE` when parent booking is deleted.

> **Rubric note.** Phase 08 makes Bookings the project's second full-CRUD demonstration after Reviews. During the demo, walk through Create → Read → Update (real edit, *not just* cancel) → Delete on a single booking record to show the complete lifecycle on a complex multi-table resource.

Continue to **[`09-CONTACT-MODULE.md`](./09-CONTACT-MODULE.md)** to make the contact form actually deliver messages.