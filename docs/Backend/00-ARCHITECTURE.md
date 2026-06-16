# 00 — Architecture Overview

Before writing any PHP, take 10 minutes to understand the **system shape**. Every module you build later follows the same pattern. If you understand this page, the rest is mostly typing.

---

## 1. The Big Picture

```
┌───────────────────────────────────────────────────────────────┐
│                         BROWSER (Client)                       │
│                                                               │
│   *.html  ──renders──>  CSS + main.js + page <script> blocks  │
│                                  │                            │
│                          ┌───────┴───────┐                    │
│                          │   js/api.js   │  (AJAX wrapper)    │
│                          └───────┬───────┘                    │
└──────────────────────────────────┼────────────────────────────┘
                                   │  Fetch(JSON)
                                   ▼
┌───────────────────────────────────────────────────────────────┐
│                      APACHE / XAMPP (Server)                   │
│                                                               │
│   /api/<module>/<action>.php                                  │
│        │                                                      │
│        ├── includes/session.php   (start PHP session)         │
│        ├── includes/auth.php      (require login if needed)   │
│        ├── includes/db.php        (PDO connection)            │
│        ├── includes/validation.php (sanitize input)           │
│        └── includes/response.php  (json_success / json_error) │
│        │                                                      │
│        ▼                                                      │
│   ┌─────────────┐    SQL via PDO    ┌──────────────────┐      │
│   │ Endpoint    │ ─────────────────> │   MySQL Server   │      │
│   │ Logic       │ <───────────────── │  tara_pangasinan │      │
│   └─────────────┘    Result set     └──────────────────┘      │
└───────────────────────────────────────────────────────────────┘
```

**Read it left-to-right:** a button click in the browser → JavaScript builds a Fetch request → Apache routes to a PHP file → PHP queries MySQL → PHP echoes JSON → JavaScript updates the DOM.

This is *the same flow for every feature in the site*. Once you have one module working, every other module is a variation of the same pattern.

---

## 2. Request Lifecycle (Worked Example)

Let's trace one click — a user saving "Hundred Islands" from the explore page:

| # | Layer | What happens |
|---|---|---|
| 1 | HTML | User clicks the heart button (`<button data-save-id="hundred-islands" onclick="toggleCardSave(...)">`) |
| 2 | JS (`js/main.js`) | `toggleCardSave('hundred-islands')` fires |
| 3 | JS (`js/api.js`) | Calls `Api.post('saved/toggle.php', { spot_id: 'hundred-islands' })` |
| 4 | Network | Browser sends `POST /api/saved/toggle.php` with JSON body and session cookie |
| 5 | PHP (`includes/session.php`) | Resumes session, identifies the user |
| 6 | PHP (`includes/auth.php`) | Confirms `$_SESSION['user_id']` exists; aborts with 401 if not |
| 7 | PHP (`api/saved/toggle.php`) | Validates `spot_id`, runs `INSERT … ON DUPLICATE KEY UPDATE` (or `DELETE` if already saved) |
| 8 | MySQL | Saves the row in `saved_spots` |
| 9 | PHP | Echoes `{"success": true, "data": {"saved": true}, "message": "Saved to your list!"}` |
| 10 | JS | Receives JSON, updates the heart fill colour, shows a toast |

That's the whole loop. Repeated for **every** backend action.

---

## 3. Layered Responsibilities (a.k.a. Why We Use `includes/`)

Don't put database connection code in every endpoint. Don't repeat JSON formatting. Use shared helpers:

| Shared file | Responsibility | Used by |
|---|---|---|
| `config/database.php` | Hostname, DB name, user, password | `includes/db.php` only |
| `includes/db.php` | Creates a single PDO instance | Every endpoint that touches the DB |
| `includes/session.php` | `session_start()` + standard cookie params | Every endpoint |
| `includes/auth.php` | `require_login()` — aborts with 401 if no session | Endpoints needing login (saved, bookings, profile) |
| `includes/response.php` | `json_success($data)`, `json_error($msg, $code)` | Every endpoint |
| `includes/validation.php` | `clean_str()`, `valid_email()`, `valid_id_slug()` | Endpoints that accept user input |

