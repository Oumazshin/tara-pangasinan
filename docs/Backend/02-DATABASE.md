# 02 — Database Schema

**Goal:** Design and create all MySQL tables needed for Tara Pangasinan, then seed the `spots` table by importing your existing `data/spots.json`.

**Why a relational schema?** Because the project rubric requires DBMS use, but also because using normalized tables (instead of dumping JSON into a blob column) lets you query things like *"average rating per category"*, *"top 5 most-saved spots"*, *"bookings in the next 7 days"* — concrete demos that impress panels.

---

## 1. Entity Overview

| Table | Purpose | Owns |
|---|---|---|
| `users` | Registered accounts | Profile fields, hashed password |
| `spots` | Tourist destinations | The 8 spots from `spots.json` |
| `spot_activities` | Activities for each spot (1:many) | "Snorkeling", "Island Hopping" |
| `spot_gallery` | Gallery images (1:many) | Image URLs |
| `spot_tips` | Visitor tips (1:many) | Tip text |
| `spot_stats` | Stats grid items (1:many) | Label/value pairs |
| `saved_spots` | User ↔ spot save (many:many) | `user_id`, `spot_id` |
| `reviews` | User-submitted reviews | Rating + text |
| `bookings` | Tour bookings | Reference, totals, status |
| `booking_addons` | Add-ons selected per booking | `booking_id`, `addon_id` |
| `tours` | Tour packages (from `plan.html`) | Name, price, image |
| `addons` | Add-on catalog | Snorkel gear, photographer, etc. |
| `promos` | Promo codes | `TARA10`, `PANGASINAN`, `FIRSTTIME` |
| `contact_messages` | Contact form submissions | First/last name, email, subject, message |

That's 14 tables. Use this header for your ER diagram if your rubric requires one.

### ER Diagram (text representation)

```
users (1) ─< (N) saved_spots (N) >─ (1) spots
users (1) ─< (N) reviews          (N) >─ (1) spots
users (1) ─< (N) bookings         (N) >─ (1) tours
bookings (1) ─< (N) booking_addons (N) >─ (1) addons
spots (1) ─< (N) spot_activities
spots (1) ─< (N) spot_gallery
spots (1) ─< (N) spot_tips
spots (1) ─< (N) spot_stats
```

---

## 2. The Schema File

Create the file below in your `sql/` folder.

**File:** `sql/schema.sql`

