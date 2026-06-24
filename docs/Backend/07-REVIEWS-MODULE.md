# 07 — Reviews Module

> **Phase 7 of 11** · Owner: _Reviews + Details page lead_ · Depends on: 03, 04, 05

This phase replaces the three hardcoded review cards on `details.html` (lines 137–160) with a real, database-backed reviews system. Logged-in users can post a star rating + comment; everyone can read. The spot's overall rating and review count are recomputed automatically.

---

## Goal

| Currently | After this phase |
|---|---|
| 3 hardcoded review cards copy-pasted on every spot's details page. | Reviews fetched per-spot from the `reviews` table on page load. |
| `spot.rating` and `spot.reviews` are static numbers from `spots.json`. | `rating` / `reviews_count` recomputed in `spots` table on every insert. |
| "Load More Reviews" button is a no-op. | Pagination via `?offset=N&limit=10`. |
| Anyone can fake-post (no form exists). | Only logged-in users see the "Write a Review" form; one review per user per spot (UNIQUE constraint). |

---

## Files Affected

**New files**
- `api/reviews/list.php`
- `api/reviews/create.php`

**Edited files**
- `details.html` — replace hardcoded review cards with `<div id="review-cards-container"></div>`, add review form
- `js/main.js` — extend `initDetailsPage()` to call reviews API instead of static rendering

**Touched tables** — `reviews`, `spots` (rating + reviews_count recomputed)

> **Schema column reminders.** The `reviews` table uses `body` (not `comment`) for the review text. The `users` table uses `first_name` and `last_name` (snake_case). These names come from `02-DATABASE.md` and must be preserved throughout.

---

## API Endpoints

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| `GET`    | `/api/reviews/list.php?spot_id=hundred-islands&limit=10&offset=0` | No   | **Read** — list reviews for a spot, newest first. |
| `POST`   | `/api/reviews/create.php` | **Yes** | **Create** — insert one review for current user. |
| `POST`   | `/api/reviews/update.php` | **Yes** | **Update** — edit current user's existing review. |
| `POST`   | `/api/reviews/delete.php` | **Yes** | **Delete** — remove current user's review. |

