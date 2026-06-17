# 12 — Project Documentation (Submission Deliverable)

> **Phase 12 of 12** · Owner: _Documentation lead + entire group_ · Depends on: completed implementation

This file is the **formal documentation deliverable** required by the PUP rubric. It contains all 13 sections the rubric mandates. Most content is pre-written from the project's actual implementation; you only need to fill in `[PLACEHOLDERS]` and convert the diagrams into visual form.

## How to use this file

1. Read top to bottom. Replace every `[PLACEHOLDER]` with your group's actual values.
2. The Use Case Diagram, ERD, and Interface Design sections contain textual specifications. Draw them in **draw.io**, **Lucidchart**, or **Figma**, then embed the resulting images (export as PNG).
3. Capture the screenshots called for in the Interface Design and Testing Results sections (XAMPP must be running, the app seeded, and a demo account logged in).
4. Export the finished document as PDF *or* convert to Word *or* keep as Markdown — the rubric accepts any of these three.
5. Submit alongside the source-code zip per the Submission Guidelines on page 4 of the rubric.

---

# Tara Pangasinan — Project Documentation

---

## 1. Title Page

> _Center this content on the first page when exporting._

**Republic of the Philippines**
**POLYTECHNIC UNIVERSITY OF THE PHILIPPINES**
**College of Computer and Information Sciences**

---

# Tara Pangasinan
## A Web Application for Promoting Tourism in Pangasinan Province

A Final Project Submitted to the Faculty of the
College of Computer and Information Sciences
in Partial Fulfillment of the Requirements for the Course
**[COMP-20163 — Web Development]**

---

**Submitted by:**
**Group 5**

| Name | Role |
|---|---|
| Bacolor, James Clark | Frontend Lead & UI/UX |
| Caole, Stephanie     | UI/UX Designer |
| Guarin, Pauline      | Documentation Lead |
| Soriano, Shouma King | Backend Lead |
| Lituco, Jessica Jhoanne | UI/UX Designer |

