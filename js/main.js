// ── GLOBAL HELPERS (called by inline onclick handlers) ────────────

function getSavedIds() { return JSON.parse(localStorage.getItem('tara_saved') || '[]'); }
function setSavedIds(s) { localStorage.setItem('tara_saved', JSON.stringify(s)); }

function showToast(msg, type) {
    type = type || 'success';
    var old = document.querySelector('.tara-toast');
    if (old) old.remove();
    var t = document.createElement('div');
    t.className = 'tara-toast tara-toast-' + type;
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('visible'); });
    setTimeout(function () {
        t.classList.remove('visible');
        setTimeout(function () { if (t.parentNode) t.remove(); }, 300);
    }, 2500);
}

function toggleCardSave(e, id) {
    e.preventDefault();
    e.stopPropagation();
    var s = getSavedIds();
    var idx = s.indexOf(id);
    var removing = idx > -1;
    if (removing) s.splice(idx, 1); else s.push(id);
    setSavedIds(s);
    document.querySelectorAll('[data-save-id="' + id + '"]').forEach(function (btn) {
        var isSaved = !removing;
        btn.classList.toggle('saved', isSaved);
        btn.title = isSaved ? 'Remove from saved' : 'Save to list';
        var svg = btn.querySelector('svg');
        if (svg) {
            svg.setAttribute('fill',   isSaved ? '#e63946' : 'none');
            svg.setAttribute('stroke', isSaved ? '#e63946' : 'currentColor');
        }
    });
    showToast(removing ? 'Removed from saved' : 'Saved to your list!', removing ? 'info' : 'success');
}