> **Why this module demonstrates full CRUD.** The Reviews resource is the cleanest example of all four operations on a single entity. Create posts a new row, Read lists existing rows, Update modifies the row the current user owns, and Delete removes it (with the spot's aggregate stats recomputed in each mutating operation). This makes it easy to point to during the demo when the panel asks where CRUD is.

### Why these endpoints?
- **`list.php`** is public — anyone visiting `details.html?id=hundred-islands` (even guests) sees real reviews.
- **`create.php`** requires login because anonymous reviews are spam bait, and we need a `user_id` to enforce "one review per user per spot" (the `UNIQUE(user_id, spot_id)` constraint on `reviews` from Phase 02).
- **`update.php`** and **`delete.php`** require login *and* enforce ownership — users can only modify or remove the reviews they themselves authored. Cross-user tampering returns 404 (not 403) to avoid leaking review IDs.

---

## Backend Code

### File 1: `api/reviews/list.php`

```php
<?php
/**
 * GET /api/reviews/list.php?spot_id=<slug>&limit=10&offset=0
 *
 * Returns reviews for a spot, newest first, paginated.
 * No auth required — reviews are public.
 *
 * Success response shape:
 * {
 *   "success": true,
 *   "data": {
 *     "reviews": [
 *       {
 *         "id": 14,
 *         "user_name": "Maria Santos",
 *         "rating": 5,
 *         "body": "Absolutely breathtaking...",
 *         "created_at": "2026-05-21 14:32:01",
 *         "date_label": "May 2026"
 *       }
 *     ],
 *     "total": 47,
 *     "has_more": true
 *   }
 * }
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/session.php';   // bootstrap session so we can read $_SESSION['user_id']
require_once __DIR__ . '/../../includes/validation.php';

$spot_id = clean_str($_GET['spot_id'] ?? '');
$limit   = clean_int($_GET['limit']  ?? 10, 1, 50);
$offset  = clean_int($_GET['offset'] ?? 0, 0, 10000);

if (!valid_slug($spot_id)) {
    json_error('Invalid spot_id.', 400);
}

// Confirm spot exists (cheap check; prevents enumeration noise)
$exists = $pdo->prepare('SELECT 1 FROM spots WHERE id = ? LIMIT 1');
$exists->execute([$spot_id]);
if (!$exists->fetchColumn()) {
    json_error('Spot not found.', 404);
}

// Total count (for "Load More" button visibility)
$count_stmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE spot_id = ?');
$count_stmt->execute([$spot_id]);
$total = (int) $count_stmt->fetchColumn();

// Page of reviews — JOIN users for name; flag owner rows so frontend can show edit/delete
$current_user_id = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.user_id,
        r.rating,
        r.body,
        r.created_at,
        CONCAT(u.first_name, ' ', u.last_name) AS user_name
    FROM reviews r
    INNER JOIN users u ON u.id = r.user_id
    WHERE r.spot_id = ?
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
// Note: LIMIT/OFFSET need int binding — bindValue handles this safely
$stmt->bindValue(1, $spot_id, PDO::PARAM_STR);
$stmt->bindValue(2, $limit,   PDO::PARAM_INT);
$stmt->bindValue(3, $offset,  PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

// Add human-readable date label ("May 2026") + owner flag
foreach ($reviews as &$r) {
    $r['rating']     = (int) $r['rating'];
    $r['is_owner']   = ((int) $r['user_id'] === $current_user_id);
    $r['date_label'] = date('F Y', strtotime($r['created_at']));
    unset($r['user_id']); // don't leak user IDs to the client
}
unset($r);

json_success([
    'reviews'  => $reviews,
    'total'    => $total,
    'has_more' => ($offset + $limit) < $total,
]);
```

**Why we JOIN `users` instead of storing `user_name` denormalized in `reviews`:**
If a user updates their name in their profile, every review they ever posted updates too. No stale display names.

---

### File 2: `api/reviews/create.php`

```php
<?php
/**
 * POST /api/reviews/create.php
 * Body: { "spot_id": "hundred-islands", "rating": 5, "body": "..." }
 *
 * Requires login. Inserts one review and recomputes spot's avg rating + count.
 * If user already reviewed this spot, returns 409 Conflict (UNIQUE constraint).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login(); // 401 if not authenticated

$payload = read_json_body();
require_fields($payload, ['spot_id', 'rating', 'body']);

$spot_id = clean_str($payload['spot_id']);
$rating  = clean_int($payload['rating'], 1, 5);
$body    = clean_str($payload['body']);
$user_id = $_SESSION['user_id'];

// ── Validation ────────────────────────────────────────────────
if (!valid_slug($spot_id)) {
    json_error('Invalid spot_id.', 400);
}
$body_len = mb_strlen($body);
if ($body_len < 10) {
    json_error('Review must be at least 10 characters.', 400);
}
if ($body_len > 1000) {
    json_error('Review cannot exceed 1000 characters.', 400);
}

// ── Verify spot exists ───────────────────────────────────────
$exists = $pdo->prepare('SELECT 1 FROM spots WHERE id = ? LIMIT 1');
$exists->execute([$spot_id]);
if (!$exists->fetchColumn()) {
    json_error('Spot not found.', 404);
}

// ── Insert + recompute, wrapped in a transaction ─────────────
try {
    $pdo->beginTransaction();

    // Insert review (UNIQUE(user_id, spot_id) will throw if duplicate)
    $ins = $pdo->prepare("
        INSERT INTO reviews (user_id, spot_id, rating, body, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $ins->execute([$user_id, $spot_id, $rating, $body]);
    $review_id = (int) $pdo->lastInsertId();

    // Recompute aggregate stats on the spot
    $agg = $pdo->prepare("
        UPDATE spots SET
            rating        = (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE spot_id = ?),
            reviews_count = (SELECT COUNT(*)              FROM reviews WHERE spot_id = ?)
        WHERE id = ?
    ");
    $agg->execute([$spot_id, $spot_id, $spot_id]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();

    // 23000 = integrity constraint violation; 1062 = duplicate entry
    if ($e->getCode() === '23000') {
        json_error('You have already reviewed this spot.', 409);
    }
    throw $e; // re-throw; handled by global handler in db.php
}

// Fetch new totals so frontend can update stars without re-fetching
$new = $pdo->prepare('SELECT rating, reviews_count FROM spots WHERE id = ?');
$new->execute([$spot_id]);
$updated = $new->fetch();

json_success([
    'review_id'     => $review_id,
    'new_rating'    => (float) $updated['rating'],
    'reviews_count' => (int)   $updated['reviews_count'],
], 'Review posted successfully.');
```

> **Defense talking point — Transactions.** This handler updates two tables (`reviews` + `spots`). If the spot-aggregate update fails halfway, we don't want an orphan review with the spot's count out of sync. `beginTransaction()` / `commit()` / `rollBack()` guarantee atomicity — either both writes happen, or neither does.

---

### File 3: `api/reviews/update.php`

```php
<?php
/**
 * POST /api/reviews/update.php
 * Body: { "review_id": 14, "rating": 4, "body": "Updated text..." }
 *
 * Updates a review owned by the current user. The spot's aggregate rating
 * is recomputed in the same transaction so display stays in sync.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['review_id', 'rating', 'body']);

$review_id = clean_int($payload['review_id'], 1);
$rating    = clean_int($payload['rating'], 1, 5);
$body      = clean_str($payload['body']);
$user_id   = $_SESSION['user_id'];

// ── Validation ───────────────────────────────────────────────
$body_len = mb_strlen($body);
if ($body_len < 10) {
    json_error('Review must be at least 10 characters.', 400);
}
if ($body_len > 1000) {
    json_error('Review cannot exceed 1000 characters.', 400);
}

// ── Verify ownership (404 not 403 to avoid ID enumeration) ──
$check = $pdo->prepare("
    SELECT id, spot_id FROM reviews
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$review_id, $user_id]);
$row = $check->fetch();
if (!$row) {
    json_error('Review not found.', 404);
}
$spot_id = $row['spot_id'];

// ── Update + recompute aggregate, in one transaction ────────
try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare("
        UPDATE reviews
        SET rating = ?, body = ?
        WHERE id = ?
    ");
    $upd->execute([$rating, $body, $review_id]);

    $agg = $pdo->prepare("
        UPDATE spots SET
            rating        = (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE spot_id = ?),
            reviews_count = (SELECT COUNT(*)              FROM reviews WHERE spot_id = ?)
        WHERE id = ?
    ");
    $agg->execute([$spot_id, $spot_id, $spot_id]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

$new = $pdo->prepare('SELECT rating, reviews_count FROM spots WHERE id = ?');
$new->execute([$spot_id]);
$updated = $new->fetch();

json_success([
    'review_id'     => $review_id,
    'new_rating'    => (float) $updated['rating'],
    'reviews_count' => (int)   $updated['reviews_count'],
], 'Review updated.');
```

---

### File 4: `api/reviews/delete.php`

```php
<?php
/**
 * POST /api/reviews/delete.php
 * Body: { "review_id": 14 }
 *
 * Deletes a review owned by the current user. The spot's aggregate rating
 * is recomputed afterward so display stays in sync.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();

$payload = read_json_body();
require_fields($payload, ['review_id']);

$review_id = clean_int($payload['review_id'], 1);
$user_id   = $_SESSION['user_id'];

// ── Verify ownership ────────────────────────────────────────
$check = $pdo->prepare("
    SELECT id, spot_id FROM reviews
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$check->execute([$review_id, $user_id]);
$row = $check->fetch();
if (!$row) {
    json_error('Review not found.', 404);
}
$spot_id = $row['spot_id'];

// ── Delete + recompute, in one transaction ──────────────────
try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$review_id]);

    // After deletion, the spot may have 0 reviews — handle that case
    $agg = $pdo->prepare("
        UPDATE spots SET
            rating        = COALESCE(
                (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE spot_id = ?),
                0.0
            ),
            reviews_count = (SELECT COUNT(*) FROM reviews WHERE spot_id = ?)
        WHERE id = ?
    ");
    $agg->execute([$spot_id, $spot_id, $spot_id]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}

$new = $pdo->prepare('SELECT rating, reviews_count FROM spots WHERE id = ?');
$new->execute([$spot_id]);
$updated = $new->fetch();

json_success([
    'review_id'     => $review_id,
    'new_rating'    => (float) $updated['rating'],
    'reviews_count' => (int)   $updated['reviews_count'],
], 'Review deleted.');
```

> **Defense talking point — Ownership enforcement and CRUD completeness.** Both `update.php` and `delete.php` first verify the review belongs to the authenticated user by including `user_id = ?` in the lookup query. If the row doesn't match, we return 404 (not 403) — this prevents an attacker from probing valid review IDs by observing which return permission errors. With Create (`create.php`), Read (`list.php`), Update (`update.php`), and Delete (`delete.php`), the Reviews resource demonstrates the full CRUD cycle on a single entity, with aggregate consistency maintained transactionally in every mutating operation.

---

## Frontend Integration

### Step 1 — Replace hardcoded review cards in `details.html`

**Find** lines 136–161 (the entire `<div class="review-cards">` block). **Replace with:**

```html
<!-- Review form (visible only when logged in) -->
<div id="review-form-card" class="review-card" style="display:none;background:#f9fafb;border:2px dashed var(--brand-green);margin-bottom:24px;">
    <h3 style="margin-top:0;">Share Your Experience</h3>
    <form id="review-form">
        <div class="form-group">
            <label>Your Rating</label>
            <div id="rating-picker" style="display:flex;gap:6px;cursor:pointer;font-size:28px;color:#d1d5db;">
                <span data-val="1">★</span><span data-val="2">★</span><span data-val="3">★</span><span data-val="4">★</span><span data-val="5">★</span>
            </div>
            <input type="hidden" id="review-rating" value="5">
        </div>
        <div class="form-group">
            <label for="review-body">Your Review</label>
            <textarea id="review-body" class="form-control" rows="4" minlength="10" maxlength="1000" placeholder="Tell other travelers what made your visit special..." required></textarea>
        </div>
        <button type="submit" class="btn-primary" style="padding:12px 24px;border-radius:12px;">Post Review</button>
        <div id="review-form-msg" style="margin-top:12px;font-size:0.9rem;"></div>
    </form>
</div>

<!-- Login prompt (visible only when logged out) -->
<div id="review-login-prompt" style="display:none;text-align:center;padding:24px;background:#f9fafb;border-radius:12px;margin-bottom:24px;">
    <p style="margin:0;">
        <a href="login.html" style="color:var(--brand-green);font-weight:600;">Log in</a>
        to share your own review of this spot.
    </p>
</div>

<!-- Reviews list (rendered by JS) -->
<div class="review-cards" id="review-cards-container">
    <p style="text-align:center;color:var(--text-light);padding:32px;">Loading reviews…</p>
</div>

<!-- Load more button (shown only when more reviews exist) -->
<div style="text-align:center;margin-top:32px;display:none;" id="load-more-wrapper">
    <button class="btn-outline" id="btn-load-more-reviews" style="padding:12px 32px;border-radius:50px;background:white;cursor:pointer;font-family:inherit;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px;"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
        Load More Reviews
    </button>
</div>
```

### Step 2 — Extend `js/main.js` (details page handler)

**Find** the block around line 463–469 in `main.js` (the rating + stars render). **Append these new functions** to the end of `initDetailsPage()` (right before its closing brace, after the `Nearby` section):

```javascript
// ── Reviews ──────────────────────────────────────────────
var reviewsState = { offset: 0, limit: 10, total: 0 };

function renderReviewCard(r) {
    // Tiny avatar from initials (no external service)
    var initials = r.user_name.split(' ').map(function(p){ return p.charAt(0); }).join('').slice(0,2).toUpperCase();

    // Owner controls — only show on reviews authored by current user
    var ownerControls = r.is_owner
        ? '<div class="review-owner-controls" style="margin-left:auto;display:flex;gap:8px;">' +
              '<button class="btn-link" onclick="startEditReview(' + r.id + ', ' + r.rating + ', this)" data-review-id="' + r.id + '" style="background:none;border:none;color:var(--brand-green);cursor:pointer;font-size:0.85rem;">Edit</button>' +
              '<button class="btn-link" onclick="deleteReview(' + r.id + ')" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:0.85rem;">Delete</button>' +
          '</div>'
        : '';

    return '<div class="review-card" id="review-' + r.id + '">' +
        '<div class="reviewer-info" style="display:flex;align-items:center;gap:12px;">' +
            '<div class="avatar" style="background:var(--brand-green);color:white;display:flex;align-items:center;justify-content:center;font-weight:600;">' + initials + '</div>' +
            '<div><strong>' + escapeHTML(r.user_name) + '</strong><span class="date">' + r.date_label + '</span></div>' +
            ownerControls +
        '</div>' +
        '<div class="review-stars">' + starsHTML(r.rating, 14) + '</div>' +
        '<p class="review-body-text">' + escapeHTML(r.body) + '</p>' +
    '</div>';
}

function escapeHTML(s) {
    return String(s).replace(/[&<>"']/g, function(c){
        return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
}

function loadReviews(append) {
    Api.get('reviews/list.php?spot_id=' + encodeURIComponent(spot.id) +
            '&limit=' + reviewsState.limit + '&offset=' + reviewsState.offset)
        .then(function (data) {
            var container = document.getElementById('review-cards-container');
            var html = data.reviews.map(renderReviewCard).join('');

            if (append) {
                container.insertAdjacentHTML('beforeend', html);
            } else if (data.reviews.length === 0) {
                container.innerHTML = '<p style="text-align:center;color:var(--text-light);padding:32px;">No reviews yet. Be the first!</p>';
            } else {
                container.innerHTML = html;
            }

            reviewsState.total = data.total;
            reviewsState.offset += data.reviews.length;
            document.getElementById('load-more-wrapper').style.display = data.has_more ? 'block' : 'none';
        })
        .catch(function (err) {
            console.error('Failed to load reviews:', err);
            document.getElementById('review-cards-container').innerHTML =
                '<p style="text-align:center;color:#dc2626;padding:32px;">Could not load reviews. Please refresh.</p>';
        });
}

function setupReviewForm() {
    var session = localStorage.getItem('tara_session');
    if (session) {
        document.getElementById('review-form-card').style.display = 'block';
    } else {
        document.getElementById('review-login-prompt').style.display = 'block';
        return;
    }

    // Rating picker (click stars)
    var picker = document.getElementById('rating-picker');
    var hidden = document.getElementById('review-rating');
    function paint(val) {
        Array.prototype.forEach.call(picker.children, function (star, i) {
            star.style.color = (i < val) ? '#FFB400' : '#d1d5db';
        });
    }
    paint(5);
    picker.addEventListener('click', function (e) {
        if (e.target.dataset.val) {
            hidden.value = e.target.dataset.val;
            paint(parseInt(e.target.dataset.val, 10));
        }
    });

    // Submit
    document.getElementById('review-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var msgEl = document.getElementById('review-form-msg');
        var btn   = e.target.querySelector('button[type="submit"]');
        var payload = {
            spot_id: spot.id,
            rating:  parseInt(hidden.value, 10),
            body:    document.getElementById('review-body').value.trim()
        };

        btn.disabled = true;
        msgEl.textContent = 'Posting…';
        msgEl.style.color = '#6b7280';

        Api.post('reviews/create.php', payload)
            .then(function (data) {
                msgEl.textContent = '✓ Review posted!';
                msgEl.style.color = 'var(--brand-green)';
                document.getElementById('review-body').value = '';

                // Update visible rating + count without full reload
                setText('sidebar-rating',  data.new_rating);
                setText('sidebar-reviews', '(' + data.reviews_count + ' reviews)');
                document.getElementById('review-stars-row').innerHTML = starsHTML(data.new_rating, 16);

                // Hide form (user already reviewed; one-per-user rule)
                setTimeout(function () {
                    document.getElementById('review-form-card').style.display = 'none';
                }, 1500);

                // Refresh the reviews list with the new review at top
                reviewsState.offset = 0;
                loadReviews(false);
            })
            .catch(function (err) {
                msgEl.textContent = '✗ ' + err.message;
                msgEl.style.color = '#dc2626';
                btn.disabled = false;
            });
    });
}

// ── Edit review (in-place) ───────────────────────────────
function startEditReview(reviewId, currentRating, btnEl) {
    var card = document.getElementById('review-' + reviewId);
    if (!card || card.dataset.editing === '1') return;
    card.dataset.editing = '1';

    var bodyEl = card.querySelector('.review-body-text');
    var currentBody = bodyEl.textContent;

    // Replace body with editable controls; preserve original for cancel
    var editorHTML =
        '<div class="review-edit-form" style="margin-top:8px;">' +
            '<div style="display:flex;gap:4px;font-size:24px;color:#d1d5db;cursor:pointer;margin-bottom:8px;" id="edit-rating-picker-' + reviewId + '">' +
                [1,2,3,4,5].map(function(n){ return '<span data-val="' + n + '" style="color:' + (n <= currentRating ? '#FFB400' : '#d1d5db') + ';">★</span>'; }).join('') +
            '</div>' +
            '<input type="hidden" id="edit-rating-' + reviewId + '" value="' + currentRating + '">' +
            '<textarea id="edit-body-' + reviewId + '" class="form-control" rows="3" minlength="10" maxlength="1000" style="width:100%;">' + escapeHTML(currentBody) + '</textarea>' +
            '<div style="margin-top:8px;display:flex;gap:8px;">' +
                '<button class="btn-primary" style="padding:6px 16px;border-radius:8px;" onclick="submitEditReview(' + reviewId + ')">Save</button>' +
                '<button class="btn-outline" style="padding:6px 16px;border-radius:8px;background:white;cursor:pointer;" onclick="cancelEditReview(' + reviewId + ', this)" data-original-body="' + encodeURIComponent(currentBody) + '" data-original-rating="' + currentRating + '">Cancel</button>' +
            '</div>' +
        '</div>';

    bodyEl.style.display = 'none';
    bodyEl.insertAdjacentHTML('afterend', editorHTML);

    // Wire up the star picker
    var picker = document.getElementById('edit-rating-picker-' + reviewId);
    var hidden = document.getElementById('edit-rating-' + reviewId);
    picker.addEventListener('click', function (e) {
        if (e.target.dataset.val) {
            var val = parseInt(e.target.dataset.val, 10);
            hidden.value = val;
            Array.prototype.forEach.call(picker.children, function (star, i) {
                star.style.color = (i < val) ? '#FFB400' : '#d1d5db';
            });
        }
    });
}

function cancelEditReview(reviewId) {
    var card = document.getElementById('review-' + reviewId);
    if (!card) return;
    var editor = card.querySelector('.review-edit-form');
    if (editor) editor.remove();
    var bodyEl = card.querySelector('.review-body-text');
    if (bodyEl) bodyEl.style.display = '';
    card.dataset.editing = '';
}

function submitEditReview(reviewId) {
    var newRating = parseInt(document.getElementById('edit-rating-' + reviewId).value, 10);
    var newBody   = document.getElementById('edit-body-' + reviewId).value.trim();

    if (newBody.length < 10) {
        alert('Review must be at least 10 characters.');
        return;
    }

    Api.post('reviews/update.php', {
        review_id: reviewId,
        rating:    newRating,
        body:      newBody
    })
        .then(function (data) {
            // Update the sidebar rating in case the average changed
            setText('sidebar-rating',  data.new_rating);
            setText('sidebar-reviews', '(' + data.reviews_count + ' reviews)');
            document.getElementById('review-stars-row').innerHTML = starsHTML(data.new_rating, 16);

            // Refresh the list to reflect the edit
            reviewsState.offset = 0;
            loadReviews(false);
        })
        .catch(function (err) {
            alert('Could not save: ' + err.message);
        });
}

// ── Delete review ────────────────────────────────────────
function deleteReview(reviewId) {
    if (!confirm('Delete this review? This cannot be undone.')) return;

    Api.post('reviews/delete.php', { review_id: reviewId })
        .then(function (data) {
            // Update sidebar (deletion changes the average)
            setText('sidebar-rating',  data.new_rating);
            setText('sidebar-reviews', '(' + data.reviews_count + ' reviews)');
            document.getElementById('review-stars-row').innerHTML = starsHTML(data.new_rating, 16);

            // Show the review form again — user no longer has a review on this spot
            var formCard = document.getElementById('review-form-card');
            if (formCard) formCard.style.display = localStorage.getItem('tara_session') ? 'block' : 'none';

            // Refresh list
            reviewsState.offset = 0;
            loadReviews(false);
        })
        .catch(function (err) {
            alert('Could not delete: ' + err.message);
        });
}

// Load more button
document.getElementById('btn-load-more-reviews').addEventListener('click', function () {
    loadReviews(true);
});

// Kick off
loadReviews(false);
setupReviewForm();
```

> **Why a hidden input for the rating?** Default form behavior is preserved (clear, validate, submit) but the UI is a custom star widget. This is a clean pattern: present the stars visually, persist the chosen value in a hidden field that the JS reads on submit.
>
> **Why `payload` instead of `body` as the JS variable name?** The request field happens to be called `body` (matching the DB column). Using `payload` as the local JS variable name avoids shadowing.

### Step 3 — Confirm `<script src="js/api.js"></script>` is loaded

`details.html` must include `js/api.js` **before** `js/main.js`. This was added in Phase 04. If you skipped that, add it now:

```html
<script src="js/api.js"></script>
<script src="js/main.js"></script>
```

---

## Testing

### Test 1 — Anonymous viewer
1. Log out (delete `tara_session` from localStorage if needed).
2. Open `details.html?id=hundred-islands`.
3. **Expect:** "Reviews" section shows seeded reviews from DB. Login prompt visible above. No form.

### Test 2 — Post a review (happy path)
1. Log in via `login.html`.
2. Open `details.html?id=hundred-islands`.
3. **Expect:** Star picker + comment box visible.
4. Click 4th star, type "Great spot, would visit again!", click **Post Review**.
5. **Expect:** Success message. Sidebar rating updates. Form disappears after ~1.5s.
6. Refresh the page — your review appears at top of list.

### Test 3 — Duplicate prevention (UNIQUE constraint)
1. Same logged-in user, same spot.
2. **Expect:** Form does not reappear (one-review-per-spot rule).
3. (Dev only) Manually call `/api/reviews/create.php` again with same body via Postman → **expect HTTP 409 Conflict** with message *"You have already reviewed this spot."*

### Test 4 — Pagination
1. With > 10 reviews on a spot (seed extras if needed), open the page.
2. **Expect:** First 10 reviews render. "Load More" button visible.
3. Click it. **Expect:** 10 more append; button hides if no more remain.

### Test 5 — Validation
1. Try posting an empty review → blocked client-side (`required` + `minlength`).
2. Use Postman to POST with `body: "hi"` → **expect 400** *"Review must be at least 10 characters."*
3. Try with `rating: 0` or `rating: 6` → **expect 400** *"Invalid value."*

### Test 6 — Rating aggregate
1. Note current rating (e.g. 4.5, 12 reviews).
2. Post a 5-star review.
3. **Expect:** Rating recomputes (e.g. 4.5 → 4.6, count → 13).
4. Verify in MySQL: `SELECT rating, reviews_count FROM spots WHERE id = 'hundred-islands';` — matches displayed value.

### Test 7 — Update review (own)
1. After posting a review, find it in the list — confirm **Edit** and **Delete** buttons appear next to your name.
2. Click **Edit**. The card switches to an inline editor with the current rating + body.
3. Change rating to 3 and edit the text. Click **Save**.
4. **Expect:** Card returns to read-only with new values. Sidebar rating recomputes.
5. Verify in MySQL: `SELECT rating, body FROM reviews WHERE id = ?;` matches the new values.

### Test 8 — Update review (someone else's — should fail)
1. Note another user's review ID via DevTools or DB.
2. From Postman or the console, POST `{review_id: <other_id>, rating: 1, body: "tampered"}` to `update.php`.
3. **Expect:** 404 — *"Review not found."* (404, not 403, to avoid ID enumeration.)

### Test 9 — Delete review
1. Click **Delete** on your own review. Confirm the dialog.
2. **Expect:** Review disappears from the list. Sidebar rating drops accordingly. Review form re-appears (you no longer have a review on this spot).
3. Verify in MySQL: row is gone; aggregate recomputed.

### Test 10 — Delete last review on a spot
1. As the only reviewer of a spot, delete your review.
2. **Expect:** `spots.reviews_count = 0`, `spots.rating = 0.0` (handled via `COALESCE` in the recompute).

---

## File Structure After Phase 7

```
api/
├── auth/                 (Phase 04)
├── reviews/
│   ├── list.php          ✓ NEW (Read)
│   ├── create.php        ✓ NEW (Create)
│   ├── update.php        ✓ NEW (Update)
│   └── delete.php        ✓ NEW (Delete)
├── saved/                (Phase 06)
└── spots/                (Phase 05)
details.html              (review section replaced)
js/
└── main.js               (+ loadReviews, setupReviewForm, startEditReview, deleteReview)
```

---

## Done When

- [ ] Reviews on every spot's `details.html` are loaded from the DB, not hardcoded.
- [ ] Logged-in users see the review form; guests see a login prompt.
- [ ] **Create:** Posting a review updates `spots.rating` and `spots.reviews_count` via transaction.
- [ ] **Read:** Listing returns paginated reviews with `is_owner` flag on the requester's rows.
- [ ] **Update:** Users can edit their own review inline; aggregate rating recomputes.
- [ ] **Delete:** Users can delete their own review; aggregate rating recomputes (and goes to 0.0 if last review on a spot).
- [ ] Cross-user tampering (updating or deleting someone else's review) returns 404 to prevent ID enumeration.
- [ ] Same user cannot review the same spot twice (409 returned via UNIQUE constraint).
- [ ] "Load More" pagination works without page reload.
- [ ] Rating in the sidebar updates instantly after every mutating operation.

> **Rubric note.** This module is the single cleanest demonstration of all four CRUD operations on one resource. During the demo, point the panel at the Reviews section on `details.html` and walk them through Create → Read → Update → Delete in order. That's 15 rubric points addressed in roughly 90 seconds of demonstration.

Continue to **[`08-BOOKINGS-MODULE.md`](./08-BOOKINGS-MODULE.md)** to wire up the tour booking wizard.