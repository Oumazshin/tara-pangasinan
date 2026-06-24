# Tara Pangasinan — Backend Integration Plan

This folder is the **master roadmap** for converting the Tara Pangasinan frontend (currently powered by `localStorage` + a static `spots.json`) into a full-stack web application using **HTML, CSS, JavaScript, PHP, MySQL, AJAX, and JSON**.

The plan is split into **modular phases**. Each phase produces working, demonstrable functionality on its own — so even if you run out of time on Day 4, every phase you finish is presentation-ready.

---

## Official Project Rubric (100 points)

This plan is aligned to the **PUP CCIS Web Development Project Rubric** released for COMP-20163. The rubric awards points across seven criteria:

| # | Criterion | Pts | Where addressed in this plan |
|---|---|---|---|
| 1 | UI/UX (responsive, consistent, easy navigation) | 20 | Existing frontend; no backend impact |
| 2 | Functionality & Features | 25 | All 11 module phases (`04` – `10`) |
| 3 | Database Design & Integration | 15 | Phase `02` (14 normalized tables) + Phase `03` (PDO) |
| 4 | CRUD Operations | 15 | Phase `07` Reviews and Phase `08` Bookings both demonstrate full Create/Read/Update/Delete — Reviews on a simple user-content resource, Bookings on a complex multi-table resource with business rules |
| 5 | Coding Standards & Best Practices | 10 | Prepared statements throughout; conventions in Phase `03` and `README` |
| 6 | Documentation | 10 | Phase `12` (formal 13-section deliverable) |
| 7 | Presentation & Demonstration | 5 | Phase `11` (demo script aligned to rubric topics) |
| | **TOTAL** | **100** | |

> **Grade interpretation:** 96–100 Outstanding · 90–95 Excellent · 85–89 Very Good · 80–84 Good · 75–79 Satisfactory · Below 75 Needs Improvement

---

## Course Tech Stack Coverage

| Requirement | Where it lives | Status after backend |
|---|---|---|
| **HTML** | All `*.html` pages | ✓ Already done |
| **CSS** | `css/global/` + `css/pages/` | ✓ Already done |
| **JavaScript** | `js/main.js` + inline `<script>` blocks | ✓ Extended with AJAX layer |
| **PHP + DBMS** | New `api/` folder + MySQL via XAMPP | ✓ Built in Phases 3–10 |
| **AJAX (Fetch API)** | New `js/api.js` wrapping all server calls | ✓ Built in Phase 3 |
| **JSON** | All API endpoints return `application/json` | ✓ Built in Phase 3 |

> Every PHP endpoint receives requests via AJAX, queries MySQL, and returns JSON. This single pattern satisfies **PHP + DBMS + AJAX + JSON** simultaneously — repeated across multiple modules.

---

## Suggested 5-Day Sprint (Deadline: June 18, 2026)

| Day | Phases | What you can demo at end of day |
|---|---|---|
| **Day 1** | 01 Setup → 02 Database → 03 Core | XAMPP running, DB seeded, API responds with JSON |
| **Day 2** | 04 Auth → 05 Spots | Users can register/login; spots load from DB |
| **Day 3** | 07 Reviews → 09 Contact → 08 Bookings | Reviews persist with full CRUD; contact form works; bookings save to DB |
| **Day 4** | 06 Saved → 10 Profile → 11 Checklist | Full localStorage → DB migration; polish for demo |
| **Day 5** | 12 Documentation + dress rehearsal | Formal 13-section document complete; demo rehearsed |

If time is tight, **the minimum viable backend** for the rubric is: **01 + 02 + 03 + 04 + 05 + 07 + 09** (this still hits CRUD via Reviews, plus Auth, Spots, and Contact). **Phase 12 is mandatory** — without the formal documentation deliverable, the project loses 10 points automatically.

---

## File Index

| File | Topic | Priority |
|---|---|---|
| [`00-ARCHITECTURE.md`](./00-ARCHITECTURE.md) | High-level system design, request flow, folder layout | Read first |
| [`01-SETUP.md`](./01-SETUP.md) | XAMPP installation, project re-mount, .htaccess, env config | Day 1 |
| [`02-DATABASE.md`](./02-DATABASE.md) | Full MySQL schema (14 tables) + seed from `spots.json` | Day 1 |
| [`03-CORE-PHP.md`](./03-CORE-PHP.md) | PDO connection, JSON response helpers, session, auth middleware, `js/api.js` | Day 1 |
| [`04-AUTH-MODULE.md`](./04-AUTH-MODULE.md) | Register, Login, Logout, Session check + form wire-up | Day 2 |
| [`05-SPOTS-MODULE.md`](./05-SPOTS-MODULE.md) | Replace `data/spots.json` fetch with `api/spots/list.php` | Day 2 |
| [`06-SAVED-MODULE.md`](./06-SAVED-MODULE.md) | Toggle save / list saved spots (DB for logged-in, fallback localStorage) | Day 4 |
| [`07-REVIEWS-MODULE.md`](./07-REVIEWS-MODULE.md) | List + create reviews on the details page | Day 3 |
| [`08-BOOKINGS-MODULE.md`](./08-BOOKINGS-MODULE.md) | Wizard submission, promo validation, history, cancel | Day 3 |
| [`09-CONTACT-MODULE.md`](./09-CONTACT-MODULE.md) | Contact form persists to DB | Day 3 |
| [`10-PROFILE-MODULE.md`](./10-PROFILE-MODULE.md) | Load + update profile fields and password | Day 4 |
| [`11-CHECKLIST.md`](./11-CHECKLIST.md) | End-to-end test plan, demo script, submission checklist | Day 4 |
| [`12-DOCUMENTATION.md`](./12-DOCUMENTATION.md) | **The formal 13-section project documentation required by the rubric** — submission deliverable | Day 5 |

