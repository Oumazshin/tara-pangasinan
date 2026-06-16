# 05 — Spots Module

**Goal:** Replace the static `fetch('data/spots.json')` calls (currently in `main.js` and inline in `map.html` and `saved.html`) with database-backed AJAX endpoints. The frontend response shape stays identical, so existing rendering code keeps working.

**Time estimate:** 1 hour. This is the simplest module — pure read endpoints.

---

## 1. Files Affected

| File | Action |
|---|---|
| `api/spots/list.php` | **NEW** — returns full list with embedded activities, gallery, tips, stats |
| `api/spots/get.php`  | **NEW** — returns a single spot by `id` |
| `js/main.js`          | MODIFIED — swap `fetch('data/spots.json')` for `Api.get('spots/list.php')` |
| `map.html`            | MODIFIED — same swap on its inline script |
| `saved.html`          | MODIFIED — same swap |

> **Important:** Keep `data/spots.json` in place. It's no longer loaded by the browser, but `sql/seed.php` reads it. Think of it as the **canonical source** the DB is seeded from.

---

## 2. Database Tables Used

`spots`, `spot_activities`, `spot_gallery`, `spot_tips`, `spot_stats` (already created in Phase 2).

---

## 3. API Endpoints

### 3.1 `GET /api/spots/list.php`

**Query parameters (all optional):**

| Param | Type | Effect |
|---|---|---|
| `category` | string | Filter by `Nature` / `Beach` / `Historical` / `Festival` / `Food` |
| `search`   | string | Case-insensitive substring match on `title` or `location` |
| `sort`     | string | `popular` (default, by `reviews_count desc`), `rating`, `name` |

**Success response:**

```json
{
  "success": true,
  "data": [
    {
      "id": "hundred-islands",
      "title": "Hundred Islands National Park",
      "location": "Alaminos City",
      "category": "Nature",
      "rating": 4.8,
      "reviews": 4948,
      "shortDesc": "124 islands and islets...",
      "description": "Full text...",
      "image": "assets/images/nature/Hundred Islands.jpg",
      "lat": 16.194,
      "lng": 119.99,
      "hours": "Open Daily 7AM – 5PM",
      "entrance": "₱200 Adults / ₱100 Children",
      "contact": "+63 75 551-1234",
      "website": "hundredislandsni.gov.ph",
      "activities": ["Island Hopping", "Snorkeling", "Kayaking", "Beach Picnic"],
      "gallery":    ["assets/images/nature/hundred-island-nature.jpg", "..."],
      "tips":       ["Best visited during the dry season...", "..."],
      "stats":      [{"label": "Islands", "value": "124"}, "..."]
    },
    ...
  ]
}
```

> The key names match what `spots.json` already has (`shortDesc`, `reviews`, not `reviews_count`). This means **zero rendering changes** in `main.js`.

### 3.2 `GET /api/spots/get.php?id=<slug>`

Returns a single spot in the same shape. 404 if not found.

---

## 4. Backend Code

### 4.1 `api/spots/list.php`

```php
<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

// Read filters
$category = clean_str($_GET['category'] ?? '', 40);
$search   = clean_str($_GET['search']   ?? '', 100);
$sort     = clean_str($_GET['sort']     ?? 'popular', 20);

$allowedCats = ['Nature', 'Beach', 'Historical', 'Festival', 'Food'];
$where  = [];
$params = [];

if ($category !== '' && in_array($category, $allowedCats, true)) {
    $where[]  = 'category = ?';
    $params[] = $category;
}
if ($search !== '') {
    $where[]  = '(title LIKE ? OR location LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$orderBy = 'reviews_count DESC';
if ($sort === 'rating') $orderBy = 'rating DESC, reviews_count DESC';
if ($sort === 'name')   $orderBy = 'title ASC';

$sql = "SELECT id, title, location, category, rating, reviews_count, short_desc, description, image, lat, lng, hours, entrance, contact, website FROM spots";
if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY ' . $orderBy;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$spots = $stmt->fetchAll();

if (empty($spots)) {
    json_success([]);
}

// Fetch all sub-data in 4 queries (avoid N+1)
$ids         = array_column($spots, 'id');
$placeholder = implode(',', array_fill(0, count($ids), '?'));

function fetchSub(PDO $pdo, string $sql, array $params): array {
    $s = $pdo->prepare($sql);
    $s->execute($params);
    $out = [];
    foreach ($s->fetchAll() as $row) {
        $out[$row['spot_id']][] = $row;
    }
    return $out;
}

$activities = fetchSub($pdo, "SELECT spot_id, activity FROM spot_activities WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order", $ids);
$gallery    = fetchSub($pdo, "SELECT spot_id, image_url FROM spot_gallery   WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order", $ids);
$tips       = fetchSub($pdo, "SELECT spot_id, tip       FROM spot_tips      WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order", $ids);
$stats      = fetchSub($pdo, "SELECT spot_id, label, value FROM spot_stats  WHERE spot_id IN ($placeholder) ORDER BY spot_id, sort_order", $ids);

// Assemble final shape (preserving keys expected by the existing frontend)
$out = [];
foreach ($spots as $s) {
    $id = $s['id'];
    $out[] = [
        'id'          => $id,
        'title'       => $s['title'],
        'location'    => $s['location'],
        'category'    => $s['category'],
        'rating'      => (float)$s['rating'],
        'reviews'     => (int)$s['reviews_count'],
        'shortDesc'   => $s['short_desc'],
        'description' => $s['description'],
        'image'       => $s['image'],
        'lat'         => $s['lat'] !== null ? (float)$s['lat'] : null,
        'lng'         => $s['lng'] !== null ? (float)$s['lng'] : null,
        'hours'       => $s['hours'],
        'entrance'    => $s['entrance'],
        'contact'     => $s['contact'],
        'website'     => $s['website'],
        'activities'  => array_column($activities[$id] ?? [], 'activity'),
        'gallery'     => array_column($gallery[$id]    ?? [], 'image_url'),
        'tips'        => array_column($tips[$id]       ?? [], 'tip'),
        'stats'       => array_map(function ($r) {
            return ['label' => $r['label'], 'value' => $r['value']];
        }, $stats[$id] ?? []),
    ];
}

json_success($out);
```