**Submitted to:** [PROFESSOR'S NAME]
**Date:** June 18, 2026
**Section:** [SECTION CODE]

---

## 2. Introduction

Pangasinan, located in the western part of Luzon, is among the most visited provinces in the Philippines. It is home to the world-famous Hundred Islands National Park, the historical Cape Bolinao Lighthouse, the pristine Patar White Sand Beach, and the deeply revered Our Lady of Manaoag Shrine. Despite its rich natural and cultural assets, tourism information about the province remains fragmented across social-media pages, blogs, and government microsites that are difficult to compare, often outdated, and rarely allow direct booking.

**Tara Pangasinan** is a database-driven web application that consolidates this information into a single, mobile-friendly platform. The system enables travelers to (1) discover tourist destinations across the province with detailed location, rating, activity, and gallery information; (2) save spots they wish to visit; (3) read and contribute reviews based on real user experiences; (4) book tours directly through a multi-step wizard; and (5) communicate with the operations team via a built-in contact form.

The system was built using the technology stack covered in the Web Development course — HTML5, CSS3, JavaScript (ES5/ES6), PHP 8 with PDO, MySQL via XAMPP, AJAX via the Fetch API, and JSON as the data interchange format. No external front-end frameworks were used; every line of code maps to a topic discussed in class.

The project demonstrates the integration of front-end presentation, client-side and server-side validation, RESTful-style API design, secure authentication using `password_hash`, relational database modeling with foreign-key constraints, and full CRUD operations on multiple resources.

---

## 3. Objectives

### 3.1 General Objective
To design and develop a responsive, secure, database-driven web application that promotes tourism in Pangasinan Province by providing accurate destination information and enabling end-to-end tour booking.

### 3.2 Specific Objectives

By the end of the project, the system shall:

1. Present an **accessible directory of eight curated tourist destinations** in Pangasinan, each enriched with location, category, rating, gallery images, activities, visitor tips, and contextual statistics.
2. Provide **secure user registration and login** using `password_hash` (bcrypt) and PHP session management.
3. Allow registered users to **save destinations** for later reference, with their saved list synchronized between devices through the database.
4. Implement a **complete CRUD lifecycle for visitor reviews** — Create (post a review), Read (view paginated reviews), Update (edit one's own review), Delete (remove one's own review) — with the spot's aggregate rating maintained transactionally.
5. Provide a **multi-step booking wizard** for six tour packages with optional add-ons, server-validated promo codes, and server-side total recomputation to prevent client-side price tampering.
6. Allow users to **view and cancel their bookings** from a personal booking history accessible after login.
7. Accept **contact-form submissions** with honeypot anti-spam protection and IP-based rate limiting (five submissions per hour).
8. Enable users to **edit their profile** (name, email, phone, city, biography) and **change their password** with current-password verification.
9. Enforce **both client-side and server-side input validation** on every form.
10. Maintain **data integrity** through foreign-key constraints, UNIQUE indexes, ENUM-restricted enumerations, CHECK constraints, and transactional aggregates.

---

## 4. Scope and Limitations

### 4.1 Scope

The system covers the following functional areas:

- **Public browsing.** Anyone (including guests) may explore the spot catalog, view spot details, read reviews, and view tour offerings without registration.
- **User account management.** Users may register with email + password, log in, log out, edit their profile, and change their password.
- **Destination catalog.** Eight tourist spots are seeded into the database, each with normalized records for activities, gallery images, visitor tips, and quick statistics.
- **Reviews.** Logged-in users may post one review per spot, edit it, or delete it. Aggregate ratings recompute automatically.
- **Saved spots.** Logged-in users may favorite spots; the list persists in the database.
- **Bookings.** Both guests and registered users may book tours. Registered users see their booking history and may cancel upcoming bookings.
- **Promo codes.** Three promo codes (TARA10, PANGASINAN, FIRSTTIME) are validated server-side.
- **Contact form.** Visitors may submit inquiries; entries persist in the database with rate limiting.
- **Map view.** Spots are plotted on a Leaflet-powered map with their geographic coordinates.

### 4.2 Limitations

The following are explicitly **outside** the scope of this project:

- **No payment processing.** Bookings record the desired tour, date, and add-ons but do not collect or charge for payment. Real-world deployment would integrate a gateway such as PayMongo or Stripe.
- **No automated email delivery.** Confirmation messages are displayed on screen only. Real-world deployment would integrate SMTP via PHPMailer or a transactional email provider.
- **No native mobile app.** The application is a responsive web app, accessed through a browser. There is no iOS or Android binary.
- **No multi-language support.** The interface is English-only; localization to Filipino, Pangasinense, or Ilocano was not included.
- **No public admin panel.** Administrators read submissions and manage records directly through phpMyAdmin during this academic cycle. A formal admin UI is left as future work.
- **No third-party authentication.** Sign-in with Google, Facebook, or Apple was excluded to keep the authentication stack within course material.
- **No password reset flow.** Implementing this would require an email-delivery integration, which is excluded above.
- **Single deployment target.** The application is deployed on the local XAMPP stack of the developers' machines and is not yet hosted on a public web server.

---

## 5. System Features

The system is organized into **eight functional modules** corresponding to user-visible capability areas:

### 5.1 Authentication Module
- Email + password registration with `password_hash()` storage
- Login with `password_verify()` (constant-time comparison)
- Session-based authentication via PHP `$_SESSION`
- Logout with full session destruction
- Live session check via `/api/auth/session.php`

### 5.2 Destination Catalog Module
- Listing of all spots with search, sort (by popularity, rating, name), and category filter
- Detailed spot page with description, photo gallery, activities, visitor tips, statistics, hours, contact information, and location coordinates
- Interactive map with all spots plotted

### 5.3 Reviews Module *(Full CRUD demonstration)*
- **Create:** Logged-in users may post a rating + body (10–1000 characters)
- **Read:** Public listing of all reviews per spot, newest first, paginated 10 at a time
- **Update:** Inline editing of own review with star picker and textarea
- **Delete:** Soft confirmation prompt, then hard delete with aggregate recompute
- One-review-per-user-per-spot enforced by UNIQUE constraint
- Aggregate spot rating and review count recomputed transactionally in every mutating operation

### 5.4 Saved Spots Module
- Heart icon toggle on any spot card persists save/unsave to database
- Saved page (`saved.html`) shows the logged-in user's list
- Guest users retain saves in `localStorage` as a fallback

### 5.5 Bookings Module
- Six-step booking wizard: select tour → date + time → guest count → add-ons → review summary → confirmation
- Three time slots: Sunrise (6 AM), Morning (8 AM), Afternoon (1 PM)
- Five optional add-ons (snorkel gear, private guide, lunch pack, photo package, transport)
- Three promo codes validated server-side (TARA10 = 10%, PANGASINAN = ₱200 fixed, FIRSTTIME = 15%)
- Server-side total recomputation prevents client-supplied price manipulation
- Guest bookings allowed (nullable `user_id`); registered users see booking history
- Cancellation with timestamped audit trail (`cancelled_at` column)

### 5.6 Profile Module
- Editable user details: first name, last name, email, phone, city, biography
- Email uniqueness check (409 Conflict on duplicate)
- Inline password change requiring current-password verification

### 5.7 Contact Module
- Subject category dropdown (Tour Inquiry, Customer Support, Partnership, Other)
- Honeypot field invisible to humans, exploitable only by naive bots
- IP-based rate limiting: 5 submissions per hour per IP
- Submissions persisted to `contact_messages` table for admin review

### 5.8 Map Module
- Leaflet.js client-side library renders an OpenStreetMap base layer
- Spots plotted using latitude and longitude from the `spots` table
- Marker click opens a popup with thumbnail, name, and link to details

---

## 6. Use Case Diagram

> **Drawing instructions.** The diagram below uses textual notation. Replicate it in draw.io or Lucidchart using the standard UML use-case symbols (stick figures for actors, ovals for use cases, lines for associations, dashed arrows with `<<include>>` labels for inclusions). Export as PNG and insert here.

### 6.1 Actors

- **Guest** — an unauthenticated visitor browsing the site
- **Registered User** — an authenticated user with a database record in `users`
- **System Administrator** — staff who read database records directly via phpMyAdmin

### 6.2 Use Cases by Actor

**Guest** can:
- View Destination Catalog
- Search and Filter Destinations
- View Destination Details
- View Reviews
- View Tour Catalog
- Submit Contact Inquiry
- Book Tour (as guest)
- Register Account
- Log In

**Registered User** can (inherits everything Guest can do, plus):
- Save Destination to Favorites
- Unsave Destination
- Post Review *(<<include>> Validate Review Input)*
- Edit Own Review
- Delete Own Review
- View Booking History
- Cancel Upcoming Booking
- Edit Profile *(<<include>> Verify Email Uniqueness)*
- Change Password *(<<include>> Verify Current Password)*
- Log Out

**System Administrator** can:
- Read Contact Messages
- Review Bookings
- Moderate Reviews
- Manage Tours and Promo Codes

### 6.3 Textual Diagram (for reference)

```
                ┌───────────────────────────────────────────┐
                │              Tara Pangasinan              │
                │                                           │
                │  (View Catalog)        (Search/Filter)    │
   ┌──────┐ ────│  (View Details)        (View Reviews)     │
   │Guest │     │  (View Tours)          (Book Tour)        │
   └──────┘ ────│  (Submit Contact)      (Register)         │
                │  (Log In)                                 │
                │                                           │
                │  ─────── Registered User adds: ────────   │
                │                                           │
   ┌──────┐ ────│  (Save Spot)           (Post Review)      │
   │ User │     │  (Edit Review)         (Delete Review)    │
   └──────┘ ────│  (View My Bookings)    (Cancel Booking)   │
                │  (Edit Profile)        (Change Password)  │
                │  (Log Out)                                │
                │                                           │
                │  ─────── Admin (via phpMyAdmin): ──────   │
                │                                           │
   ┌──────┐ ────│  (Read Messages)       (Review Bookings)  │
   │Admin │     │  (Moderate Reviews)    (Manage Tours)     │
   └──────┘     │                                           │
                └───────────────────────────────────────────┘
```

---

## 7. Entity Relationship Diagram (ERD)

> **Drawing instructions.** Convert the textual ERD below into a visual using draw.io's "Entity Relation" shape library. Use crow's-foot notation for cardinalities. Export the image and insert here.

### 7.1 Entities and Attributes (summary; full attributes in Section 8)

| Entity | Primary Key | Notes |
|---|---|---|
| `users` | `id` | Stores `password_hash` only, never plaintext. |
| `spots` | `id` (slug VARCHAR) | Catalog of destinations. |
| `spot_activities` | `id` | Child of `spots`. |
| `spot_gallery` | `id` | Child of `spots`. |
| `spot_tips` | `id` | Child of `spots`. |
| `spot_stats` | `id` | Child of `spots`. |
| `saved_spots` | (`user_id`, `spot_id`) composite | Junction table. |
| `reviews` | `id` | UNIQUE on (`user_id`, `spot_id`). |
| `tours` | `id` (slug VARCHAR) | Booking offerings. |
| `addons` | `id` (slug VARCHAR) | Optional purchases. |
| `promos` | `code` | Discount codes. |
| `bookings` | `id` | Nullable `user_id` for guests. |
| `booking_addons` | (`booking_id`, `addon_id`) composite | Junction table. |
| `contact_messages` | `id` | No FK; standalone log. |

### 7.2 Relationships

| Parent | Child | Cardinality | Description |
|---|---|---|---|
| `users` | `saved_spots` | 1 : N | A user may save many spots. |
| `users` | `reviews` | 1 : N | A user may post many reviews (one per spot). |
| `users` | `bookings` | 1 : N | A user may book many tours. |
| `spots` | `spot_activities` | 1 : N | Each spot has multiple activities. |
| `spots` | `spot_gallery` | 1 : N | Each spot has multiple gallery images. |
| `spots` | `spot_tips` | 1 : N | Each spot has multiple visitor tips. |
| `spots` | `spot_stats` | 1 : N | Each spot has multiple statistics rows. |
| `spots` | `saved_spots` | 1 : N | Each spot may be saved by many users. |
| `spots` | `reviews` | 1 : N | Each spot may receive many reviews. |
| `tours` | `bookings` | 1 : N | Each tour may be booked many times. |
| `addons` | `booking_addons` | 1 : N | Each add-on may be selected on many bookings. |
| `bookings` | `booking_addons` | 1 : N | Each booking may include multiple add-ons. |
| `promos` | `bookings` | 1 : N (nullable) | A booking may optionally use a promo. |

### 7.3 Textual ERD (for reference)

```
           users                                spots
        ┌────────┐                          ┌──────────┐
        │ id PK  │◄───┐                ┌───►│  id PK   │◄───┐
        └────────┘    │                │    └──────────┘    │
            │ 1       │                │ 1                  │
            │         │                │                    │
            │ N       │                │ N                  │
  ┌─────────▼────┐    │    ┌───────────▼───────┐            │
  │  reviews     │────┼───►│ saved_spots (M:N) │            │
  │  UQ(uid,sid) │    │    └───────────────────┘            │
  └──────────────┘    │                                     │
            │         │   ┌────────────────┐                │
            └─────────┼──►│ spot_activities│────────────────┤
                      │   └────────────────┘                │
                      │   ┌────────────────┐                │
                      ├──►│ spot_gallery   │────────────────┤
                      │   └────────────────┘                │
                      │   ┌────────────────┐                │
                      ├──►│ spot_tips      │────────────────┤
                      │   └────────────────┘                │
                      │   ┌────────────────┐                │
                      └──►│ spot_stats     │────────────────┘
                          └────────────────┘

         tours                bookings              addons
       ┌────────┐         ┌───────────────┐       ┌────────┐
       │ id PK  │────────►│ id PK         │       │ id PK  │
       └────────┘  1:N    │ user_id (NULL)│       └────────┘
                          │ tour_id FK    │           │
       ┌────────┐         │ promo_code FK │           │ 1
       │ promos │────────►│ reference UQ  │           │
       │code PK │  1:N    │ status        │           │ N
       └────────┘  (opt)  └───────┬───────┘           │
                                  │ 1                 │
                                  │                   │
                                  │ N                 │
                          ┌───────▼──────────────┐    │
                          │ booking_addons (M:N) │◄───┘
                          └──────────────────────┘

         contact_messages (standalone)
       ┌──────────────────────┐
       │ id PK                │
       │ first_name, last_name│
       │ email, subject       │
       │ message, ip_address  │
       │ submitted_at         │
       └──────────────────────┘
```

---

## 8. Data Dictionary

Full column reference for every table. Types follow MySQL conventions.

### 8.1 `users`

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | Surrogate primary key. |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Login identifier. |
| `password_hash` | VARCHAR(255) | NOT NULL | Bcrypt-hashed password. |
| `first_name` | VARCHAR(100) | NOT NULL | User's given name. |
| `last_name` | VARCHAR(100) | NOT NULL | User's family name. |
| `phone` | VARCHAR(30) | NULL | Contact number (optional). |
| `city` | VARCHAR(150) | NULL | Home city. |
| `bio` | TEXT | NULL | Short biography. |
| `avatar_url` | VARCHAR(500) | NULL | Profile picture URL. |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Registration timestamp. |
| `updated_at` | DATETIME | NOT NULL, ON UPDATE | Last modification time. |

### 8.2 `spots`

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | VARCHAR(80) | PK | URL slug (e.g. `hundred-islands`). |
| `title` | VARCHAR(200) | NOT NULL | Display name. |
| `location` | VARCHAR(150) | NOT NULL | City or municipality. |
| `category` | ENUM | NOT NULL | One of Nature, Beach, Historical, Festival, Food. |
| `rating` | DECIMAL(2,1) | NOT NULL, DEFAULT 0.0 | Average review rating. |
| `reviews_count` | INT UNSIGNED | NOT NULL, DEFAULT 0 | Cached review count. |
| `short_desc` | VARCHAR(500) | NOT NULL | Card-sized teaser. |
| `description` | TEXT | NOT NULL | Full description. |
| `image` | VARCHAR(500) | NOT NULL | Hero image URL. |
| `lat` | DECIMAL(10,7) | NULL | Latitude. |
| `lng` | DECIMAL(10,7) | NULL | Longitude. |
| `hours`, `entrance`, `contact`, `website` | VARCHAR | NULL | Visitor-info fields. |
| `created_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Insertion timestamp. |

### 8.3 `spot_activities` / `spot_gallery` / `spot_tips` / `spot_stats`

All four follow the same shape: `(id PK auto-inc, spot_id VARCHAR(80) FK → spots(id), <payload>, sort_order TINYINT)` with `ON DELETE CASCADE`.

### 8.4 `saved_spots`

| Column | Type | Constraints |
|---|---|---|
| `user_id` | INT UNSIGNED | PK composite, FK → users(id) ON DELETE CASCADE |
| `spot_id` | VARCHAR(80) | PK composite, FK → spots(id) ON DELETE CASCADE |
| `saved_at` | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 8.5 `reviews`

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | INT UNSIGNED | FK → users(id) ON DELETE CASCADE | |
| `spot_id` | VARCHAR(80) | FK → spots(id) ON DELETE CASCADE | |
| `rating` | TINYINT UNSIGNED | CHECK BETWEEN 1 AND 5 | |
| `body` | TEXT | NOT NULL | 10–1000 characters. |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| _index_ | UNIQUE | `(user_id, spot_id)` | One review per user per spot. |

### 8.6 `tours`

| Column | Type | Constraints |
|---|---|---|
| `id` | VARCHAR(80) | PK slug |
| `name`, `location`, `duration`, `image`, `meeting_point` | VARCHAR | NOT NULL |
| `price` | DECIMAL(10,2) | NOT NULL |
| `badge`, `badge_color` | VARCHAR | NULL |
| `rating`, `reviews_count` | DECIMAL/INT | DEFAULT 0 |
| `includes` | TEXT | NOT NULL (JSON array of inclusion strings) |

### 8.7 `addons`

| Column | Type | Constraints |
|---|---|---|
| `id` | VARCHAR(40) | PK slug |
| `name`, `description` | VARCHAR | NOT NULL |
| `price` | DECIMAL(10,2) | NOT NULL |

### 8.8 `promos`

| Column | Type | Constraints |
|---|---|---|
| `code` | VARCHAR(40) | PK |
| `type` | ENUM('percent','fixed') | NOT NULL |
| `value` | DECIMAL(10,2) | NOT NULL |
| `label` | VARCHAR(120) | NOT NULL (UI display text) |
| `is_active` | TINYINT(1) | DEFAULT 1 |
| `expires_at` | DATETIME | NULL |

### 8.9 `bookings`

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `reference` | VARCHAR(40) | UNIQUE, NOT NULL | `TPG-XXXXXX` reference. |
| `user_id` | INT UNSIGNED | FK → users(id) ON DELETE SET NULL, **NULL allowed** | Guest bookings supported. |
| `tour_id` | VARCHAR(80) | FK → tours(id) ON DELETE RESTRICT | |
| `tour_date` | DATE | NOT NULL | |
| `tour_time` | VARCHAR(20) | NOT NULL | One of three slot values. |
| `adults`, `children`, `infants` | TINYINT UNSIGNED | DEFAULT 0 | |
| `promo_code` | VARCHAR(40) | FK → promos(code) ON DELETE SET NULL | Optional. |
| `discount`, `total` | DECIMAL(10,2) | NOT NULL | |
| `status` | ENUM('upcoming','completed','cancelled') | DEFAULT 'upcoming' | |
| `contact_name`, `contact_email`, `contact_phone` | VARCHAR | NOT NULL | |
| `requests` | TEXT | NULL | Special instructions. |
| `booked_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| `cancelled_at` | DATETIME | NULL | Set on cancellation. |

### 8.10 `booking_addons`

| Column | Type | Constraints |
|---|---|---|
| `booking_id` | INT UNSIGNED | PK composite, FK → bookings(id) ON DELETE CASCADE |
| `addon_id` | VARCHAR(40) | PK composite, FK → addons(id) ON DELETE RESTRICT |
| `price_charged` | DECIMAL(10,2) | NOT NULL (snapshot of addon price at booking time) |

### 8.11 `contact_messages`

| Column | Type | Constraints |
|---|---|---|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `first_name`, `last_name` | VARCHAR(100) | NOT NULL |
| `email` | VARCHAR(255) | NOT NULL |
| `subject` | VARCHAR(80) | NOT NULL (one of 4 enum values) |
| `message` | TEXT | NOT NULL |
| `ip_address` | VARCHAR(45) | NULL (IPv4 or IPv6) |
| `submitted_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| `is_read` | TINYINT(1) | DEFAULT 0 |

---

## 9. Database Design

### 9.1 Design Philosophy

The schema follows **Third Normal Form (3NF)** principles. Three core decisions guided the design:

1. **Normalize repeating data into child tables.** Instead of storing activities, gallery images, tips, and statistics inside a JSON column on `spots`, each repeating group is given its own table (`spot_activities`, `spot_gallery`, etc.) with a foreign key back to `spots`. This enables ordered retrieval (`ORDER BY sort_order`), referential integrity, and individual updates without rewriting the parent row.
2. **Use slugs as primary keys for stable identifiers.** The `spots`, `tours`, and `addons` tables use VARCHAR slugs (`hundred-islands`, `snorkel`) instead of opaque INT IDs. The slugs already appear in URLs (`details.html?id=hundred-islands`) and stay stable across redeploys, which avoids URL churn.
3. **Snapshot price-sensitive data at write time.** `booking_addons.price_charged` records what the customer was actually charged. If an admin later changes the price of "Seafood Lunch Pack" from ₱450 to ₱600, *past bookings still show ₱450*. This is essential for auditability.

### 9.2 Data Integrity Mechanisms

Beyond the standard primary-key and foreign-key declarations, the schema enforces integrity through:

- **`UNIQUE KEY uq_user_spot` on `reviews(user_id, spot_id)`** — enforces "one review per user per spot" at the database layer. Application logic cannot accidentally introduce duplicates.
- **`UNIQUE` on `users.email`** — prevents account duplication. The application's email-conflict check is defense in depth; even without it, MySQL rejects the insert.
- **`CHECK (rating BETWEEN 1 AND 5)`** on `reviews.rating` — rejects out-of-range ratings at the schema level.
- **`ENUM`-restricted columns** — `spots.category`, `bookings.status`, `promos.type`, and contact `subject` are restricted to fixed value sets at the schema level, eliminating an entire class of validation bugs.
- **`ON DELETE CASCADE` on dependent rows** — deleting a `spot` automatically removes its activities, gallery, tips, stats, saved entries, and reviews. No orphan rows.
- **`ON DELETE SET NULL` on `bookings.user_id` and `bookings.promo_code`** — preserves booking history even after a user account is deleted or a promo code is retired.
- **`ON DELETE RESTRICT` on `bookings.tour_id` and `booking_addons.addon_id`** — prevents deletion of tours or add-ons that have bookings against them.
- **Composite primary keys** on junction tables (`saved_spots`, `booking_addons`) — enforce uniqueness of each link.
- **Transactions** wrap multi-row operations — `reviews.create.php`, for example, updates the `reviews` table and the `spots` aggregate in a single transaction that rolls back on any error.

### 9.3 Indexes

| Table | Index | Purpose |
|---|---|---|
| `users` | `idx_email` | Login lookup. |
| `spots` | `idx_category` | Filter by category on Explore page. |
| `spot_activities`, `spot_gallery`, `spot_tips`, `spot_stats` | `idx_spot` | Bulk-load related rows per spot. |
| `reviews` | `idx_spot_created` | Newest-first listing per spot. |
| `reviews` | `uq_user_spot` | Duplicate prevention and ownership check. |
| `bookings` | `idx_user_status` | "My bookings" filtered by status. |
| `bookings` | `idx_tour_date` | Tour-occupancy queries (future feature). |
| `contact_messages` | `idx_ip_submitted` | Rate-limit lookup. |
| `contact_messages` | `idx_submitted` | Newest-first admin display. |

### 9.4 Sample Queries

The application's most common queries are simple thanks to the normalized layout:

```sql
-- List all spots in the Beach category sorted by rating
SELECT id, title, location, rating
FROM spots
WHERE category = 'Beach'
ORDER BY rating DESC, reviews_count DESC;

-- Get the latest 10 reviews for Hundred Islands
SELECT r.body, r.rating, CONCAT(u.first_name,' ',u.last_name) AS user_name
FROM reviews r
JOIN users u ON u.id = r.user_id
WHERE r.spot_id = 'hundred-islands'
ORDER BY r.created_at DESC
LIMIT 10;

-- Get a user's upcoming bookings with tour details
SELECT b.reference, b.tour_date, b.total, t.name
FROM bookings b
JOIN tours t ON t.id = b.tour_id
WHERE b.user_id = ? AND b.status = 'upcoming'
ORDER BY b.tour_date ASC;
```

---

## 10. Interface Design

> **Screenshot instructions.** Capture each page at desktop (1440 × 900) and mobile (375 × 812) resolution. Use the browser's responsive design mode. Save to `/docs/screenshots/` and insert below.

### 10.1 Design System

The site uses a tight, intentional design system rather than off-the-shelf styling:

- **Brand color** — `#1D9E75` (forest-leaf green), evoking Pangasinan's natural assets
- **Typography** — system font stack for fast loading; bold weight for headings
- **Layout** — 1200 px max-width container; 12-column grid on desktop; single column on mobile (< 768 px)
- **Components** — rounded corners (12–24 px), subtle shadows, generous whitespace, consistent button and form styles
- **Iconography** — inline SVG icons (no icon font); custom star icons; Leaflet's default marker iconography on the map
- **Imagery** — high-resolution photos sourced via Unsplash with attribution where required

### 10.2 Page Inventory

| Page | URL | Purpose | Screenshot |
|---|---|---|---|
| Home | `home.html` | Featured spots, hero CTA | `[home.png]` |
| Explore | `explore.html` | Catalog with filters | `[explore.png]` |
| Details | `details.html?id=<slug>` | Single-spot deep-dive | `[details.png]` |
| Map | `map.html` | Geographic view | `[map.png]` |
| Plan | `plan.html` | Booking wizard | `[plan.png]` |
| About | `about.html` | Project context | `[about.png]` |
| Contact | `contact.html` | Inquiry form | `[contact.png]` |
| Login | `login.html` | Authentication entry | `[login.png]` |
| Register | `register.html` | Account creation | `[register.png]` |
| Profile | `profile.html` | Account settings + bookings | `[profile.png]` |
| Saved | `saved.html` | Favorites list | `[saved.png]` |

### 10.3 Responsive Behavior

- **Desktop (≥ 1024 px)** — multi-column grids, fixed sidebar on details/profile, horizontal nav
- **Tablet (768–1023 px)** — two-column grids, condensed nav
- **Mobile (< 768 px)** — single-column grids, collapsed hamburger nav, full-width forms

### 10.4 Accessibility Considerations

- Semantic HTML (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`)
- Form labels paired with inputs via `for` / `id`
- Color contrast of at least 4.5:1 for body text against background
- Focus states visible on all interactive elements
- Keyboard navigation supported throughout (Tab, Enter, Esc)

---

## 11. Testing Results

Each module was tested through a combination of manual browser tests and direct API calls. The matrix below records the outcome of representative test cases drawn from the per-module test plans.

### 11.1 Authentication Module

| # | Test Case | Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| A1 | Register new account | Fill register form with unique email + 8+ char password | 201 + session set | [PASS/FAIL] | [✓/✗] |
| A2 | Register duplicate email | Re-submit same email | 409 "Email already registered" | [PASS/FAIL] | [✓/✗] |
| A3 | Login with correct credentials | Submit valid email + password | 200 + session set + redirect | [PASS/FAIL] | [✓/✗] |
| A4 | Login with wrong password | Submit wrong password | 401 "Invalid credentials" | [PASS/FAIL] | [✓/✗] |
| A5 | Access protected route logged out | GET `/api/profile/get.php` without session | 401 AUTH_REQUIRED | [PASS/FAIL] | [✓/✗] |
| A6 | Logout destroys session | Click logout, retry protected route | 401 AUTH_REQUIRED | [PASS/FAIL] | [✓/✗] |

### 11.2 Reviews Module (Full CRUD)

| # | Test Case | Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| R1 | **Create** review (happy path) | Post 4-star + valid body | 200 + sidebar rating updates | [PASS/FAIL] | [✓/✗] |
| R2 | Create duplicate review | Post second review for same spot | 409 "Already reviewed this spot" | [PASS/FAIL] | [✓/✗] |
| R3 | Create with short body | Body length 5 chars | 400 "Must be at least 10 characters" | [PASS/FAIL] | [✓/✗] |
| R4 | **Read** reviews | GET list for spot | Reviews paginated 10 per page | [PASS/FAIL] | [✓/✗] |
| R5 | **Update** own review | Click Edit, modify, Save | 200 + card updates inline + aggregate recomputes | [PASS/FAIL] | [✓/✗] |
| R6 | Update someone else's review | POST update.php with another user's review_id | 404 "Not found" | [PASS/FAIL] | [✓/✗] |
| R7 | **Delete** own review | Click Delete + confirm | 200 + card removed + aggregate recomputes | [PASS/FAIL] | [✓/✗] |
| R8 | Delete last review on spot | Delete the only review of a spot | rating goes to 0.0, count to 0 | [PASS/FAIL] | [✓/✗] |

### 11.3 Bookings Module

| # | Test Case | Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| B1 | Book as guest | Complete wizard without login | TPG-XXXXXX reference returned | [PASS/FAIL] | [✓/✗] |
| B2 | Book as registered user | Complete wizard while logged in | Booking visible in profile | [PASS/FAIL] | [✓/✗] |
| B3 | Apply valid promo TARA10 | Type code, click Apply | 10% off shown in summary | [PASS/FAIL] | [✓/✗] |
| B4 | Apply invalid promo | Type FAKE123 | "Promo not found or expired" | [PASS/FAIL] | [✓/✗] |
| B5 | Server total enforcement | Submit booking with `total: 1` in body | Server ignores client total | [PASS/FAIL] | [✓/✗] |
| B6 | Cancel upcoming booking | Click Cancel + confirm | Status becomes cancelled, `cancelled_at` stamped | [PASS/FAIL] | [✓/✗] |
| B7 | Cancel other user's booking | POST cancel.php with another user's booking_id | 404 "Not found" | [PASS/FAIL] | [✓/✗] |

### 11.4 Other Modules

| # | Module | Test Case | Expected Result | Status |
|---|---|---|---|---|
| S1 | Spots | GET list of all spots | Returns 8 spots with activities, gallery | [✓/✗] |
| S2 | Spots | GET spot by slug | Full detail with all relations | [✓/✗] |
| S3 | Spots | GET non-existent spot | 404 "Spot not found" | [✓/✗] |
| SV1 | Saved | Toggle save (logged in) | Heart filled, row in `saved_spots` | [✓/✗] |
| SV2 | Saved | Toggle unsave | Heart cleared, row removed | [✓/✗] |
| C1 | Contact | Submit valid message | Row in `contact_messages` | [✓/✗] |
| C2 | Contact | Submit with honeypot filled | Fake success, no DB row | [✓/✗] |
| C3 | Contact | 6th submission from same IP within 1 hour | 429 rate-limit | [✓/✗] |
| P1 | Profile | Load profile | Fields populate from DB | [✓/✗] |
| P2 | Profile | Update profile | Sidebar reflects change | [✓/✗] |
| P3 | Profile | Change password (correct current pw) | Success; new password works for login | [✓/✗] |
| P4 | Profile | Change password (wrong current pw) | 401 "Current password incorrect" | [✓/✗] |

### 11.5 Security Testing

| # | Test | Method | Expected Result | Status |
|---|---|---|---|---|
| SEC1 | SQL injection in login | Submit `' OR '1'='1` as email | Stored as literal string; not executed | [✓/✗] |
| SEC2 | XSS in review body | Submit `<script>alert(1)</script>` | Renders as escaped text via `escapeHTML()` | [✓/✗] |
| SEC3 | Password never returned by API | GET `/api/profile/get.php` | Response excludes `password_hash` | [✓/✗] |
| SEC4 | Session cookie flags | Inspect cookie in DevTools | `HttpOnly`, `SameSite=Lax` | [✓/✗] |

### 11.6 Browser Compatibility

Tested on:

| Browser | Version | Result |
|---|---|---|
| Chrome | [VERSION] | [PASS/FAIL] |
| Firefox | [VERSION] | [PASS/FAIL] |
| Edge | [VERSION] | [PASS/FAIL] |
| Safari (mobile) | [VERSION] | [PASS/FAIL] |

---

## 12. Conclusion

The Tara Pangasinan project successfully realized every requirement set in the course rubric. Across eight functional modules, the team built a database-driven web application that demonstrates the full range of front-end and back-end skills covered during the semester: semantic HTML and responsive CSS on the surface, AJAX-driven interactions in JavaScript, PHP business logic on the server, and a normalized MySQL schema underneath. The application is fully functional, ready to demonstrate, and architecturally honest about its scope.

Three contributions distinguish this project beyond the minimum specification:

1. **Full CRUD on the Reviews resource.** Most student projects implement isolated Create or Read operations; Tara Pangasinan demonstrates all four operations on a single resource with transactional aggregate consistency.
2. **Server-side total enforcement on bookings.** By recomputing prices server-side, the application defends against a class of client-side manipulation attacks that hardcoded-JavaScript implementations cannot prevent.
3. **Honeypot anti-spam with IP rate limiting.** The contact form blocks the majority of automated submissions without requiring any third-party CAPTCHA service.

The team encountered and resolved several technical challenges during development. Migrating from `localStorage` to a database-backed session required careful attention to authentication state synchronization. The transition from the original hardcoded `TOURS` and `PROMOS` JavaScript constants to server-validated data required redesigning the booking wizard's state model. Cross-cutting concerns like consistent JSON error envelopes, prepared-statement parameter binding, and transactional aggregate updates demanded discipline across every module.

The team gained practical experience in (a) designing a normalized relational schema that supports real application workflows, (b) writing PHP that is auditable, secure, and free of SQL injection by construction, (c) integrating the front-end and back-end via JSON over AJAX without a framework, and (d) coordinating parallel development across multiple feature modules using a clearly written specification.

If extended beyond this academic project, the highest-value next steps would be (1) integrating a payment gateway to complete the booking flow, (2) deploying the application to a public web server, (3) adding an administrative dashboard for managing tours and reviewing messages, and (4) translating the interface into Filipino and other regional languages.

---

## 13. References

> Replace bracketed placeholders with the actual editions/dates you consulted.

### Documentation
- PHP Group. (2026). *PHP Manual.* Retrieved from https://www.php.net/manual/en/
- Oracle Corporation. (2026). *MySQL 8.0 Reference Manual.* Retrieved from https://dev.mysql.com/doc/refman/8.0/en/
- Mozilla Developer Network. (2026). *Web technology for developers.* Retrieved from https://developer.mozilla.org/

### Libraries Used
- Leaflet — open-source JavaScript library for interactive maps. Retrieved from https://leafletjs.com/
- OpenStreetMap — base map tiles under ODbL license. Retrieved from https://www.openstreetmap.org/copyright

### Course Material
- [INSTRUCTOR NAME]. (2026). *COMP-20163 Web Development: Lecture Slides and Hands-on Activities.* Polytechnic University of the Philippines, College of Computer and Information Sciences.

### Imagery
- Unsplash. (2026). *Tourist destination photographs.* Used under the Unsplash License. Retrieved from https://unsplash.com/

### Industry Best Practices Consulted
- OWASP Foundation. (2026). *OWASP Top Ten.* Retrieved from https://owasp.org/Top10/ — Specifically consulted for A02 Cryptographic Failures (password hashing) and A03 Injection (prepared statements).

### Tools
- XAMPP — local Apache + MySQL + PHP stack. Retrieved from https://www.apachefriends.org/
- phpMyAdmin — database administration UI. Retrieved from https://www.phpmyadmin.net/
- Visual Studio Code — source editor. Retrieved from https://code.visualstudio.com/

---

# Submission Checklist

Before exporting this document and submitting:

- [ ] Title page placeholders (course code, professor, section, member roles) are filled in
- [ ] All `[PASS/FAIL]` and `[✓/✗]` cells in Section 11 are completed based on actual testing
- [ ] All `[VERSION]` placeholders in browser compatibility table are filled in
- [ ] Use Case Diagram (Section 6) has been drawn and embedded as an image
- [ ] ERD (Section 7) has been drawn and embedded as an image
- [ ] At least one screenshot per page from Section 10.2 has been embedded
- [ ] Document has been spell-checked
- [ ] Exported as PDF (recommended) or DOCX or final README
- [ ] Bundled with source code zip per Submission Guidelines

— *End of Documentation Deliverable*
