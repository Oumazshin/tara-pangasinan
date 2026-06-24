# 11 — Final Checklist, Demo Script & Defense Notes

> **Phase 11 of 11** · For: _Everyone, day-of-presentation_

This is the closing document. Use it the morning of June 18 to verify the project is presentation-ready, walk through the demo flow with the panel, and prep talking points for likely questions.

---

## Pre-Demo Checklist

Run through this list end-to-end the night before the presentation. Tick each box.

### Environment

- [ ] XAMPP control panel shows **Apache** and **MySQL** both running (green).
- [ ] phpMyAdmin opens at `http://localhost/phpmyadmin` and shows the `tara_pangasinan` database.
- [ ] All 14 tables exist: `users`, `spots`, `spot_activities`, `spot_gallery`, `spot_tips`, `spot_stats`, `saved_spots`, `reviews`, `tours`, `addons`, `promos`, `bookings`, `booking_addons`, `contact_messages`.
- [ ] `SELECT COUNT(*) FROM spots;` returns **8**.
- [ ] `SELECT COUNT(*) FROM tours;` returns **6**.
- [ ] `SELECT COUNT(*) FROM promos;` returns **3** (`TARA10`, `PANGASINAN`, `FIRSTTIME`).
- [ ] Site loads at `http://localhost/tara-pangasinan/`.

### Smoke Tests

- [ ] **Homepage** (`index.html` → redirects to `home.html`) — featured spots render.
- [ ] **Explore** — spot cards render; sort/filter/search work; heart icon toggles state.
- [ ] **Details** — pick any spot; reviews load from DB; rating + count match phpMyAdmin.
- [ ] **Map** — markers placed; clicking a marker opens info window.
- [ ] **Register** — new account `demo@panel.test` / `panelpass1` creates a row in `users` with hashed password.
- [ ] **Login** — same credentials log in; sidebar/profile area updates.
- [ ] **Saved** — heart 2–3 spots; `saved.html` lists them; logout/login preserves them.
- [ ] **Review** — post a review on a spot; rating in sidebar updates instantly.
- [ ] **Plan** — complete the wizard with promo `TARA10`; success modal shows `TPG-` ref.
- [ ] **Profile** — booking appears in history; edit profile + save; change password; log out + re-login with new password.
- [ ] **Contact** — submit a message; row appears in `contact_messages`.
- [ ] **Logout** — session ends; protected endpoints return 401.

### Code Sanity

- [ ] **No `console.error` output** in DevTools while clicking around all pages.
- [ ] **Network tab clean** — no 404s, no 500s on a normal flow.
- [ ] All 8+ pages include `<script src="js/api.js"></script>` **before** `<script src="js/main.js"></script>`.
- [ ] `data/spots.json` is no longer fetched by `main.js` — it now hits `/api/spots/list.php`.
- [ ] No remaining references to `tara_profile` or `tara_bookings` in the JS (search for them).
- [ ] `config/database.php` does **not** contain the production password in plain text. (For this demo: a comment explaining "in production, these would come from environment variables" is enough.)

### Demo Data Prep

- [ ] Seed at least **12 reviews** across various spots so the rating numbers look plausible (run `sql/seed.php` again or insert manually).
- [ ] Seed **2 example bookings** on the demo account so the profile page isn't empty:
  ```sql
  INSERT INTO bookings
      (reference, user_id, tour_id, tour_date, tour_time, adults, children, infants,
       total, status, contact_name, contact_email, contact_phone, booked_at)
  VALUES
      ('TPG-DEMO01', 1, 'hundred-islands', '2026-07-15', '8:00 AM', 2, 1, 0,
       3600, 'upcoming',  'Demo User', 'demo@panel.test', '+63 917 000 0000', NOW()),
      ('TPG-DEMO02', 1, 'patar-beach',     '2026-04-10', '1:00 PM', 2, 0, 0,
       1600, 'completed', 'Demo User', 'demo@panel.test', '+63 917 000 0000', '2026-03-20 12:00:00');
  ```
- [ ] Clear browser cache + localStorage before the demo. The site should behave the same in a clean browser.

---

## Demo Script (10–12 minutes)

This script is structured around the **five presentation topics required by the official rubric**: (1) Problem & solution overview, (2) Live demonstration, (3) Key features & workflows, (4) Technical challenges & solutions, (5) Lessons learned. Topics 2 and 3 are interleaved during the walkthrough — you demonstrate features while you demonstrate the app. Talk while you click; don't pause mid-sentence waiting for pages to load.

