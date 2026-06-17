# Tara Pangasinan

**A web application for promoting tourism in Pangasinan Province**

> Course Final Project · Group 5 · Web Development (COMP-20163)
> Polytechnic University of the Philippines · College of Computer and Information Sciences

---

## Group Members

| Member | Role |
|---|---|
| Bacolor, James Clark | Frontend Lead & UI/UX |
| Caole, Stephanie | UI/UX Designer |
| Guarin, Pauline | Documentation Lead |
| Soriano, Shouma King | Backend Lead |
| Lituco, Jessica Jhoanne | UI/UX Designer |

---

## What is Tara Pangasinan?

Pangasinan is one of the most-visited provinces in Luzon — home to Hundred Islands National Park, Patar Beach, Cape Bolinao Lighthouse, and Manaoag Church — but information about its destinations is scattered across blogs, social-media pages, and government microsites. **Tara Pangasinan** consolidates that information into a single, mobile-friendly platform where travelers can discover destinations, read reviews, save spots, and book tours end-to-end.

The system is built on the exact technology stack covered in this course: **HTML, CSS, vanilla JavaScript with AJAX, PHP with PDO, and MySQL** — no frameworks, no build step. Every layer is auditable.

The name *"Tara"* is Filipino for *"Let's go"* — reflecting the site's invitation for users to explore the Pearl of the North.

---

## Features

- **Destination catalog.** Eight curated spots (Hundred Islands, Patar Beach, Bolinao Falls, Enchanted Cave, Cape Bolinao, Lingayen Gulf, Manaoag Church, Bangus Festival) with descriptions, photo galleries, activities, visitor tips, and statistics.
- **Search, filter, and sort.** Find spots by category (Nature, Beach, Historical, Festival, Food), search by name or location, sort by popularity, rating, or name.
- **Interactive map view.** All destinations plotted on a Leaflet-powered map with OpenStreetMap base layer.
- **User accounts.** Registration and login with bcrypt-hashed passwords and PHP session management.
- **Reviews with full CRUD.** Logged-in users can post, edit, and delete their own reviews. Spot rating aggregates recompute automatically.
- **Saved spots.** Favorite destinations persist to the database; saved list follows the user across devices.
- **Multi-step booking wizard.** Six tour packages, three time slots, five optional add-ons. Promo codes validated server-side. Total recomputed server-side to prevent client tampering.
- **Booking management.** View, edit, and cancel upcoming bookings from the profile page.
- **Contact form.** Honeypot-protected, IP-rate-limited inquiry form.
- **Profile management.** Edit personal details and change password with current-password verification.
- **Responsive design.** Mobile-friendly layouts across all 12 pages.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Markup | HTML5 (semantic tags) |
| Styling | CSS3 (responsive, mobile-first) |
| Client logic | Vanilla JavaScript (ES5 + ES6) |
| AJAX | Fetch API via `js/api.js` |
| Server logic | PHP 8 (procedural, PSR-style) |
| Database | MySQL 5.7+ / MariaDB 10.3+ via XAMPP |
| Database driver | PDO with prepared statements |
| Map | Leaflet.js + OpenStreetMap |
| Wire format | JSON (request and response envelopes) |
| Sessions | Native PHP sessions (HttpOnly, SameSite=Lax) |
| Password storage | `password_hash()` (bcrypt) + `password_verify()` |

No JavaScript frameworks, no Composer, no `node_modules` — the project runs on a stock XAMPP install.

---

## Quick Start

### Prerequisites

