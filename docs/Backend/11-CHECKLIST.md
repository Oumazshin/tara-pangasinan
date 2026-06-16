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
  INSERT INTO bookings (ref, user_id, tour_id, tour_date, tour_time, adults, children, infants, total, status, name, email, created_at)
  VALUES
    ('TPG-DEMO01', 1, 'hundred-islands', '2026-07-15', '8:00 AM', 2, 1, 0, 3600, 'upcoming', 'Demo User', 'demo@panel.test', NOW()),
    ('TPG-DEMO02', 1, 'patar-beach',     '2026-04-10', '1:00 PM', 2, 0, 0, 1600, 'completed', 'Demo User', 'demo@panel.test', '2026-03-20 12:00:00');
  ```
- [ ] Clear browser cache + localStorage before the demo. The site should behave the same in a clean browser.

---

## Demo Script (10–12 minutes)

This script is paced for a panel that wants to see the project work, not be lectured. Talk while you click — don't pause for the page to load mid-sentence.

### 0. Setup (before walking in)
Open three browser tabs in advance:
1. `http://localhost/tara-pangasinan/home.html` (the site)
2. `http://localhost/phpmyadmin` (selected on `tara_pangasinan` DB, showing the `users` table empty list)
3. DevTools docked at the bottom on tab 1, **Network** tab active

### 1. Introduction — 30 seconds
> "Good morning, panel. We're Group 5. Our project, **Tara Pangasinan**, is a tourism web application that helps travelers discover and book tours across Pangasinan. The frontend is HTML, CSS, JavaScript. The backend is PHP and MySQL. The two layers talk to each other through AJAX requests carrying JSON. We'll walk you through it page by page."

### 2. Homepage + Featured Spots — 1 minute
> *Click around home.html.*
>
> "When the homepage loads, JavaScript makes an AJAX request to `/api/spots/list.php`. That PHP script queries our `spots` table with two normalized JOINs — for activities and gallery images — and returns a JSON payload. We render the cards from that payload."
>
> *Show the Network tab → `list.php` → Response.*

### 3. Explore + Details — 1.5 minutes
> *Click Explore → click a card.*
>
> "On the details page, you see Visitor Reviews at the bottom. These are *real* reviews from our `reviews` table, joined against `users`. Watch what happens when I post a new one."
>
> *Log in (use prepared `demo@panel.test`). Open a spot. Type a review. Submit.*
>
> "Notice the star rating in the sidebar updated from 4.5 to 4.6 instantly — that's because the server recomputed the average inside a transaction and returned the new value in the response."
>
> *Open phpMyAdmin → `reviews` table → show the new row.*

### 4. Save / Unsave (AJAX Demo) — 1 minute
> *Hover over a spot card. Click the heart icon. Show the heart fill.*
>
> "Clicking the heart fires a POST to `/api/saved/toggle.php`. The UI updates *optimistically* — the heart fills before we even hear back from the server. If the request fails, we'd roll it back. This is the same pattern used by Twitter and Instagram."
>
> *Go to Saved page. Show the spot is there. Click heart again. Refresh. Spot is gone.*

### 5. Booking Wizard (the showpiece) — 3 minutes
> *Click Plan a Trip. Pick a tour.*
>
> "The booking wizard pulls available tours, add-ons, and time slots from `/api/spots/tours.php`. Watch the promo code — I'm typing TARA10."
>
> *Type code, click Apply.*
>
> "That just hit `/api/promos/validate.php`. The discount amount comes from the server, not the JavaScript. This matters because…"
>
> *Open DevTools → Sources → show `plan.html` JS.*
>
> "…there's no `PROMOS` object in the client code anymore. A user who reads our source can no longer discover hidden discount codes. This is a real security improvement over the localStorage version of this project."
>
> *Complete the wizard. Click Confirm.*
>
> "The booking is now in our `bookings` table with a `TPG-` reference. The total was recomputed server-side — even if a malicious user changed the `total` field in the request body, the server ignores it and uses the database's values."
>
> *Show the new row in phpMyAdmin.*

### 6. Profile + Booking History — 1 minute
> *Go to Profile.*
>
> "The profile page reads `/api/profile/get.php` to fill the form. Booking history reads `/api/bookings/list.php`."
>
> *Show the booking just made. Click Cancel.*
>
> "Cancel hits `/api/bookings/cancel.php`. The endpoint checks that the booking actually belongs to the logged-in user — if it didn't, it returns 404, not 403, to prevent leaking that other users' bookings exist."
>
> *Edit profile name. Save. Refresh. Confirm it persisted.*

### 7. Security & Architecture Talking Points — 1.5 minutes
> *Stay on profile.html. Open DevTools → Application → Cookies.*
>
> "Authentication uses PHP sessions. The session cookie is HttpOnly, SameSite=Lax, and the password is stored as a bcrypt hash, not plaintext. Login uses `password_verify`, which is constant-time — it doesn't leak the password through timing differences."
>
> *Open phpMyAdmin → users table → show `password_hash` column → click one cell.*
>
> "This is what we store. Nobody who breaches our database can read user passwords."
>
> *Switch to plan.html network tab in DevTools.*
>
> "Every endpoint uses prepared statements through PDO. No string concatenation anywhere — SQL injection is impossible by construction, even on inputs we don't validate. We tested this with classic payloads like `' OR '1'='1` — they get stored as literal strings, never executed."

### 8. Contact Form (closing the loop) — 30 seconds
> *Click Contact. Fill in a quick message. Submit.*
>
> "Contact uses a honeypot field to block dumb bots without needing CAPTCHA. The IP gets rate-limited to 5 messages an hour."
>
> *Show the row in `contact_messages`.*

### 9. Close — 30 seconds
> "Everything you saw is HTML, CSS, vanilla JavaScript with AJAX, PHP with PDO, MySQL with proper indexes, and JSON as the wire format — the exact tech stack the course covered. We avoided frameworks deliberately so each layer is auditable. Happy to answer any questions."

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

Before you zip the folder for submission:

- [ ] Final commit: `Initial release for defense — June 18, 2026`.
- [ ] `docs/` folder is included in the zip (this folder you're reading).
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

## You're Done.

You've built a real, working, end-to-end web application — frontend, backend, database, and the AJAX layer that ties them together. Every page does something. Every endpoint is secured. Every query is parameterized. Every form validates server-side. Every feature has tests.

This is a portfolio-quality project. Be proud of it during the defense.

— *Tara Pangasinan, Group 5 · BSCS · PUP CCIS · June 2026*