// ─────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {

    // ─── NAVBAR ──────────────────────────────────────────────
    var navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
        var hamburger = document.querySelector('.hamburger');
        var navLinks  = document.querySelector('.nav-links');
        if (hamburger && navLinks) {
            hamburger.addEventListener('click', function () {
                hamburger.classList.toggle('active');
                navLinks.classList.toggle('active');
            });
            navLinks.querySelectorAll('a').forEach(function (a) {
                a.addEventListener('click', function () {
                    hamburger.classList.remove('active');
                    navLinks.classList.remove('active');
                });
            });
        }

        // Active nav link detection
        var currentFile = window.location.pathname.split('/').pop() || 'home.html';
        document.querySelectorAll('.nav-links a').forEach(function (a) {
            var href = a.getAttribute('href');
            if (href && href !== '#' && href === currentFile) a.classList.add('active');
        });

        // Session-aware profile icon: redirect to login if not logged in
        var isLoggedIn = !!localStorage.getItem('tara_session');
        document.querySelectorAll('.nav-profile-icon').forEach(function (icon) {
            if (!isLoggedIn) {
                icon.href  = 'login.html';
                icon.title = 'Sign In';
            }
        });
    }

    // ─── SCROLL-TO-TOP BUTTON ────────────────────────────────
    var scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-top-btn';
    scrollBtn.title = 'Back to top';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    scrollBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>';
    scrollBtn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    document.body.appendChild(scrollBtn);
    window.addEventListener('scroll', function () {
        scrollBtn.classList.toggle('visible', window.scrollY > 300);
    });

    // ─── SHARED HELPERS ──────────────────────────────────────
    var CAT_COLORS = {
        Nature:     '#10b981',
        Beach:      '#0ea5e9',
        Historical: '#78716c',
        Festival:   '#f59e0b',
        Food:       '#ef4444'
    };

    function starsHTML(rating, size) {
        size = size || 14;
        var html = '<div class="stars-row" style="display:flex;align-items:center;gap:2px;">';
        for (var i = 1; i <= 5; i++) {
            if (rating >= i - 0.25) {
                html += '<img src="assets/Home Page/StarYellow.svg" width="' + size + '" height="' + size + '" alt="Star">';
            } else if (rating >= i - 0.75) {
                html += '<img src="assets/Home Page/HalfStarYellow.svg" width="' + size + '" height="' + size + '" alt="Half Star">';
            } else {
                html += '<svg width="' + size + '" height="' + size + '" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
            }
        }
        html += '</div>';
        return html;
    }

    function cardHTML(spot) {
        var isSaved     = getSavedIds().includes(spot.id);
        var heartFill   = isSaved ? '#e63946' : 'none';
        var heartStroke = isSaved ? '#e63946' : 'currentColor';
        return '<a href="details.html?id=' + spot.id + '" class="explore-card">' +
            '<div class="explore-card-img" style="background-image:url(\'' + spot.image + '\')">' +
            '<span class="explore-card-badge" style="background:' + (CAT_COLORS[spot.category] || '#6b7280') + '">' + spot.category + '</span>' +
            '<button class="card-heart-btn' + (isSaved ? ' saved' : '') + '" data-save-id="' + spot.id + '"' +
            ' onclick="toggleCardSave(event,\'' + spot.id + '\')"' +
            ' title="' + (isSaved ? 'Remove from saved' : 'Save to list') + '">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="' + heartFill + '" stroke="' + heartStroke + '" stroke-width="2">' +
            '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>' +
            '</svg></button></div>' +
            '<div class="explore-card-body">' +
            '<div class="explore-card-title">' + spot.title + '</div>' +
            '<div class="explore-card-loc">' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
            spot.location + '</div>' +
            '<div class="explore-card-footer">' +
            '<div class="explore-card-rating">' + starsHTML(spot.rating, 13) +
            '<span style="margin-left:3px;font-size:13px;">' + spot.rating + '</span></div>' +
            '<span class="explore-card-link">View Details' +
            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
            '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>' +
            '</span></div></div></a>';
    }

    // ─── DATA FETCH ───────────────────────────────────────────
    var spotsData = [];

    fetch('data/spots.json')
        .then(function (r) { return r.json(); })
        .then(function (data) { spotsData = data; initPage(); })
        .catch(function (err) {
            console.error('Error loading spots:', err);
            var g = document.getElementById('exploreGrid') || document.getElementById('destinationsGrid');
            if (g) g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:#6b7280;">Failed to load destinations.</div>';
        });

    function initPage() {
        if (document.getElementById('exploreGrid'))           initExplorePage();
        else if (document.getElementById('details-content')) initDetailsPage();
        else if (document.getElementById('destinationsGrid')) initHomePage();
    }

    // ─── HOME PAGE ────────────────────────────────────────────
    function initHomePage() {
        var grid    = document.getElementById('destinationsGrid');
        var filters = document.querySelectorAll('#categoryFilters .map-pill');
        var currentCat = 'All';

        function render() {
            var filtered = currentCat === 'All'
                ? spotsData
                : spotsData.filter(function (s) { return s.category === currentCat; });

            grid.innerHTML = filtered.slice(0, 4).map(function (spot) {
                return '<a href="details.html?id=' + spot.id + '" class="category-card">' +
                    '<div class="category-img" style="background-image:url(\'' + spot.image + '\')"></div>' +
                    '<div class="category-info">' +
                    '<span class="category-name">' + spot.title + '</span>' +
                    '<span class="category-text">' + spot.location + '</span>' +
                    '</div></a>';
            }).join('');
        }

        filters.forEach(function (pill) {
            pill.addEventListener('click', function () {
                filters.forEach(function (p) { p.classList.remove('active'); });
                pill.classList.add('active');
                currentCat = pill.dataset.category;
                render();
            });
        });

        render();
    }

    // ─── EXPLORE PAGE ─────────────────────────────────────────
    function initExplorePage() {
        var grid     = document.getElementById('exploreGrid');
        var searchEl = document.getElementById('exploreSearch');
        var sortEl   = document.getElementById('exploreSort');
        var countEl  = document.getElementById('exploreCount');
        var paginEl  = document.getElementById('explorePagination');
        var catBtns  = document.querySelectorAll('#exploreCats .explore-cat-btn');
        var gridBtn  = document.getElementById('gridViewBtn');
        var listBtn  = document.getElementById('listViewBtn');

        var PER_PAGE = 8;
        var cat    = 'All';
        var query  = '';
        var sort   = 'popular';
        var page   = 1;
        var isGrid = true;

        function filtered() {
            var d = spotsData.filter(function (s) {
                var matchCat = cat === 'All' || s.category === cat;
                var matchQ   = !query || s.title.toLowerCase().includes(query) || s.location.toLowerCase().includes(query);
                return matchCat && matchQ;
            });
            if (sort === 'rating') d = d.slice().sort(function (a, b) { return b.rating - a.rating; });
            if (sort === 'name')   d = d.slice().sort(function (a, b) { return a.title.localeCompare(b.title); });
            return d;
        }

        function render() {
            var all   = filtered();
            var pages = Math.ceil(all.length / PER_PAGE) || 1;
            if (page > pages) page = 1;
            var slice = all.slice((page - 1) * PER_PAGE, page * PER_PAGE);

            countEl.textContent = all.length;
            grid.className = 'explore-grid' + (isGrid ? '' : ' list-view');

            if (!slice.length) {
                grid.innerHTML = '<div class="explore-empty">No destinations match your search.</div>';
                paginEl.innerHTML = '';
                return;
            }

            grid.innerHTML = slice.map(cardHTML).join('');
            renderPagination(pages);
        }

        function renderPagination(total) {
            if (total <= 1) { paginEl.innerHTML = ''; return; }
            var html = '<button class="page-btn" id="pprev"' + (page === 1 ? ' disabled' : '') + '>&#8592;</button>';
            for (var i = 1; i <= total; i++)
                html += '<button class="page-btn' + (i === page ? ' active' : '') + '" data-p="' + i + '">' + i + '</button>';
            html += '<button class="page-btn" id="pnext"' + (page === total ? ' disabled' : '') + '>&#8594;</button>';
            paginEl.innerHTML = html;

            paginEl.querySelectorAll('[data-p]').forEach(function (b) {
                b.addEventListener('click', function () { page = +b.dataset.p; render(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
            });
            document.getElementById('pprev').addEventListener('click', function () { if (page > 1) { page--; render(); } });
            document.getElementById('pnext').addEventListener('click', function () { if (page < total) { page++; render(); } });
        }

        searchEl.addEventListener('input',  function (e) { query = e.target.value.toLowerCase(); page = 1; render(); });
        sortEl.addEventListener('change',   function (e) { sort  = e.target.value; page = 1; render(); });
        catBtns.forEach(function (b) {
            b.addEventListener('click', function () {
                catBtns.forEach(function (x) { x.classList.remove('active'); });
                b.classList.add('active');
                cat = b.dataset.cat; page = 1; render();
            });
        });
        gridBtn.addEventListener('click', function () { isGrid = true;  gridBtn.classList.add('active'); listBtn.classList.remove('active'); render(); });
        listBtn.addEventListener('click', function () { isGrid = false; listBtn.classList.add('active'); gridBtn.classList.remove('active'); render(); });

        render();
    }

    // ─── DETAILS PAGE ─────────────────────────────────────────
    function initDetailsPage() {
        var id   = new URLSearchParams(window.location.search).get('id') || 'hundred-islands';
        var spot = spotsData.find(function (s) { return s.id === id; });

        if (!spot) {
            document.getElementById('details-loading').textContent = 'Destination not found.';
            return;
        }

        document.getElementById('details-loading').style.display = 'none';
        document.getElementById('details-content').style.display = 'block';

        document.title = spot.title + ' | Tara Pangasinan';

        // Hero
        document.getElementById('dh-hero').style.backgroundImage = "url('" + spot.image + "')";
        var badge = document.getElementById('dh-category-badge');
        badge.textContent = spot.category;
        badge.style.background = CAT_COLORS[spot.category] || '#6b7280';
        document.getElementById('dh-title').textContent         = spot.title;
        document.getElementById('dh-location-text').textContent = spot.location + ', Pangasinan · ' + spot.category;
        document.getElementById('bc-spot-name').textContent     = spot.title;

        // Overview
        document.getElementById('overview-text').innerHTML =
            spot.description.split('\n\n').map(function (p) { return '<p style="margin-bottom:1rem;">' + p + '</p>'; }).join('');

        // Stats
        var statsEl = document.getElementById('stats-grid');
        if (spot.stats) {
            statsEl.innerHTML = spot.stats.map(function (s) {
                return '<div class="stat-box"><span class="stat-label">' + s.label + '</span><span class="stat-value">' + s.value + '</span></div>';
            }).join('');
        }

        // Activities
        var actEl = document.getElementById('activities-grid');
        if (spot.activities) {
            actEl.innerHTML = spot.activities.map(function (act) {
                return '<div class="activity-card"><div class="activity-icon">' +
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>' +
                    '</div><div class="activity-info"><h4>' + act + '</h4><p>Popular experience</p></div></div>';
            }).join('');
        }

        // Gallery with lightbox
        var galleryEl = document.getElementById('gallery-grid');
        if (spot.gallery && spot.gallery.length) {
            var imgs = spot.gallery;
            var SHOW = Math.min(5, imgs.length);
            var extra = imgs.length - SHOW;

            galleryEl.innerHTML = imgs.slice(0, SHOW).map(function (img, i) {
                var isLast = i === SHOW - 1 && extra > 0;
                return '<div class="gallery-item" onclick="openLightbox(' + i + ')">' +
                    '<div class="gallery-img" style="background-image:url(\'' + img + '\')"></div>' +
                    '<div class="gallery-hover">' +
                    '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="filter:drop-shadow(0 1px 4px rgba(0,0,0,0.5))">' +
                    '<polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/>' +
                    '<line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>' +
                    '</div>' +
                    (isLast
                        ? '<div class="gallery-more-overlay"><span class="gallery-more-count">+' + extra + '</span>' +
                          '<span class="gallery-more-label">more photos</span></div>'
                        : '') +
                    '</div>';
            }).join('');

            // "See all photos" row below grid
            var footer = document.createElement('div');
            footer.className = 'gallery-footer';
            footer.innerHTML =
                '<span class="gallery-photo-count">' + imgs.length + ' photos</span>' +
                '<button class="gallery-see-all" onclick="openLightbox(0)">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">' +
                '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>' +
                '<polyline points="21 15 16 10 5 21"/></svg>' +
                'See all photos</button>';
            galleryEl.parentNode.insertBefore(footer, galleryEl.nextSibling);

            if (!document.getElementById('gallery-lightbox')) {
                var lb = document.createElement('div');
                lb.id = 'gallery-lightbox';
                lb.innerHTML =
                    '<div class="lightbox-overlay" id="lb-overlay">' +
                    '<div class="lb-header">' +
                    '<span class="lb-counter" id="lb-counter">1 / ' + imgs.length + '</span>' +
                    '<button class="lightbox-close" onclick="closeLightbox()" aria-label="Close">' +
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">' +
                    '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>' +
                    '</div>' +
                    '<button class="lightbox-prev" id="lb-prev" aria-label="Previous">&#8249;</button>' +
                    '<img class="lightbox-img" id="lightbox-img" src="" alt="Gallery photo">' +
                    '<button class="lightbox-next" id="lb-next" aria-label="Next">&#8250;</button>' +
                    '<div class="lb-thumbs" id="lb-thumbs">' +
                    imgs.map(function (img, i) {
                        return '<button class="lb-thumb" data-idx="' + i + '" style="background-image:url(\'' + img + '\')" onclick="jumpLightbox(' + i + ')"></button>';
                    }).join('') +
                    '</div>' +
                    '</div>';
                document.body.appendChild(lb);
                document.getElementById('lb-overlay').addEventListener('click', function (e) {
                    if (e.target === this) closeLightbox();
                });
            }

            var lbIdx = 0;

            function syncLb(idx) {
                document.getElementById('lightbox-img').src = imgs[idx];
                document.getElementById('lb-counter').textContent = (idx + 1) + ' / ' + imgs.length;
                document.querySelectorAll('.lb-thumb').forEach(function (t, i) {
                    t.classList.toggle('active', i === idx);
                });
                var active = document.querySelector('.lb-thumb.active');
                if (active) active.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
            }

            window.openLightbox = function (idx) {
                lbIdx = idx;
                syncLb(lbIdx);
                document.getElementById('gallery-lightbox').style.display = 'block';
                document.body.style.overflow = 'hidden';
            };
            window.closeLightbox = function () {
                document.getElementById('gallery-lightbox').style.display = 'none';
                document.body.style.overflow = '';
            };
            window.jumpLightbox = function (idx) {
                lbIdx = idx;
                syncLb(lbIdx);
            };
            document.getElementById('lb-prev').onclick = function () {
                lbIdx = (lbIdx - 1 + imgs.length) % imgs.length;
                syncLb(lbIdx);
            };
            document.getElementById('lb-next').onclick = function () {
                lbIdx = (lbIdx + 1) % imgs.length;
                syncLb(lbIdx);
            };
            document.addEventListener('keydown', function (e) {
                var lb2 = document.getElementById('gallery-lightbox');
                if (!lb2 || lb2.style.display !== 'block') return;
                if (e.key === 'Escape')     closeLightbox();
                if (e.key === 'ArrowLeft')  { lbIdx = (lbIdx - 1 + imgs.length) % imgs.length; syncLb(lbIdx); }
                if (e.key === 'ArrowRight') { lbIdx = (lbIdx + 1) % imgs.length; syncLb(lbIdx); }
            });
        }

        // Map
        if (spot.lat && spot.lng) {
            var dMap = L.map('details-map', { scrollWheelZoom: false })
                        .setView([spot.lat, spot.lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(dMap);
            L.marker([spot.lat, spot.lng]).addTo(dMap)
             .bindPopup('<strong>' + spot.title + '</strong><br>' + spot.location + ', Pangasinan<br>' +
                        '<a href="details.html?id=' + spot.id + '" style="color:#1D9E75;font-family:Inter,sans-serif;font-size:12px;">View Details →</a>')
             .openPopup();
            document.getElementById('map-caption').textContent = '📍 ' + spot.location + ', Pangasinan, Philippines';
            var dirBtn = document.getElementById('directions-btn');
            if (dirBtn) dirBtn.href = 'https://www.google.com/maps?q=' + spot.lat + ',' + spot.lng;
        }

        // Tips
        var tipsEl = document.getElementById('tips-list');
        if (spot.tips) {
            tipsEl.innerHTML = spot.tips.map(function (t) { return '<li>' + t + '</li>'; }).join('');
        }

        // Sidebar info
        setText('sidebar-hours',    spot.hours    || 'Contact for hours');
        setText('sidebar-entrance', spot.entrance || 'Free');
        setText('sidebar-contact',  spot.contact  || 'N/A');
        setText('sidebar-address',  spot.location + ', Pangasinan, Philippines');
        if (spot.website) {
            var ws = document.getElementById('sidebar-website');
            ws.textContent = spot.website;
            ws.href = 'https://' + spot.website;
        }
        document.getElementById('weather-location').textContent = spot.location;

        // Rating
        setText('sidebar-rating',  spot.rating);
        setText('sidebar-reviews', '(' + spot.reviews + ' reviews)');
        ['review-stars-row', 'r1-stars', 'r2-stars', 'r3-stars'].forEach(function (rid) {
            var el = document.getElementById(rid);
            if (el) el.innerHTML = starsHTML(spot.rating, rid === 'review-stars-row' ? 16 : 14);
        });

        // Nearby (3 other spots)
        var nearbyEl = document.getElementById('nearby-list');
        var others   = spotsData.filter(function (s) { return s.id !== spot.id; }).slice(0, 3);
        nearbyEl.innerHTML = others.map(function (s) {
            return '<li>' +
                '<div class="nearby-img" style="background-image:url(\'' + s.image + '\');"></div>' +
                '<div class="nearby-info"><h4>' + s.title + '</h4><span>' + s.location + ' · ' + s.category + '</span></div>' +
                '<a href="details.html?id=' + s.id + '" style="margin-left:auto;color:#1D9E75;flex-shrink:0;">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a></li>';
        }).join('');

        // You Might Also Like
        var likeEl    = document.getElementById('you-might-like-grid');
        var likeSpots = spotsData.filter(function (s) { return s.id !== spot.id; }).slice(0, 4);
        likeEl.innerHTML = likeSpots.map(cardHTML).join('');
    }

    function setText(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val;
    }

});
