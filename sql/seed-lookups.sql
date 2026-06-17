-- ─────────────────────────────────────────────────────────────────────────────
-- Tara Pangasinan — Lookup Table Seeds
-- Import AFTER schema.sql has been run.
-- ─────────────────────────────────────────────────────────────────────────────

-- ─── TOURS ────────────────────────────────────────────────────────────────────
-- Mirrors tour packages currently hardcoded in plan.html
INSERT INTO tours (id, name, location, duration, price, image, badge, badge_color, rating, reviews_count, meeting_point, `includes`) VALUES
(
    'hundred-islands',
    'Hundred Islands Boat Tour',
    'Alaminos City',
    '6–8 hours',
    1200.00,
    'assets/images/spots/hundred-islands.png',
    'Most Popular',
    '#1D9E75',
    4.8,
    248,
    'Alaminos City Pier',
    '["Local boat crew","Life vest","Island hopping (3 islands)","Snorkeling equipment"]'
),
(
    'patar-beach',
    'Patar Beach Sunset Experience',
    'Bolinao',
    '4–5 hours',
    800.00,
    'assets/images/spots/patar-beach.png',
    'Beach',
    '#0284c7',
    4.7,
    134,
    'Patar Beach Entrance',
    '["Cottage rental","Welcome coconut drink","Bonfire setup","Sunset guided walk"]'
),
(
    'enchanted-cave',
    'Enchanted Cave & Falls Combo',
    'Bolinao',
    '5–6 hours',
    1500.00,
    'assets/images/spots/enchanted-cave.png',
    'Adventure',
    '#7c3aed',
    4.6,
    97,
    'Bolinao Town Hall',
    '["Cave entry fee","Life vest","Falls access","Tour guide"]'
),
(
    'cape-bolinao',
    'Cape Bolinao Lighthouse Trek',
    'Bolinao',
    '3–4 hours',
    700.00,
    'assets/images/spots/cape-bolinao.png',
    'Historical',
    '#b45309',
    4.6,
    92,
    'Cape Bolinao Lighthouse Gate',
    '["Lighthouse climb access","Historical guide","Coastal trail walk","Photo stops"]'
),
(
    'lingayen-gulf',
    'Lingayen Gulf Sunrise Tour',
    'Lingayen',
    '2–3 hours',
    500.00,
    'assets/images/spots/lingayen-gulf.png',
    'Scenic',
    '#0e7490',
    4.4,
    65,
    'Lingayen Beach Boulevard',
    '["Sunrise viewing","Historical markers tour","Bangus (milkfish) breakfast","Guide"]'
),
(
    'manaoag-church',
    'Manaoag Heritage Pilgrimage',
    'Manaoag',
    '3–4 hours',
    400.00,
    'assets/images/spots/manaoag-church.png',
    'Pilgrimage',
    '#78716c',
    4.9,
    180,
    'Manaoag Church Main Gate',
    '["Church tour guide","Historical walk","Souvenir voucher","Light refreshments"]'
);

-- ─── ADDONS ───────────────────────────────────────────────────────────────────
INSERT INTO addons (id, name, description, price) VALUES
('snorkel',   'Snorkeling Gear Set',   'Mask, fins, and snorkel for the full underwater experience.',  250.00),
('guide',     'Private Guide Upgrade', 'Dedicated guide just for your group throughout the tour.',     500.00),
('lunch',     'Seafood Lunch Pack',    'Fresh local seafood lunch prepared by the resort.',            450.00),
('photo',     'Photography Package',   'Professional drone + underwater photos delivered digitally.',  800.00),
('transport', 'Round-trip Transport',  'Air-conditioned van pick-up from Dagupan City.',               600.00);

-- ─── PROMOS ───────────────────────────────────────────────────────────────────
INSERT INTO promos (code, type, value, label) VALUES
('TARA10',     'percent',  10.00, '10% off'),
('PANGASINAN', 'fixed',   200.00, '₱200 off'),
('FIRSTTIME',  'percent',  15.00, '15% off');