> The "4 queries instead of N+1" pattern is a small but important pedagogical point. Without it, listing 8 spots would issue 1 + (8 × 4) = 33 queries. With it, exactly **5** queries are issued regardless of how many spots exist.

### 4.2 `api/spots/get.php`

```php
<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/db.php';

$id = clean_str($_GET['id'] ?? '', 80);

if ($id === '' || !valid_slug($id)) {
    json_error('A valid spot id is required.', 'invalid_id', 422);
}

$stmt = $pdo->prepare("
    SELECT id, title, location, category, rating, reviews_count,
           short_desc, description, image, lat, lng,
           hours, entrance, contact, website
    FROM spots
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    json_error('Destination not found.', 'not_found', 404);
}

function fetchOne(PDO $pdo, string $sql, string $id): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

$activities = array_column(fetchOne($pdo, "SELECT activity  FROM spot_activities WHERE spot_id = ? ORDER BY sort_order", $id), 'activity');
$gallery    = array_column(fetchOne($pdo, "SELECT image_url FROM spot_gallery   WHERE spot_id = ? ORDER BY sort_order", $id), 'image_url');
$tips       = array_column(fetchOne($pdo, "SELECT tip       FROM spot_tips      WHERE spot_id = ? ORDER BY sort_order", $id), 'tip');
$stats      = array_map(function ($r) { return ['label' => $r['label'], 'value' => $r['value']]; },
                       fetchOne($pdo, "SELECT label, value FROM spot_stats WHERE spot_id = ? ORDER BY sort_order", $id));

json_success([
    'id'          => $s['id'],
    'title'       => $s['title'],
    'location'    => $s['location'],
    'category'    => $s['category'],
    'rating'      => (float)$s['rating'],
    'reviews'     => (int)$s['reviews_count'],
    'shortDesc'   => $s['short_desc'],
    'description' => $s['description'],
    'image'       => $s['image'],
    'lat'         => $s['lat'] !== null ? (float)$s['lat'] : null,
    'lng'         => $s['lng'] !== null ? (float)$s['lng'] : null,
    'hours'       => $s['hours'],
    'entrance'    => $s['entrance'],
    'contact'     => $s['contact'],
    'website'     => $s['website'],
    'activities'  => $activities,
    'gallery'     => $gallery,
    'tips'        => $tips,
    'stats'       => $stats,
]);
```

---

## 5. Frontend Integration

### 5.1 Update `js/main.js`

Find the existing fetch block around **line 152**:

```javascript
fetch('data/spots.json')
    .then(function (r) { return r.json(); })
    .then(function (data) { spotsData = data; initPage(); })
    .catch(function (err) {
        console.error('Error loading spots:', err);
        ...
    });
```

**Replace with:**

```javascript
Api.get('spots/list.php')
    .then(function (data) { spotsData = data; initPage(); })
    .catch(function (err) {
        console.error('Error loading spots:', err);
        var g = document.getElementById('exploreGrid') || document.getElementById('destinationsGrid');
        if (g) g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:#6b7280;">Failed to load destinations.</div>';
    });
```

Two differences:

- `fetch().then(r => r.json())` is replaced by `Api.get(...)`, which already returns the parsed `data` field.
- Same `then(data => ...)` and `.catch(...)` callbacks — your existing `initPage()` is untouched.

### 5.2 Update `map.html`

Find around **line 179**:

```javascript
fetch('data/spots.json')
    .then(r => r.json())
    .then(spots => { /* ...marker logic... */ })
    .catch(...);
```

**Replace with:**

```javascript
Api.get('spots/list.php')
    .then(spots => { /* ...marker logic unchanged... */ })
    .catch(err => { console.error(err); });
```

And ensure `<script src="js/api.js"></script>` is loaded **before** the inline script. Add it just above the existing `<script>` block at the bottom of `map.html`.

