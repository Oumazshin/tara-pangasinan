-- ─────────────────────────────────────────────────────────────────────────────
-- Tara Pangasinan — Database Schema
-- Target : MySQL 5.7+ / MariaDB 10.3+
-- Engine : InnoDB (foreign keys, transactions)
-- Charset: utf8mb4 (full Unicode including Filipino characters and emoji)
-- ─────────────────────────────────────────────────────────────────────────────

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

-- ─── USERS ────────────────────────────────────────────────────────────────────
CREATE TABLE users (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    first_name      VARCHAR(100)    NOT NULL,
    last_name       VARCHAR(100)    NOT NULL,
    phone           VARCHAR(30)     DEFAULT NULL,
    city            VARCHAR(150)    DEFAULT NULL,
    bio             TEXT            DEFAULT NULL,
    avatar_url      VARCHAR(500)    DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOTS ────────────────────────────────────────────────────────────────────
-- id is a slug (e.g. "hundred-islands") matching the existing URL scheme
CREATE TABLE spots (
    id              VARCHAR(80)     PRIMARY KEY,
    title           VARCHAR(200)    NOT NULL,
    location        VARCHAR(150)    NOT NULL,
    category        ENUM('Nature','Beach','Historical','Festival','Food') NOT NULL,
    rating          DECIMAL(2,1)    NOT NULL DEFAULT 0.0,
    reviews_count   INT UNSIGNED    NOT NULL DEFAULT 0,
    short_desc      VARCHAR(500)    NOT NULL,
    description     TEXT            NOT NULL,
    image           VARCHAR(500)    NOT NULL,
    lat             DECIMAL(10,7)   DEFAULT NULL,
    lng             DECIMAL(10,7)   DEFAULT NULL,
    hours           VARCHAR(150)    DEFAULT NULL,
    entrance        VARCHAR(150)    DEFAULT NULL,
    contact         VARCHAR(80)     DEFAULT NULL,
    website         VARCHAR(255)    DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_ACTIVITIES (1:many) ─────────────────────────────────────────────────
CREATE TABLE spot_activities (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)     NOT NULL,
    activity        VARCHAR(150)    NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_GALLERY (1:many) ────────────────────────────────────────────────────
CREATE TABLE spot_gallery (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)     NOT NULL,
    image_url       VARCHAR(500)    NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_TIPS (1:many) ───────────────────────────────────────────────────────
CREATE TABLE spot_tips (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)     NOT NULL,
    tip             VARCHAR(500)    NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SPOT_STATS (1:many) ──────────────────────────────────────────────────────
CREATE TABLE spot_stats (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    spot_id         VARCHAR(80)     NOT NULL,
    label           VARCHAR(80)     NOT NULL,
    value           VARCHAR(150)    NOT NULL,
    sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,

    FOREIGN KEY (spot_id) REFERENCES spots(id) ON DELETE CASCADE,
    INDEX idx_spot (spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SAVED_SPOTS (many:many junction) ────────────────────────────────────────
CREATE TABLE saved_spots (
    user_id         INT UNSIGNED    NOT NULL,
    spot_id         VARCHAR(80)     NOT NULL,
    saved_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, spot_id),
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (spot_id) REFERENCES spots(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── REVIEWS ──────────────────────────────────────────────────────────────────
CREATE TABLE reviews (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    spot_id         VARCHAR(80)     NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL,   -- 1 to 5
    body            TEXT            NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (spot_id) REFERENCES spots(id)  ON DELETE CASCADE,
    INDEX idx_spot_created (spot_id, created_at),
    UNIQUE KEY uq_user_spot (user_id, spot_id),
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TOURS ────────────────────────────────────────────────────────────────────
-- Mirrors the tour packages hardcoded in plan.html
CREATE TABLE tours (
    id              VARCHAR(80)     PRIMARY KEY,   -- e.g. "hundred-islands"
    name            VARCHAR(200)    NOT NULL,
    location        VARCHAR(150)    NOT NULL,
    duration        VARCHAR(80)     NOT NULL,
    price           DECIMAL(10,2)   NOT NULL,
    image           VARCHAR(500)    NOT NULL,
    badge           VARCHAR(80)     DEFAULT NULL,
    badge_color     VARCHAR(20)     DEFAULT NULL,
    rating          DECIMAL(2,1)    NOT NULL DEFAULT 0.0,
    reviews_count   INT UNSIGNED    NOT NULL DEFAULT 0,
    meeting_point   VARCHAR(200)    NOT NULL,
    includes        TEXT            NOT NULL,      -- JSON array string
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ADDONS ───────────────────────────────────────────────────────────────────
CREATE TABLE addons (
    id              VARCHAR(40)     PRIMARY KEY,  -- e.g. "snorkel"
    name            VARCHAR(150)    NOT NULL,
    description     VARCHAR(500)    NOT NULL,
    price           DECIMAL(10,2)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PROMOS ───────────────────────────────────────────────────────────────────
CREATE TABLE promos (
    code            VARCHAR(40)     PRIMARY KEY,
    type            ENUM('percent','fixed') NOT NULL,
    value           DECIMAL(10,2)   NOT NULL,
    label           VARCHAR(120)    NOT NULL,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    expires_at      DATETIME        DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── BOOKINGS ─────────────────────────────────────────────────────────────────
CREATE TABLE bookings (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    reference       VARCHAR(40)     NOT NULL UNIQUE,    -- e.g. "TPG-AB12C3"
    user_id         INT UNSIGNED    DEFAULT NULL,       -- nullable: guest bookings allowed
    tour_id         VARCHAR(80)     NOT NULL,
    tour_date       DATE            NOT NULL,
    tour_time       VARCHAR(20)     NOT NULL,           -- "6:00 AM", "8:00 AM", "1:00 PM"
    adults          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    children        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    infants         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    promo_code      VARCHAR(40)     DEFAULT NULL,
    discount        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total           DECIMAL(10,2)   NOT NULL,
    status          ENUM('upcoming','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    contact_name    VARCHAR(150)    NOT NULL,
    contact_email   VARCHAR(255)    NOT NULL,
    contact_phone   VARCHAR(30)     NOT NULL,
    requests        TEXT            DEFAULT NULL,
    booked_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at    DATETIME        DEFAULT NULL,

    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL,
    FOREIGN KEY (tour_id)    REFERENCES tours(id)    ON DELETE RESTRICT,
    FOREIGN KEY (promo_code) REFERENCES promos(code) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_tour_date   (tour_id, tour_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── BOOKING_ADDONS (many:many junction) ─────────────────────────────────────
CREATE TABLE booking_addons (
    booking_id      INT UNSIGNED    NOT NULL,
    addon_id        VARCHAR(40)     NOT NULL,
    price_charged   DECIMAL(10,2)   NOT NULL,   -- locked at time of booking

    PRIMARY KEY (booking_id, addon_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id)   REFERENCES addons(id)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CONTACT_MESSAGES ─────────────────────────────────────────────────────────
CREATE TABLE contact_messages (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100)    NOT NULL,
    last_name       VARCHAR(100)    NOT NULL,
    email           VARCHAR(255)    NOT NULL,
    subject         VARCHAR(80)     NOT NULL,
    message         TEXT            NOT NULL,
    submitted_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address      VARCHAR(45)     DEFAULT NULL,
    is_read         TINYINT(1)      NOT NULL DEFAULT 0,

    INDEX idx_submitted (submitted_at),
    INDEX idx_ip_submitted (ip_address, submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
