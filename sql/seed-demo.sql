-- ─────────────────────────────────────────────────────────────────────────────
-- Tara Pangasinan — Demo Seed Data
-- Run AFTER schema.sql and seed-spots.sql are already applied.
-- Inserts demo users, reviews, and example bookings.
--
-- USAGE (phpMyAdmin → SQL tab, or via CLI):
--   mysql -u root tara_pangasinan < sql/seed-demo.sql
--
-- SAFETY: Uses INSERT IGNORE so re-running is safe.
-- ─────────────────────────────────────────────────────────────────────────────

-- ─── DEMO USERS ───────────────────────────────────────────────────────────────
-- Passwords are all: panelpass1  (bcrypt hash generated for demo)
INSERT IGNORE INTO users (id, email, password_hash, first_name, last_name, phone, city, bio, created_at) VALUES
(10, 'demo@panel.test',   '$2y$12$3CX7kU.c9n2tRN3eP9B7MePQH1UxZVscq84mNKKJMjf3c8TKSCmDu', 'Demo',   'User',    '+63 917 000 0000', 'Dagupan City, Pangasinan', 'Test account for panel demonstration.', NOW()),
(11, 'maria@test.com',    '$2y$12$3CX7kU.c9n2tRN3eP9B7MePQH1UxZVscq84mNKKJMjf3c8TKSCmDu', 'Maria',  'Santos',  '+63 918 111 2222', 'Alaminos City, Pangasinan', 'I love island hopping!', NOW()),
(12, 'juan@test.com',     '$2y$12$3CX7kU.c9n2tRN3eP9B7MePQH1UxZVscq84mNKKJMjf3c8TKSCmDu', 'Juan',   'dela Cruz','+63 919 333 4444', 'Lingayen, Pangasinan', 'Proud Pangasinanon.', NOW()),
(13, 'anna@test.com',     '$2y$12$3CX7kU.c9n2tRN3eP9B7MePQH1UxZVscq84mNKKJMjf3c8TKSCmDu', 'Anna',   'Reyes',   '+63 920 555 6666', 'Bolinao, Pangasinan', 'Nature photographer.', NOW()),
(14, 'carlo@test.com',    '$2y$12$3CX7kU.c9n2tRN3eP9B7MePQH1UxZVscq84mNKKJMjf3c8TKSCmDu', 'Carlo',  'Garcia',  '+63 921 777 8888', 'Dagupan City, Pangasinan', 'Food and travel enthusiast.', NOW());

-- ─── DEMO REVIEWS ─────────────────────────────────────────────────────────────
-- One review per (user, spot) — enforced by UNIQUE KEY uq_user_spot
INSERT IGNORE INTO reviews (user_id, spot_id, rating, body, created_at) VALUES
(10, 'hundred-islands',   5, 'Absolutely breathtaking! The island hopping tour was the highlight of my Pangasinan trip. Crystal clear waters and so many islands to explore. A must-visit for every Filipino.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(11, 'hundred-islands',   4, 'Beautiful place, worth every peso. The kayaking around the smaller islands was fantastic. Bring sunscreen though, the sun can be brutal. Would definitely come back with family.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(12, 'hundred-islands',   5, 'One of the best national parks in the Philippines. The snorkeling was amazing and the water is incredibly clear. The boat operators are friendly and very helpful.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(13, 'bolinao-falls',     5, 'Stunning tiered waterfalls surrounded by lush greenery. The water is refreshing and perfect after the long drive. Facilities are clean and well-maintained. Highly recommended!', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(14, 'bolinao-falls',     4, 'Peaceful and serene. Not too crowded when we visited on a weekday morning. The falls have multiple tiers you can jump into. Pack a picnic and spend the whole day here.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 'patar-beach',       5, 'Golden sand that stretches for miles with barely anyone around. Watched the most incredible sunset here. The drive from Manila is worth it just for this beach alone.', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(11, 'patar-beach',       5, 'Hidden gem of Pangasinan! The sand is golden and the waves are gentle. Perfect for camping overnight. Bring your own supplies as there are limited stores nearby.', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(12, 'enchanted-cave',    4, 'The cave pool inside is magical — the light filtering through creates a beautiful blue glow. The guide was knowledgeable. Can get crowded on weekends so go early.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(13, 'cape-bolinao',      5, 'Spectacular 360-degree view from the top! The lighthouse itself is a piece of history dating back to 1903. The climb is worth it — bring water and comfortable shoes.', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(14, 'pangasinan-bangus', 5, 'The Dagupan bangus is truly different from any milkfish I have tried before. Ordered the boneless daing and it was perfectly crispy. Had it for breakfast and could not stop eating!', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(10, 'pigar-pigar',       4, 'Best street food experience in Pangasinan. The stalls at Galvan Street at night are lively and the pigar-pigar is delicious. Pair it with garlic rice and ice cold drinks.', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(11, 'lingayen-gulf',     5, 'Beautiful historical beach. Standing where the allied forces landed in 1945 gave me goosebumps. The boulevard area is great for an evening walk with seafood restaurants nearby.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(12, 'manaoag-church',    4, 'Deeply moving experience for Catholic pilgrims. The basilica is grand and the atmosphere is peaceful. Visit on a weekday to avoid the massive crowds. The souvenir shops outside have nice items.', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Update spot aggregate ratings from the seeded reviews
UPDATE spots SET
    rating        = COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.spot_id = spots.id), rating),
    reviews_count = COALESCE((SELECT COUNT(*) FROM reviews r WHERE r.spot_id = spots.id), reviews_count);

-- ─── DEMO BOOKINGS ────────────────────────────────────────────────────────────
-- For user id=10 (demo@panel.test), assuming user_id 10 exists from above.
INSERT IGNORE INTO bookings
    (reference, user_id, tour_id, tour_date, tour_time, adults, children, infants,
     total, status, contact_name, contact_email, contact_phone, booked_at)
VALUES
    ('TPG-DEMO01', 10, 'hundred-islands', '2026-07-15', '8:00 AM', 2, 1, 0,
     3600.00, 'upcoming',  'Demo User', 'demo@panel.test', '+63 917 000 0000', NOW()),
    ('TPG-DEMO02', 10, 'patar-beach',     '2026-04-10', '1:00 PM', 2, 0, 0,
     1600.00, 'completed', 'Demo User', 'demo@panel.test', '+63 917 000 0000', DATE_SUB(NOW(), INTERVAL 74 DAY));