### 5.3 Update `saved.html`

Find around **line 134**:

```javascript
fetch('data/spots.json')
    .then(r => r.json())
    .then(spots => { window._spotsCache = spots; renderSaved(spots); })
    .catch(...);
```

**Replace with:**

```javascript
Api.get('spots/list.php')
    .then(spots => { window._spotsCache = spots; renderSaved(spots); })
    .catch(() => {
        document.getElementById('savedGrid').innerHTML =
            '<div style="grid-column:1/-1;text-align:center;padding:60px;color:#6b7280;font-family:Inter,sans-serif;">Failed to load spots. Please refresh.</div>';
    });
```

And ensure `<script src="js/api.js"></script>` loads before the inline `<script>` block.

### 5.4 (Optional) Server-side Filtering on Explore

Currently `js/main.js` does category/search/sort filtering client-side. That's fine — but you can demonstrate "real" DB filtering by switching the explore-page to refetch on every filter change:

```javascript
function filteredFromServer() {
    return Api.get('spots/list.php', {
        category: cat === 'All' ? '' : cat,
        search:   query,
        sort:     sort
    });
}
```

This is a stretch goal and **not required**. Leave the existing client-side filtering in place to keep the diff minimal.

---

## 6. Testing

### 6.1 Direct API Test

Visit these URLs in your browser:

| URL | Expected |
|---|---|
| <http://localhost/tara-pangasinan/api/spots/list.php> | JSON array of 8 spots |
| <http://localhost/tara-pangasinan/api/spots/list.php?category=Nature> | Subset (Hundred Islands, Bolinao Falls, Enchanted Cave) |
| <http://localhost/tara-pangasinan/api/spots/list.php?search=patar> | Only Patar Beach |
| <http://localhost/tara-pangasinan/api/spots/list.php?sort=name> | Alphabetical |
| <http://localhost/tara-pangasinan/api/spots/get.php?id=hundred-islands> | Single object |
| <http://localhost/tara-pangasinan/api/spots/get.php?id=fake-id> | 404 with `not_found` error |

### 6.2 UI Walk-Through

1. Reload <http://localhost/tara-pangasinan/explore.html>.
2. DevTools → Network → filter `Fetch/XHR`. Confirm `list.php` is called (not `spots.json`).
3. Click any card → details page shows the spot fully (activities, tips, gallery, stats — all from DB now).
4. Reload `map.html` — markers appear. (You may need to add `<script src="js/api.js"></script>` to `map.html` first.)
5. Reload `saved.html` after saving a spot — it loads correctly.

### 6.3 Comparison Trick (for Defense)

A slick demo: edit a spot's title directly in phpMyAdmin (`spots` table → `id = hundred-islands` → change `title` to *"Hundred Islands National Park ⭐"*). Reload `explore.html`. The change appears immediately — without redeploying or editing any file. **This is the headline benefit of having a database.**

---

## 7. Why This Module Matters for the Rubric

When the panel asks *"What's the difference between using `spots.json` and using the database?"*, your answer should hit:

1. **Single source of truth.** Editing one row in MySQL updates every page that displays that spot, instantly. With JSON, every developer had a separate copy.
2. **Query power.** `WHERE category = 'Nature' AND rating > 4.5 ORDER BY reviews_count DESC` is a one-line server-side filter. With JSON, you'd ship the entire file to the browser and filter in JS.
3. **Relational integrity.** Activities, gallery, tips, stats live in separate tables linked by foreign keys. Deleting a spot cascades cleanly. With JSON, sub-arrays are duplicated and easy to corrupt.
4. **Mutations.** Reviews, bookings, saved spots — anything user-generated — needs INSERT. A static JSON file can't be written to from a web request.

---

## 8. File Tree After This Phase

```
tara-pangasinan/
├── api/
│   ├── auth/                 (from Phase 04)
│   └── spots/
│       ├── list.php          ✓ NEW
│       └── get.php           ✓ NEW
├── data/
│   └── spots.json            (kept as seed source)
├── js/
│   ├── api.js
│   └── main.js               (one line changed)
└── map.html, saved.html      (one fetch line each changed; api.js included)
```

---

## Done When

- [ ] `api/spots/list.php` returns valid JSON with all 8 spots and sub-arrays.
- [ ] `api/spots/get.php?id=hundred-islands` returns a single complete spot.
- [ ] `explore.html`, `home.html`, `details.html` render identically to before — but DevTools shows the API call instead of `spots.json`.
- [ ] `map.html` and `saved.html` are also migrated and have `api.js` included.
- [ ] Filtering URLs (`?category=Beach`, `?search=bolinao`) return correct subsets.
- [ ] Editing a spot title in phpMyAdmin and reloading the page reflects the change.

Continue to **[`06-SAVED-MODULE.md`](./06-SAVED-MODULE.md)** for save-to-DB, or skip ahead to **[`07-REVIEWS-MODULE.md`](./07-REVIEWS-MODULE.md)** if you'd rather do user-generated content first.