### 0. Setup (before walking in)
Open three browser tabs in advance:
1. `http://localhost/tara-pangasinan/home.html` (the site)
2. `http://localhost/phpmyadmin` (selected on `tara_pangasinan` DB, showing the `users` table)
3. DevTools docked at the bottom on tab 1, **Network** tab active

---

### TOPIC 1 — Problem & Proposed Solution — 1.5 minutes

> "Good morning, panel. We're Group 5. Pangasinan attracts over a million tourists a year — Hundred Islands, Patar Beach, Manaoag Church — but information about these destinations is fragmented across social-media pages, blogs, and government microsites. There's no single place to compare options, read genuine reviews, and book a tour in one flow.
>
> **Tara Pangasinan** is our proposed solution: a single, mobile-friendly web application that consolidates destination information, lets travelers read and contribute reviews, and enables direct tour booking. It's built entirely on the course's required stack — HTML, CSS, vanilla JavaScript with AJAX, PHP with PDO, and MySQL — with no front-end frameworks. Every line of code in our project maps to something we covered in class.
>
> We'll spend the next ten minutes walking you through the running system, calling out the key features, then close with the technical challenges we hit and what we learned."

---

### TOPIC 2 + 3 — Live Demonstration & Key Features — 7 to 8 minutes

> _The demonstration order shows the panel **two** clean CRUD walkthroughs: Reviews on a simple resource (2b), then Bookings on a complex resource (2d). Mention this explicitly — it's worth pointing to both._

#### 2a. Homepage + Catalog (1 min)
> *Click around `home.html`. Open DevTools Network tab.*
>
> "The homepage fetches featured spots from `/api/spots/list.php`. That endpoint runs a single MySQL query joined to four child tables — `spot_activities`, `spot_gallery`, `spot_tips`, `spot_stats` — and returns the result as JSON. No matter how many spots we add, this stays at exactly five queries thanks to `IN`-clause batching."
>
> *Click Explore. Show filter + sort working.*

#### 2b. Details Page + Full CRUD on Reviews (2.5 min — **rubric showcase**)
> *Click a spot card.*
>
> "I'll log in as our demo user, then I'll walk you through all four CRUD operations on the Reviews resource. This is where the rubric's CRUD points are most clearly visible."
>
> *Log in. Return to the spot.*
>
> **CREATE.** *Click 5 stars. Type a review. Submit.*
> "POST to `/api/reviews/create.php`. The handler verifies the user is logged in, validates the rating between 1 and 5, the body between 10 and 1000 characters, then inserts the review and recomputes the spot's average rating inside the same transaction. Notice the sidebar rating just updated."
>
> **READ.** *Scroll up so the new review is visible at the top of the list.*
> "Reading is `/api/reviews/list.php`. It joins against `users` so we always show the current display name, never a stale snapshot."
>
> **UPDATE.** *Click Edit on your own review. Change the rating and text. Save.*
> "Update is `/api/reviews/update.php`. The handler checks the review belongs to the requesting user — otherwise we'd return 404, not 403, to prevent ID enumeration. Aggregate rating recomputes again."
>
> **DELETE.** *Click Delete. Confirm.*
> "And delete. The review is gone, the aggregate recomputes — including the edge case where the spot ends up with zero reviews and we fall back to 0.0."
>
> "That's Create, Read, Update, and Delete on a single resource, with transactional aggregate consistency in every mutating call. Each operation enforces ownership at the server, not the client."

#### 2c. Save / Unsave (45 seconds)
> *Browse to a card. Click the heart.*
>
> "The heart filled before the network request finished — that's optimistic UI. The POST hits `/api/saved/toggle.php`. Logged-out visitors still get the experience through localStorage; the database becomes the source of truth once they log in."
>
> *Refresh. Click heart again to unsave.*