This separation means **each endpoint file is ~30 lines** — easy to read, easy to test, easy to grade.

---

## 4. State Management Strategy

The current frontend uses `localStorage` for everything. We're moving to a **dual-source** model:

| State | Before | After |
|---|---|---|
| `tara_session` | `localStorage` flag set on form submit | Set by the **server** session; mirrored to `localStorage` by `/api/auth/session.php` for UI hints (e.g. profile icon link) |
| `tara_saved` | Array of spot IDs in `localStorage` | Stored in `saved_spots` table when logged in; falls back to `localStorage` for guests |
| `tara_bookings` | Array of booking objects | Stored in `bookings` + `booking_addons` tables; `localStorage` cleared on logout |
| `tara_profile` | Profile object in `localStorage` | Stored in `users` table; not duplicated in `localStorage` |

**Why fall back to `localStorage` for saved spots when logged-out?** It preserves the existing "save without account" UX. When the user later logs in, you can optionally merge their `localStorage` saves into the database — covered in `06-SAVED-MODULE.md`.

---

## 5. Tech Stack Mapping (For Your Defense / Presentation)

When the panel asks *"where did you use AJAX?"*, you can point at:

| Requirement | Concrete File(s) | Demonstration |
|---|---|---|
| **HTML** | All `.html` files in project root | Open any page |
| **CSS** | `css/global/*.css`, `css/pages/**/*.css` | Inspect element, show stylesheet links |
| **JavaScript** | `js/main.js`, `js/api.js`, page-level scripts | Show console logging API calls |
| **PHP** | `api/**/*.php` (12+ endpoints) | Open `api/spots/list.php` in editor |
| **DBMS** | MySQL — `tara_pangasinan` database | Open `phpMyAdmin`, show 8 tables |
| **AJAX** | `js/api.js` — `Api.get()` / `Api.post()` | Browser DevTools → Network tab → filter "Fetch" |
| **JSON** | Every API response | Network tab → click any `api/*.php` → see JSON body |

**One screen, one explanation.** Open DevTools → Network → click "Save" on a spot → show: (1) the JS that made the Fetch call (AJAX), (2) the JSON request body, (3) the PHP file in the URL (PHP), (4) the JSON response, (5) phpMyAdmin showing the new row (DBMS). That single click demonstrates 4 of the 6 tech stack items.

---

## 6. What's Out of Scope (Deliberately)

To stay focused on the rubric, the following are **intentionally not** in this plan:

- **REST verbs (PUT/DELETE/PATCH).** All mutations use `POST` — simpler, equally valid, and your AJAX layer stays consistent.
- **A JS framework.** Vanilla JS is part of the rubric. No React, no Vue.
- **Composer / PHP dependencies.** Native PHP only — `password_hash`, PDO, `json_encode` are all built-in.
- **A build step.** No webpack, no SCSS compile. Plain files, served directly.
- **External APIs.** Weather widget, maps, etc. stay as they are (Leaflet/OSM).
- **Email sending.** Booking confirmations are *logged to the database* but not emailed (covered in `08-BOOKINGS-MODULE.md`'s "Future Enhancements" section).

---

## 7. Glossary

| Term | Definition (in the context of this project) |
|---|---|
| **Endpoint** | A single `.php` file under `/api/` that handles one action |
| **Envelope** | The `{success, data, error}` shape every response uses |
| **Middleware** | A `require_*()` function called at the top of an endpoint to enforce a precondition (e.g. login) |
| **PDO** | PHP Data Objects — the safe way to talk to MySQL using prepared statements |
| **Prepared Statement** | A SQL query with `?` placeholders that prevents SQL injection |
| **Session** | Server-side memory tied to a browser cookie (`PHPSESSID`) |
| **Seeding** | Populating the database with initial data (here, copying `spots.json` into the `spots` table) |

---

Continue to **[`01-SETUP.md`](./01-SETUP.md)** to install XAMPP and prepare the project folder.