---

## Project Folder Structure (After Backend)

```
tara-pangasinan-main/
├── api/                          ← NEW (all PHP endpoints)
│   ├── auth/
│   │   ├── register.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── session.php
│   ├── spots/
│   │   ├── list.php
│   │   └── get.php
│   ├── saved/
│   │   ├── toggle.php
│   │   └── list.php
│   ├── reviews/
│   │   ├── list.php
│   │   └── create.php
│   ├── bookings/
│   │   ├── create.php
│   │   ├── list.php
│   │   └── cancel.php
│   ├── promos/
│   │   └── validate.php
│   ├── contact/
│   │   └── submit.php
│   └── profile/
│       ├── get.php
│       └── update.php
├── config/                       ← NEW
│   └── database.php              (DB credentials)
├── includes/                     ← NEW (shared PHP)
│   ├── db.php                    (PDO connection)
│   ├── response.php              (JSON helpers)
│   ├── session.php               (session bootstrap)
│   ├── auth.php                  (login required middleware)
│   └── validation.php            (input sanitization)
├── sql/                          ← NEW
│   ├── schema.sql                (CREATE TABLE statements)
│   └── seed.php                  (migrates spots.json into DB)
├── assets/                       (unchanged)
├── css/                          (unchanged)
├── data/
│   └── spots.json                (kept as seed source — not loaded by browser anymore)
├── docs/
│   ├── DESIGN_GUIDELINES.md
│   ├── PROJECT_OVERVIEW.md
│   └── backend/                  ← THIS FOLDER
├── js/
│   ├── api.js                    ← NEW (AJAX wrapper)
│   ├── main.js                   (modified — fetch swapped for api.js)
│   └── update-reviews.js
└── *.html                        (light JS modifications inline)
```

---

## Conventions Used Throughout This Plan

### API Response Envelope (every endpoint)

```json
{
  "success": true,
  "data": { ... },
  "message": "Optional human-readable message"
}
```

On error:

```json
{
  "success": false,
  "error": "Specific error string",
  "code": "validation_error"
}
```

### HTTP Methods

| Method | Used for |
|---|---|
| `GET` | Read operations (list, get) |
| `POST` | Create / update / delete (we use POST for everything that mutates state, simpler than PUT/DELETE for a course project) |

### Naming

- PHP files: `snake_case.php`
- SQL tables: `snake_case` plural (`users`, `spots`, `saved_spots`)
- SQL columns: `snake_case` (`created_at`, `user_id`)
- JS variables (new code): `camelCase`
- API URLs: `/api/<module>/<action>.php`

### Security Baseline

- All database access uses **PDO with prepared statements** — no string concatenation in SQL.
- Passwords are stored with `password_hash($pw, PASSWORD_DEFAULT)`.
- All inputs are validated server-side (do not trust JS validation alone).
- Sessions are server-side; `localStorage.tara_session` becomes a UI-only mirror set by `/api/auth/session.php`.

---

## Pre-Flight Checklist

Before starting Phase 1, confirm:

- [ ] You have **XAMPP** (or Laragon / WAMP) installed.
- [ ] You can open `phpMyAdmin` at `http://localhost/phpmyadmin`.
- [ ] PHP version is **7.4+** (8.0+ preferred).
- [ ] You have a code editor (VS Code recommended) and Git.
- [ ] The full team has agreed on **module ownership** (who owns Auth, who owns Bookings, etc.). Module independence is what makes parallel work possible.

---

## How to Read Each Phase Document

Each module file follows the same structure:

1. **Goal** — One-paragraph summary of what this phase achieves
2. **Files Affected** — Backend files created + frontend files modified
3. **Database Tables** — SQL schema used by this module
4. **API Endpoints** — Request/response specs
5. **Backend Code** — Full PHP source, copy-paste ready
6. **Frontend Integration** — Specific JS/HTML edits with line references
7. **Testing** — Browser-based and `cURL` checks
8. **Done When** — Checkbox-style acceptance criteria

Open the file, follow top to bottom, and you have a working module.

---

Proceed to **[`00-ARCHITECTURE.md`](./00-ARCHITECTURE.md)** to understand the big picture before touching any code.