#### 2d. Booking Wizard + Server-Side Total Enforcement + Full CRUD (3 min)
> *Click Plan a Trip. Pick a tour.*
>
> "The wizard pulls tours, add-ons, and time slots from `/api/spots/tours.php`. Watch what happens with promo codes."
>
> *Type TARA10, click Apply.*
>
> "That call hits `/api/promos/validate.php`. The discount value comes from the server, not the client."
>
> *Open DevTools → Sources. Show plan.html JS.*
>
> "There's no `PROMOS` array in the client code anymore. A user reading our source can't enumerate hidden discount codes. They have to *try* each code and observe the server response."
>
> *Complete the wizard. Click Confirm.*
>
> "**That was CREATE.** The booking is now in our `bookings` table with a `TPG-` reference generated server-side. The total was recomputed on the server using DB prices — even if I had edited the request to send `total: 1`, the server would have ignored it."
>
> *Navigate to Profile, point at the new card.*
>
> "**That's READ.** Profile fetches `/api/bookings/list.php` with a JOIN against tours and a separate query for addons — 2 queries regardless of how many bookings the user has."
>
> *Click Edit on the booking. Modal opens.*
>
> "**This is UPDATE.** I can change the date, guest count, addons, or apply a promo I forgot the first time. Notice the tour name at the top — that field is locked. Changing the tour would effectively be a new booking, so the endpoint refuses to read `tour_id` from the client payload."
>
> *Change guest count, add an addon, click Save Changes.*
>
> "Server recomputes the total — same logic as Create. The addons table gets wiped and rewritten inside a single transaction, so we're never in a half-updated state."
>
> *Click Cancel on the same card. Confirm.*
>
> "Cancellation is a soft-update: status flips, `cancelled_at` gets a timestamp, the row stays."
>
> *Click Remove from history. Confirm.*
>
> "**And that's DELETE.** Hard-delete is restricted to cancelled rows only — upcoming and completed bookings can't be removed because they're audit records. The `booking_addons` rows clean themselves up via `ON DELETE CASCADE`."
>
> *Show phpMyAdmin — row is gone.*
>
> "Same CRUD lifecycle as Reviews, but on a more complex resource with business rules, server-side pricing, and audit-trail protection."

#### 2e. Profile Edit (30 seconds)
> *Stay on Profile. Edit your bio or city. Save.*
>
> "Profile uses `/api/profile/get.php` and `/api/profile/update.php`. Password changes require the current password — verified with `password_verify()`, which is constant-time, so we can't leak the password through timing differences."

#### 2f. Contact Form (30 seconds)
> *Click Contact. Fill a quick message. Submit.*
>
> "Contact uses a honeypot field — hidden off-screen at left:-9999px — to block naive bots without a CAPTCHA. We also rate-limit submissions to five per hour per IP using the `idx_ip_submitted` index. Submissions land in the `contact_messages` table for admin review."
>
> *Show the row in phpMyAdmin.*

---

### TOPIC 4 — Technical Challenges & Solutions — 1.5 minutes

> "We hit three substantive technical challenges during development.
>
> **First, schema column drift.** Eight days into the project, we discovered our implementation modules had drifted from the canonical schema — `firstname` instead of `first_name`, `ref` instead of `reference`. Rather than patch around it, we did an audit pass, settled on snake_case as the canon, and refactored every PHP endpoint and JS file to match. The lesson here is to define your schema first and then enforce it everywhere — drift is silent until it explodes at integration time.
>
> **Second, the localStorage-to-session migration.** Profile data, saved spots, and bookings were all originally in localStorage. Moving to a database meant rethinking authentication state — when is the session the source of truth, when is localStorage just a UI cache? Our resolution was that `localStorage.tara_session` is a UI mirror written by `/api/auth/session.php`, never trusted, and the actual auth check always runs server-side via `require_login()`.
>
> **Third, server-side total enforcement on bookings.** The original client-side `submitBooking()` computed the total in JavaScript. We rewrote it so the server independently recomputes prices using the database, ignoring whatever the client sends as `total`. This defends against a class of price-manipulation attacks that hardcoded-JavaScript implementations cannot prevent. It also added complexity — the client now displays one number and trusts the server's confirmation, which required a round-trip in the success modal."

---

### TOPIC 5 — Lessons Learned — 45 seconds

> "Three things we'll carry forward.
>
> One — **normalization pays for itself**. Activities, gallery images, tips, and stats all started as JSON columns. Moving them to separate tables with foreign keys took an afternoon and gave us individually editable rows, ordered retrieval, and referential integrity — for the same query cost.
>
> Two — **prepared statements aren't optional**. Once we built the habit early, SQL injection became impossible by construction. There's not a single instance of string concatenation into SQL anywhere in our codebase. You can grep for it.
>
> Three — **defense in depth is real**. Email uniqueness is checked in PHP *and* by a UNIQUE constraint. Review ownership is checked in PHP *and* by a UNIQUE composite key. The honeypot is one of several anti-spam layers. When one layer fails, the others hold.
>
> Everything you saw is HTML, CSS, vanilla JavaScript with AJAX, PHP with PDO, MySQL with proper indexes, and JSON over the wire — the exact stack this course covered. We're happy to answer any questions."

---

## Likely Panel Questions & Prepared Answers

**Q: Why didn't you use a framework like React or Laravel?**
> The project brief specified plain HTML, JavaScript, PHP, and MySQL. We wanted every line of code to map directly to course concepts — there's no `node_modules`, no Composer, no build step. Anyone in this room can read our entire codebase top to bottom in an afternoon.