- **XAMPP** (Apache + MySQL + PHP 8.0 or higher) — [apachefriends.org](https://www.apachefriends.org/)
- A modern web browser (Chrome, Firefox, Edge, or Safari)

### Installation

1. **Clone or extract** this project into your XAMPP `htdocs/` folder:
   ```
   xampp/htdocs/tara-pangasinan/
   ```
2. **Start XAMPP** Apache and MySQL services from the XAMPP Control Panel.
3. **Create the database.** Open `http://localhost/phpmyadmin` in your browser, then run:
   ```sql
   CREATE DATABASE tara_pangasinan
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```
4. **Import the schema.** In phpMyAdmin, select the `tara_pangasinan` database and import in this order:
   - `sql/schema.sql` — creates all 14 tables
   - `sql/seed-lookups.sql` — seeds tours, add-ons, and promo codes
   - `sql/seed-spots.sql` — seeds the 8 tourist destinations *(see note below)*
5. **Configure database credentials.** Open `config/database.php` and adjust `user` and `password` if your local MySQL isn't using the XAMPP defaults (`root` / empty password).
6. **Open the app** in your browser:
   ```
   http://localhost/tara-pangasinan/
   ```

> **Note on spot data:** A `sql/seed-spots.sql` (or equivalent `sql/seed.php`) must seed the eight destinations from `data/spots.json` into the database. Without it, the home, explore, details, and map pages will show "Failed to load destinations." See `docs/Backend/02-DATABASE.md` for the seeding strategy.

### Demo Credentials

For quick evaluation, after database setup you can either register a new account at `register.html` or use the seeded demo account:

| Email | Password |
|---|---|
| `demo@panel.test` | `panelpass1` |

*(Set up via the registration form on first run, or insert manually with `password_hash()`.)*

---

## Project Structure

```
tara-pangasinan/
├── api/                          PHP endpoints — all return JSON
│   ├── auth/                     register, login, logout, session
│   ├── bookings/                 create, list, cancel
│   ├── promos/                   validate
│   ├── reviews/                  list, create, update, delete  ← full CRUD
│   ├── saved/                    toggle, list
│   └── spots/                    list, get, tours
├── assets/                       Images and icons
│   ├── icons/                    Category icons, social icons, UI icons
│   └── images/                   Destination photography
├── config/
│   └── database.php              DB credentials (gitignored in production)
├── css/                          Stylesheets
├── data/
│   └── spots.json                Source data for seeding
├── docs/                         Documentation
│   ├── Backend/                  Implementation modules 00–12
│   ├── DESIGN_GUIDELINES.md      Visual design system reference
│   └── PROJECT_OVERVIEW.md       High-level project description
├── includes/                     PHP helpers (shared by every endpoint)
│   ├── auth.php                  require_login() middleware
│   ├── db.php                    PDO singleton
│   ├── response.php              json_success(), json_error(), read_json_body()
│   ├── session.php               Session bootstrap
│   └── validation.php            clean_str(), clean_int(), valid_email(), ...
├── js/
│   ├── api.js                    AJAX wrapper with custom ApiError class
│   ├── main.js                   Page-level controllers
│   └── update-reviews.js         Reviews-specific helpers
├── sql/
│   ├── schema.sql                14-table schema with FKs, indexes, constraints
│   └── seed-lookups.sql          Tours, add-ons, and promo codes
├── *.html                        12 pages (index, home, explore, details, ...)
├── .htaccess                     Apache configuration
└── README.md                     This file
```

---

## API Reference

All endpoints return JSON with a consistent envelope:

```json
// Success
{ "success": true, "data": { ... }, "message": "Optional human-readable note" }

// Error
{ "success": false, "error": "Human-readable error", "code": "machine_code", "details": null }
```

### Endpoints

| Method | Path | Auth | Purpose |
|---|---|---|---|
| `POST` | `/api/auth/register.php` | — | Create account |
| `POST` | `/api/auth/login.php` | — | Establish session |
| `POST` | `/api/auth/logout.php` | ✓ | Destroy session |
| `GET`  | `/api/auth/session.php` | — | Read current session |
| `GET`  | `/api/spots/list.php` | — | List destinations (filterable, sortable) |
| `GET`  | `/api/spots/get.php` | — | Get one destination by slug |
| `GET`  | `/api/spots/tours.php` | — | List tour packages + add-ons + time slots |
| `POST` | `/api/saved/toggle.php` | ✓ | Save or unsave a destination |
| `GET`  | `/api/saved/list.php` | ✓ | List current user's saved destinations |
| `GET`  | `/api/reviews/list.php` | — | List reviews for a spot |
| `POST` | `/api/reviews/create.php` | ✓ | Post a new review |
| `POST` | `/api/reviews/update.php` | ✓ | Edit current user's review |
| `POST` | `/api/reviews/delete.php` | ✓ | Delete current user's review |
| `POST` | `/api/promos/validate.php` | — | Validate a promo code |
| `POST` | `/api/bookings/create.php` | — | Create a booking (guest allowed) |
| `GET`  | `/api/bookings/list.php` | ✓ | List current user's bookings |
| `POST` | `/api/bookings/cancel.php` | ✓ | Cancel an upcoming booking |

All authenticated endpoints return HTTP 401 with `code: "auth_required"` when called without an active session.

---

## Security Notes

- **Password storage.** Plaintext passwords are never stored or logged. `password_hash()` with the default bcrypt algorithm hashes on registration; `password_verify()` performs a constant-time comparison on login.
- **SQL injection.** Every database call uses PDO prepared statements with parameter binding. No string concatenation of user input into SQL anywhere.
- **Cross-user enumeration.** Ownership-restricted endpoints (review update/delete, booking cancel) return HTTP 404 — not 403 — when the requesting user doesn't own the row. This prevents attackers from learning which IDs exist.
- **Server-side total enforcement.** The bookings module recomputes prices server-side using database values. Client-supplied `total` is ignored entirely.
- **Honeypot anti-spam.** The contact form includes a CSS-hidden input that bots fill but humans don't. IP-based rate limiting caps submissions at five per hour.
- **Session cookies.** Configured `HttpOnly` and `SameSite=Lax` to defend against XSS cookie theft and basic CSRF.

---

## Documentation

This repository ships with three documentation surfaces:

| Document | Purpose | Audience |
|---|---|---|
| `README.md` (this file) | Project orientation, setup, API overview | Anyone opening the repo |
| `docs/FRONTEND_DOCUMENTATION.md` | Frontend architecture, design system, and recent updates | Frontend developers |
| `docs/Backend/` | Implementation modules 00–12 (architecture → testing) | Developers building or auditing the system |
| `docs/Backend/12-DOCUMENTATION.md` | **Formal 13-section project documentation per the course rubric** | Course rubric evaluators |

The complete rubric-aligned documentation deliverable lives in **`docs/Backend/12-DOCUMENTATION.md`** with all 13 required sections (Title Page, Introduction, Objectives, Scope and Limitations, System Features, Use Case Diagram, ERD, Data Dictionary, Database Design, Interface Design, Testing Results, Conclusion, References).

---


## Acknowledgments

- **Course staff** of COMP-20163 Web Development at PUP CCIS for the project brief, rubric, and feedback.
- **Leaflet** — open-source JavaScript library for interactive maps.
- **OpenStreetMap** contributors for the base map tiles, used under the Open Database License (ODbL).
- **Unsplash** photographers whose images appear in the destination galleries, used under the Unsplash License.
- **The PHP Group** and **Oracle MySQL team** for the open-source server stack the project depends on.

---

## License & Academic Integrity

All source code in this repository is original work produced by Group 5 for the COMP-20163 Web Development course at the Polytechnic University of the Philippines. External libraries (Leaflet, OpenStreetMap tiles) and assets (Unsplash images) are used under their respective open-source or open-content licenses as noted above.

This project is submitted in partial fulfillment of the course requirements and is not intended for commercial use.

---

*Tara! Let's go explore Pangasinan.*