```sql
-- ─────────────────────────────────────────────────────────────
-- Tara Pangasinan — Database Schema
-- Target: MySQL 5.7+ / MariaDB 10.3+
-- Engine: InnoDB (foreign keys, transactions)
-- Charset: utf8mb4 (full Unicode incl. emoji)
-- ─────────────────────────────────────────────────────────────

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS booking_addons;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS saved_spots;
DROP TABLE IF EXISTS spot_activities;
DROP TABLE IF EXISTS spot_gallery;
DROP TABLE IF EXISTS spot_tips;
DROP TABLE IF EXISTS spot_stats;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS promos;
DROP TABLE IF EXISTS addons;
DROP TABLE IF EXISTS tours;
DROP TABLE IF EXISTS spots;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ─── USERS ────────────────────────────────────────────────────
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    first_name      VARCHAR(100)  NOT NULL,
    last_name       VARCHAR(100)  NOT NULL,
    phone           VARCHAR(30)   DEFAULT NULL,
    city            VARCHAR(150)  DEFAULT NULL,
    bio             TEXT          DEFAULT NULL,
    avatar_url      VARCHAR(500)  DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOTS ────────────────────────────────────────────────────
CREATE TABLE spots (
    id              VARCHAR(80)   PRIMARY KEY,            -- slug, e.g. "hundred-islands"
    title           VARCHAR(200)  NOT NULL,
    location        VARCHAR(150)  NOT NULL,
    category        ENUM('Nature','Beach','Historical','Festival','Food') NOT NULL,
    rating          DECIMAL(2,1)  NOT NULL DEFAULT 0.0,    -- 0.0 to 5.0
    reviews_count   INT UNSIGNED  NOT NULL DEFAULT 0,
    short_desc      VARCHAR(500)  NOT NULL,
    description     TEXT          NOT NULL,
    image           VARCHAR(500)  NOT NULL,
    lat             DECIMAL(10,7) DEFAULT NULL,
    lng             DECIMAL(10,7) DEFAULT NULL,
    hours           VARCHAR(150)  DEFAULT NULL,
    entrance        VARCHAR(150)  DEFAULT NULL,
    contact         VARCHAR(80)   DEFAULT NULL,
    website         VARCHAR(255)  DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_ACTIVITIES (1:many) ─────────────────────────────────
CREATE TABLE spot_activities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)   NOT NULL,
    activity        VARCHAR(150)  NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_GALLERY (1:many) ────────────────────────────────────
CREATE TABLE spot_gallery (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)   NOT NULL,
    image_url       VARCHAR(500)  NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_TIPS (1:many) ───────────────────────────────────────
CREATE TABLE spot_tips (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)   NOT NULL,
    tip             VARCHAR(500)  NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_STATS (1:many) ──────────────────────────────────────
CREATE TABLE spot_stats (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)   NOT NULL,
    label           VARCHAR(80)   NOT NULL,
    value           VARCHAR(150)  NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SAVED_SPOTS ──────────────────────────────────────────────
CREATE TABLE saved_spots (
    user_id         INT UNSIGNED  NOT NULL,
    spot_id         VARCHAR(80)   NOT NULL,
    saved_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, spot_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── REVIEWS ──────────────────────────────────────────────────
CREATE TABLE reviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED  NOT NULL,
    spot_id         VARCHAR(80)   NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL,            -- 1..5
    body            TEXT          NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot_created (spot_id, created_at),
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TOURS (referenced by bookings; mirrors plan.html data) ───
CREATE TABLE tours (
    id              VARCHAR(80)   PRIMARY KEY,            -- e.g. "hundred-islands"
    name            VARCHAR(200)  NOT NULL,
    location        VARCHAR(150)  NOT NULL,
    duration        VARCHAR(80)   NOT NULL,
    price           DECIMAL(10,2) NOT NULL,
    image           VARCHAR(500)  NOT NULL,
    badge           VARCHAR(80)   DEFAULT NULL,
    badge_color     VARCHAR(20)   DEFAULT NULL,
    rating          DECIMAL(2,1)  NOT NULL DEFAULT 0.0,
    reviews_count   INT UNSIGNED  NOT NULL DEFAULT 0,
    meeting_point   VARCHAR(200)  NOT NULL,
    includes        TEXT          NOT NULL,                -- JSON array string
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ADDONS ───────────────────────────────────────────────────
CREATE TABLE addons (
    id              VARCHAR(40)   PRIMARY KEY,            -- e.g. "snorkel"
    name            VARCHAR(150)  NOT NULL,
    description     VARCHAR(500)  NOT NULL,
    price           DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PROMOS ───────────────────────────────────────────────────
CREATE TABLE promos (
    code            VARCHAR(40)   PRIMARY KEY,
    type            ENUM('percent','fixed') NOT NULL,
    value           DECIMAL(10,2) NOT NULL,
    label           VARCHAR(120)  NOT NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    expires_at      DATETIME      DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── BOOKINGS ─────────────────────────────────────────────────
CREATE TABLE bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference       VARCHAR(40)   NOT NULL UNIQUE,         -- e.g. "TPG-AB12C3"
    user_id         INT UNSIGNED  DEFAULT NULL,            -- nullable: guest bookings allowed
    tour_id         VARCHAR(80)   NOT NULL,
    tour_date       DATE          NOT NULL,
    tour_time       VARCHAR(20)   NOT NULL,                -- "6:00 AM", "8:00 AM", "1:00 PM"
    adults          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    children        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    infants         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    promo_code      VARCHAR(40)   DEFAULT NULL,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(10,2) NOT NULL,
    status          ENUM('upcoming','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    contact_name    VARCHAR(150)  NOT NULL,
    contact_email   VARCHAR(255)  NOT NULL,
    contact_phone   VARCHAR(30)   NOT NULL,
    requests        TEXT          DEFAULT NULL,
    booked_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)    REFERENCES users(id)  ON DELETE SET NULL,
    FOREIGN KEY (tour_id)    REFERENCES tours(id)  ON DELETE RESTRICT,
    FOREIGN KEY (promo_code) REFERENCES promos(code) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_tour_date   (tour_id, tour_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── BOOKING_ADDONS (M:N) ─────────────────────────────────────
CREATE TABLE booking_addons (
    booking_id      INT UNSIGNED  NOT NULL,
    addon_id        VARCHAR(40)   NOT NULL,
    price_charged   DECIMAL(10,2) NOT NULL,                -- locked at time of booking

    PRIMARY KEY (booking_id, addon_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id)   REFERENCES addons(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CONTACT_MESSAGES ─────────────────────────────────────────
CREATE TABLE contact_messages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100)  NOT NULL,
    last_name       VARCHAR(100)  NOT NULL,
    email           VARCHAR(255)  NOT NULL,
    subject         VARCHAR(80)   NOT NULL,
    message         TEXT          NOT NULL,
    submitted_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read         TINYINT(1)    NOT NULL DEFAULT 0,

    INDEX idx_submitted (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Import the Schema

In phpMyAdmin:

1. Click your `tara_pangasinan` database in the left sidebar.
2. Click the **Import** tab.
3. **Choose File** → select `sql/schema.sql`.
4. Click **Go** at the bottom.

You should see "Import has been successfully finished, 14 queries executed."

Refresh the sidebar — all 14 tables should appear under `tara_pangasinan`.

> **Re-running:** The `DROP TABLE IF EXISTS` lines at the top let you re-run `schema.sql` safely while you're iterating. **Warning:** Each re-run wipes your data. Only do this during development.

---

## 4. Seed Lookup Tables

The `tours`, `addons`, and `promos` tables need to mirror what's currently hardcoded in `plan.html`. Create one more SQL file with seed data:

**File:** `sql/seed-lookups.sql`

```sql
-- ─── TOURS ───────────────────────────────────────────────────
INSERT INTO tours (id, name, location, duration, price, image, badge, badge_color, rating, reviews_count, meeting_point, includes) VALUES
('hundred-islands',  'Hundred Islands Boat Tour',          'Alaminos City', '6–8 hours', 1200.00, 'https://images.unsplash.com/photo-1544253138-09cb9f3f4e3c?w=800&auto=format&fit=crop', 'Most Popular', '#1D9E75', 4.8, 248, 'Alaminos City Pier',          '["Local boat crew","Life vest","Island hopping (3 islands)","Snorkeling equipment"]'),
('patar-beach',      'Patar Beach Sunset Experience',      'Bolinao',       '4–5 hours',  800.00, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&auto=format&fit=crop', 'Beach',        '#0284c7', 4.7, 134, 'Patar Beach Entrance',        '["Cottage rental","Welcome coconut drink","Bonfire setup","Sunset guided walk"]'),
('enchanted-cave',   'Enchanted Cave & Falls Combo',       'Bolinao',       '5–6 hours', 1500.00, 'https://images.unsplash.com/photo-1505051508008-923feaf90263?w=800&auto=format&fit=crop', 'Adventure',    '#7c3aed', 4.6,  97, 'Bolinao Town Hall',           '["Cave entry fee","Life vest","Falls access","Tour guide"]'),
('cape-bolinao',     'Cape Bolinao Lighthouse Trek',       'Bolinao',       '3–4 hours',  700.00, 'https://images.unsplash.com/photo-1559132279-0db9cd84c68d?w=800&auto=format&fit=crop', 'Historical',   '#b45309', 4.6,  92, 'Cape Bolinao Lighthouse Gate', '["Lighthouse climb access","Historical guide","Coastal trail walk","Photo stops"]'),
('lingayen-gulf',    'Lingayen Gulf Sunrise Tour',         'Lingayen',      '2–3 hours',  500.00, 'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=800&auto=format&fit=crop', 'Scenic',       '#0e7490', 4.4,  65, 'Lingayen Beach Boulevard',    '["Sunrise viewing","Historical markers tour","Bangus (milkfish) breakfast","Guide"]'),
('manaoag-church',   'Manaoag Heritage Pilgrimage',        'Manaoag',       '3–4 hours',  400.00, 'https://images.unsplash.com/photo-1548625361-9c636f3db5a8?w=800&auto=format&fit=crop', 'Pilgrimage',   '#78716c', 4.9, 180, 'Manaoag Church Main Gate',    '["Church tour guide","Historical walk","Souvenir voucher","Light refreshments"]');

-- ─── ADDONS ──────────────────────────────────────────────────
INSERT INTO addons (id, name, description, price) VALUES
('snorkel',   'Snorkeling Gear Set',     'Mask, fins, and snorkel for the full underwater experience.', 250.00),
('guide',     'Private Guide Upgrade',   'Dedicated guide just for your group throughout the tour.',    500.00),
('lunch',     'Seafood Lunch Pack',      'Fresh local seafood lunch prepared by the resort.',           450.00),
('photo',     'Photography Package',     'Professional drone + underwater photos delivered digitally.', 800.00),
('transport', 'Round-trip Transport',    'Air-conditioned van pick-up from Dagupan City.',              600.00);

-- ─── PROMOS ──────────────────────────────────────────────────
INSERT INTO promos (code, type, value, label) VALUES
('TARA10',     'percent',  10.00, '10% off'),
('PANGASINAN', 'fixed',   200.00, '₱200 off'),
('FIRSTTIME',  'percent',  15.00, '15% off');
```

Import this the same way: **Import** tab → choose file → Go.

---

## 5. Seed `spots` From `data/spots.json`

Rather than hand-writing 8 INSERT statements with all sub-tables, write a one-shot PHP script that reads your existing JSON and inserts everything correctly.

**File:** `sql/seed.php`

```php
<?php
/**
 * One-shot seeder: reads ../data/spots.json and inserts into the DB.
 * Run from browser: http://localhost/tara-pangasinan/sql/seed.php
 * DELETE this file after running (it has DB credentials exposure risk).
 */

require_once __DIR__ . '/../includes/db.php';

$jsonPath = __DIR__ . '/../data/spots.json';
if (!file_exists($jsonPath)) {
    die("Cannot find $jsonPath\n");
}

$raw   = file_get_contents($jsonPath);
$spots = json_decode($raw, true);

if (!is_array($spots)) {
    die("Failed to parse spots.json — check the file is valid JSON.\n");
}

try {
    $pdo->beginTransaction();

    // Clear existing spot data (cascades to sub-tables via FK)
    $pdo->exec("DELETE FROM spots");

    $insertSpot = $pdo->prepare("
        INSERT INTO spots
            (id, title, location, category, rating, reviews_count, short_desc, description, image, lat, lng, hours, entrance, contact, website)
        VALUES
            (:id, :title, :location, :category, :rating, :reviews_count, :short_desc, :description, :image, :lat, :lng, :hours, :entrance, :contact, :website)
    ");

    $insertActivity = $pdo->prepare("INSERT INTO spot_activities (spot_id, activity, sort_order) VALUES (?, ?, ?)");
    $insertGallery  = $pdo->prepare("INSERT INTO spot_gallery   (spot_id, image_url, sort_order) VALUES (?, ?, ?)");
    $insertTip      = $pdo->prepare("INSERT INTO spot_tips       (spot_id, tip, sort_order) VALUES (?, ?, ?)");
    $insertStat     = $pdo->prepare("INSERT INTO spot_stats      (spot_id, label, value, sort_order) VALUES (?, ?, ?, ?)");

    $count = 0;
    foreach ($spots as $spot) {
        $insertSpot->execute([
            ':id'            => $spot['id'],
            ':title'         => $spot['title'],
            ':location'      => $spot['location'],
            ':category'      => $spot['category'],
            ':rating'        => $spot['rating']   ?? 0.0,
            ':reviews_count' => $spot['reviews']  ?? 0,
            ':short_desc'    => $spot['shortDesc']    ?? '',
            ':description'   => $spot['description']  ?? '',
            ':image'         => $spot['image']        ?? '',
            ':lat'           => $spot['lat']          ?? null,
            ':lng'           => $spot['lng']          ?? null,
            ':hours'         => $spot['hours']        ?? null,
            ':entrance'      => $spot['entrance']     ?? null,
            ':contact'       => $spot['contact']      ?? null,
            ':website'       => $spot['website']      ?? null,
        ]);

        foreach (($spot['activities'] ?? []) as $i => $act) {
            $insertActivity->execute([$spot['id'], $act, $i]);
        }
        foreach (($spot['gallery']    ?? []) as $i => $img) {
            $insertGallery->execute([$spot['id'], $img, $i]);
        }
        foreach (($spot['tips']       ?? []) as $i => $tip) {
            $insertTip->execute([$spot['id'], $tip, $i]);
        }
        foreach (($spot['stats']      ?? []) as $i => $s) {
            $insertStat->execute([$spot['id'], $s['label'], $s['value'], $i]);
        }

        $count++;
    }

    $pdo->commit();

    echo "<pre>Seeded $count spots successfully.\n";
    echo "You can now DELETE this file (sql/seed.php).</pre>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<pre style='color:red'>Seed failed: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
```

**You can't run this yet** — it needs `includes/db.php` which is created in Phase 3. **After** Phase 3 is complete:

1. Visit <http://localhost/tara-pangasinan/sql/seed.php> once.
2. See "Seeded 8 spots successfully."
3. Delete `sql/seed.php` for security.

---

## 6. Verify the Schema

In phpMyAdmin, open the `spots` table → **Browse** tab. Initially empty (until you run the seeder in Phase 3). The structure should match:

```
id           varchar(80)    PK
title        varchar(200)
location     varchar(150)
category     enum(...)
rating       decimal(2,1)
...
```

Click **Structure** to confirm all 15 columns exist.

Run a sample query in the **SQL** tab to confirm the `category` enum works:

```sql
INSERT INTO spots (id, title, location, category, short_desc, description, image) 
VALUES ('test-1', 'Test', 'Test City', 'Nature', 'x', 'x', 'x');

SELECT * FROM spots WHERE id = 'test-1';

DELETE FROM spots WHERE id = 'test-1';
```

If all three queries succeed, your schema is healthy.

---

## 7. Notes on Design Decisions

**Why is `spots.id` a `VARCHAR` slug instead of an `INT`?**
Because `spots.json` already uses slugs like `"hundred-islands"`, and the frontend URLs are `details.html?id=hundred-islands`. Keeping the slug as PK means **zero changes to the URL scheme** and zero changes to existing JS that compares IDs.

**Why are activities/gallery/tips in separate tables instead of JSON columns?**
Because the rubric explicitly mentions DBMS. A panel will be more impressed by a normalized schema with foreign keys than by JSON columns that effectively reproduce localStorage. Separate tables also let you do `ORDER BY sort_order` cleanly and add/remove tips without rewriting the whole record.

**Why `user_id` is nullable on `bookings`?**
Because the booking wizard in `plan.html` lets guests book without an account. The contact info fields (`contact_name`, `contact_email`, `contact_phone`) hold their details. If a logged-in user books, we associate `user_id` too.

**Why `password_hash` and not `password`?**
PHP's `password_hash()` returns a bcrypt string like `$2y$10$...`. The column name reflects that the column never stores plaintext. We never `SELECT password FROM users WHERE password = ?` — we `SELECT password_hash FROM users WHERE email = ?` then `password_verify($input, $row['password_hash'])`. See Phase 04.

---

## Done When

- [ ] `sql/schema.sql` exists in the project.
- [ ] `sql/seed-lookups.sql` exists in the project.
- [ ] Both files were imported into phpMyAdmin without errors.
- [ ] 14 empty tables visible in the `tara_pangasinan` database.
- [ ] `tours`, `addons`, `promos` tables each have rows (you can see them in phpMyAdmin → Browse).
- [ ] `sql/seed.php` exists but **has not been run yet** (you'll run it after Phase 3).

Continue to **[`03-CORE-PHP.md`](./03-CORE-PHP.md)** to build the PHP foundation and AJAX layer.
