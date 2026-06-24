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
),
(
    'bolinao-falls',
    'Bolinao Falls Adventure',
    'Bolinao',
    '3–4 hours',
    600.00,
    'assets/images/nature/bolinao-falls-1.jpg',
    'Nature',
    '#10b981',
    4.2,
    88,
    'Bolinao Town Entrance',
    '["Falls entry fee","Life vest","Nature guide","Freshwater swimming"]'
),
(
    'bangus-festival',
    'Dagupan Bangus Festival Tour',
    'Dagupan',
    '4–5 hours',
    550.00,
    'assets/images/festivals/bangus-festival.jpg',
    'Festival',
    '#f59e0b',
    4.5,
    120,
    'Dagupan City Hall Plaza',
    '["Festival guide","Street food tour","Cultural show access","Bangus lunch"]'
);

-- Additional tours for all remaining spots
INSERT INTO tours (id, name, location, duration, price, image, badge, badge_color, rating, reviews_count, meeting_point, `includes`) VALUES
('cabongaoan-beach',    'Cabongaoan Beach & Death Pool Tour',      'Burgos',      '3–5 hours', 450.00,  'assets/images/beaches/cabongaoan-beach.jpg',                   'Beach',      '#0284c7', 4.6, 80,  'Burgos Municipal Hall',            '["Beach access","Tidal pool guide","Snorkeling equipment","Shoreline walk"]'),
('tondol-beach',        'Tondol White Sandbar Experience',          'Anda',        '3–4 hours', 400.00,  'assets/images/beaches/tondol-whitesand.jpg',                   'Beach',      '#0e7490', 4.6, 120, 'Tondol Beach Entrance',            '["Sandbar guide","Boat ride to islet","Fresh coconut drink","Photography stops"]'),
('tambobong-beach',     'Tambobong Island Hopping Tour',            'Dasol',       '4–5 hours', 650.00,  'assets/images/beaches/tambobong-beach.jpg',                    'Beach',      '#0284c7', 4.8, 90,  'Dasol Port',                       '["Boat crew","Snorkeling gear","Colibra Island visit","Packed lunch"]'),
('sky-plaza',           'Sky Plaza & Cristo Rey Trek',              'Natividad',   '4–6 hours', 550.00,  'assets/images/nature/sky-plaza.jpg',                           'Adventure',  '#7c3aed', 4.2, 75,  'Sky Plaza Natividad Gate',         '["Trekking guide","Zipline access","Cristo Rey statue visit","Picnic area"]'),
('tayug-sunflower',     'Tayug Sunflower Eco Park Tour',            'Tayug',       '2–3 hours', 350.00,  'assets/images/nature/tayug.webp',                              'Nature',     '#10b981', 4.2, 60,  'Tayug Town Plaza',                 '["Park entry","Flower field walk","Vegetable picking","Photo opportunities"]'),
('saint-james-bolinao', 'Bolinao Church Heritage Walk',             'Bolinao',     '2–3 hours', 350.00,  'assets/images/historical/saint-james-bolinao-church-2.jpg',   'Historical', '#b45309', 4.2, 55,  'Bolinao Church Front Gate',        '["Church tour guide","Historical walk","Museum visit","Photo stops"]'),
('pangasinan-capitol',  'Lingayen Capitol & Gulf Heritage Tour',    'Lingayen',    '3–4 hours', 400.00,  'assets/images/historical/pangasinan-provincial-capitol.jpg',  'Historical', '#b45309', 4.2, 50,  'Pangasinan Capitol Main Entrance', '["Capitol tour guide","Gulf historical markers","Park walk","Sunset viewing"]'),
('pista-y-dayat',       "Pista''y Dayat Festival Experience",       'Lingayen',    '5–7 hours', 600.00,  'assets/images/festivals/pista-y-dayat-festival.jpeg',          'Festival',   '#f59e0b', 4.9, 45,  'Lingayen Beach Boulevard',         '["Festival guide","Beach party access","Pageant viewing","Street food tour"]'),
('bagoong-festival',    'Lingayen Bagoong Festival Tour',           'Lingayen',    '4–5 hours', 500.00,  'assets/images/festivals/bagoong-festival.jpg',                 'Festival',   '#f59e0b', 4.1, 70,  'Lingayen Town Plaza',              '["Festival guide","Bagoong tasting","Cultural show","Market tour"]'),
('puto-festival',       'Calasiao Puto Festival Tour',              'Calasiao',    '3–4 hours', 400.00,  'assets/images/festivals/puto-festival.jpg',                    'Festival',   '#f59e0b', 4.4, 80,  'Calasiao Church',                  '["Festival guide","Puto tasting","Eating contest entry","Cultural street dance viewing"]'),
('cape-bojeador',       'Cape Bojeador Lighthouse Day Trip',        'Ilocos Norte','5–7 hours', 700.00,  'assets/images/historical/cape-bojeador-lighthouse.jpg',        'Historical', '#b45309', 4.4, 55,  'Alaminos City Pier',               '["Transport to lighthouse","Historical guide","Wind farm visit","Coastal scenic stops"]'),
('pangasinan-longganisa','Alaminos Food Heritage Tour',             'Alaminos',    '2–3 hours', 300.00,  'assets/images/delicacies/longganisa-pangasinan.jpg',           'Food',       '#ef4444', 4.3, 65,  'Alaminos Public Market',           '["Local food guide","Longganisa tasting","Market tour","Recipe story session"]'),
('pigar-pigar',         'Dagupan Street Food Night Tour',           'Dagupan',     '2–3 hours', 350.00,  'assets/images/delicacies/pigar-pigar.jpg',                     'Food',       '#ef4444', 4.3, 70,  'Galvan Street, Dagupan',           '["Street food guide","Pigar-pigar serving","Night market walk","Local drinks"]'),
('puto-calasiao',       'Calasiao Puto & Delicacies Tour',          'Calasiao',    '2–3 hours', 300.00,  'assets/images/delicacies/puto-calasio.jfif',                   'Food',       '#ef4444', 4.5, 85,  'Calasiao Church Grounds',          '["Puto tasting","Bakery visit","Recipe walk","Pasalubong shopping guide"]'),
('tupig',               'Pangasinan Rice Cake Trail',               'Villasis',    '2–3 hours', 300.00,  'assets/images/delicacies/tupig.jfif',                          'Food',       '#ef4444', 4.4, 55,  'Villasis Public Market',           '["Tupig grilling demo","Tasting session","MacArthur Highway vendor walk","Pasalubong guide"]'),
('pangasinan-bangus',   'Dagupan Bangus & Seafood Tour',            'Dagupan',     '3–4 hours', 450.00,  'assets/images/delicacies/bangus-pangasinan.jpg',               'Food',       '#ef4444', 4.5, 60,  'Dagupan City Fish Port',           '["Bangus farm visit","Cooking demo","Seafood lunch","Market tour"]'),
('talong-festival',     'Villasis Talong Festival Tour',            'Villasis',    '4–5 hours', 450.00,  'assets/images/festivals/talong-festival.jpg',                  'Festival',   '#f59e0b', 4.2, 40,  'Villasis Town Plaza',              '["Festival guide","Cook-off viewing","Street dance parade","Eggplant tasting"]');

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