**Q: What happens if two users try to register with the same email at the same time?**
> The `users.email` column has a `UNIQUE` constraint. MySQL serializes the writes — one succeeds, the other gets a duplicate-key error, which our PHP catches and returns as a friendly "Email already registered" message. We don't need our own locking logic.

**Q: How do you prevent SQL injection?**
> Every database call uses PDO prepared statements with parameter binding. There is zero string concatenation of user input into SQL anywhere in the codebase. You can grep for it: `grep -rn '\$pdo->query' api/` returns only queries with no user input.

**Q: How are passwords stored?**
> `password_hash($pw, PASSWORD_DEFAULT)` on registration. That uses bcrypt with a per-user salt and a cost factor PHP picks based on hardware. Verification uses `password_verify()`, which is constant-time. The plaintext password never touches the database.

**Q: Can a malicious user manipulate the booking total?**
> No. The booking endpoint ignores any `total` the client sends. It looks up the tour and add-on prices from the database, applies the promo (also validated against the DB), and computes the total server-side. The client's number is just for display.

**Q: What's the slowest query in your app?**
> Probably the spots list, which does four sub-queries (activities, gallery, tips, stats) using `IN (?, ?, ?, ...)`. We use `IN` to avoid the N+1 query problem — fetching the spot list and then looping to fetch each spot's relations would be 8 + 8×4 = 40 queries. Our approach is exactly 5 queries regardless of how many spots there are.

**Q: How does your app behave without JavaScript?**
> The pages still render — the HTML and CSS work — but interactivity stops. We made a deliberate trade-off: a richer, AJAX-driven UX rather than progressive enhancement. If we needed accessibility-first, we'd add form-action fallbacks to every form. For a tourism site for desktop and mobile browsers in 2026, the trade-off is reasonable.

**Q: How would you scale this to thousands of users?**
> Three things would change:
> 1. Move the database to a managed service (RDS, PlanetScale).
> 2. Add an LRU cache (Redis or Memcached) in front of `spots/list.php` — those records change rarely and are read constantly.
> 3. Replace PHP sessions with stateless JWTs so we can run multiple app servers behind a load balancer.
>
> The schema and the application logic would not need to change.

**Q: Why didn't you implement password reset?**
> Password reset requires sending email, which means SMTP or a third-party email service — both out of scope for this course. We considered using `mail()` against `localhost`, but most ISPs reject that, so it would have worked on the demo machine and broken in any realistic deployment. We'd add it as a follow-up using PHPMailer + an SMTP relay.

**Q: How do you handle file uploads (e.g., profile pictures)?**
> We don't, currently. Avatars are derived from initials on the client. Real file uploads would need: a typed-content check (`finfo_file()`), a size limit (`upload_max_filesize` in php.ini), and a separate folder outside the web root for storage with a slug-based filename. We scoped it out for time.

**Q: Why VARCHAR primary keys for spots? Isn't INT faster?**
> Slightly faster, yes, but our URLs already use slugs — `details.html?id=hundred-islands` is more user-friendly and SEO-friendly than `?id=42`. The performance difference at our scale (8 spots) is rounding-error. If we had a million spots, we'd add an INT surrogate primary key and keep the slug as a UNIQUE column.

---

## Submission Checklist

The official Submission Guidelines require **three deliverables**: source code, compiled/deployment link if applicable, and complete documentation. Make sure all three are in the bundle before you zip.

Before you zip the folder for submission:

- [ ] Final commit: `Initial release for defense — June 18, 2026`.
- [ ] **Documentation deliverable.** Export `12-DOCUMENTATION.md` to PDF (or DOCX). All `[PLACEHOLDER]` values filled in. Use Case Diagram and ERD drawn as images. At least one screenshot per page in § 10.2. All Testing Results in § 11 marked PASS/FAIL based on real testing. **(See `12-DOCUMENTATION.md` for the template.)**
- [ ] `docs/` folder is included in the zip (this folder you're reading, plus the exported PDF/DOCX).
- [ ] `sql/schema.sql` exports cleanly. Re-import it on a blank database to confirm.
- [ ] `sql/seed.php` runs without errors against a fresh, schema-only database.
- [ ] `README.md` at the project root has setup instructions for anyone who clones the repo.
- [ ] No `.DS_Store`, `Thumbs.db`, `node_modules/`, or IDE config files (`.vscode/`, `.idea/`) in the zip.
- [ ] Zip name: `Group5_Tara_Pangasinan_BSCS-WebDev_Final.zip` (or whatever your instructor specified).
- [ ] Email a backup of the zip to **every member's** Google Drive the night before. Disk failure on demo morning is a real risk.

---

## Tech Stack Coverage — Cross-Check

The course rubric likely requires you to demonstrate each item. Use this matrix during the demo if asked to "point to where you use X":

| Required | Where it lives | What to point to |
|---|---|---|
| **HTML** | All pages | Semantic markup, forms, `<section>`, `<article>` |
| **CSS** | `css/global/`, `css/pages/` | Brand colors, grid layouts, responsive breakpoints |
| **JavaScript** | `js/main.js`, `js/api.js`, page-level scripts | Event handling, DOM manipulation, ES5 + ES6 mixed for clarity |
| **PHP** | `api/**`, `includes/**`, `config/**` | Server-side logic, OOP-style PDO use, hashed passwords |
| **DBMS (MySQL)** | `sql/schema.sql`, phpMyAdmin | 14 normalized tables, FKs, indexes, ENUMs, CHECK constraints |
| **AJAX** | `js/api.js`, every page that calls `Api.get` / `Api.post` | `fetch()` with `Content-Type: application/json`, async/await |
| **JSON** | Every API response, every request body | Universal interchange between PHP and JS |

If the panel asks "where's AJAX?" — open DevTools, Network tab, filter to XHR/Fetch. Every action makes one.

---

## Post-Demo

After the panel finishes:

- [ ] Save the panel's questions and your group's answers in a shared doc. Future students will thank you.
- [ ] Stop XAMPP. Disable autostart so it doesn't slow your laptop down later.
- [ ] Take a screenshot of the working app, the database schema, and the file tree — for your resume / portfolio.
- [ ] Eat something. You earned it.

---

## Rubric Self-Assessment

Before submitting, walk through the official 100-point rubric and score yourselves honestly. If a row is below "Excellent (100%)", note what's missing and decide whether to fix it now or accept the points lost.

| # | Criterion | Pts | Self-score | Evidence |
|---|---|---|---|---|
| 1 | UI/UX (responsive, professional, easy navigation) | 20 | _/20 | Existing frontend; test responsive design mode on Chrome at 375, 768, 1440 px |
| 2 | Functionality & Features (all required features work) | 25 | _/25 | All 12 pages load; all 8 modules pass the test plan |
| 3 | Database Design & Integration (normalization, FKs) | 15 | _/15 | 14 tables in 3NF with FKs, ENUMs, UNIQUE, CHECK; see `02-DATABASE.md` + `12-DOCUMENTATION.md` § 9 |
| 4 | CRUD Operations (all four work) | 15 | _/15 | Reviews resource demonstrates C/R/U/D explicitly; see `07-REVIEWS-MODULE.md` |
| 5 | Coding Standards (organized, readable, comments) | 10 | _/10 | PSR-style PHP; consistent snake_case; comments on every endpoint |
| 6 | Documentation (complete, professional, diagrams) | 10 | _/10 | `12-DOCUMENTATION.md` has all 13 required sections; diagrams drawn |
| 7 | Presentation & Demonstration (clear, confident) | 5 | _/5 | This document's demo script aligns to the rubric's 5 required topics |
| | **TOTAL (target: 96+ Outstanding)** | **100** | _**/100** | |

### Common Point-Losers — Avoid These

- **No Use Case Diagram drawn** → instant -2 to -5 from Documentation
- **No ERD as a visual** → instant -2 to -5 from Documentation
- **Testing Results table left empty / mostly `[PASS/FAIL]` placeholders** → -3 to -5 from Documentation
- **Demo glitches** (XAMPP not running, DB not seeded, console errors) → -5 to -10 from Presentation **and** Functionality
- **Update or Delete review broken** → -5 from CRUD (this is why we test it specifically)
- **Edit Booking modal silently broken** → -5 from CRUD (test it the day before; modal state is the trickiest part of the project)
- **Inline `style="..."` everywhere instead of CSS** → -1 to -3 from Coding Standards
- **Missing comments on PHP files** → -1 from Coding Standards

---

Continue to **[`12-DOCUMENTATION.md`](./12-DOCUMENTATION.md)** for the formal 13-section documentation deliverable required by the rubric.

---

## You're Done.

You've built a real, working, end-to-end web application — frontend, backend, database, and the AJAX layer that ties them together. Every page does something. Every endpoint is secured. Every query is parameterized. Every form validates server-side. Every feature has tests. The formal documentation is complete.

This is a portfolio-quality project. Be proud of it during the defense.

— *Tara Pangasinan, Group 5 · BSCS · PUP CCIS · June 2026*