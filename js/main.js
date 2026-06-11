document.addEventListener('DOMContentLoaded', () => {

    // ─── NAVBAR ──────────────────────────────────────────────
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
        const hamburger = document.querySelector('.hamburger');
        const navLinks  = document.querySelector('.nav-links');
        if (hamburger && navLinks) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navLinks.classList.toggle('active');
            });
            navLinks.querySelectorAll('a').forEach(a =>
                a.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    navLinks.classList.remove('active');
                })
            );
        }
    }

    // ─── SHARED HELPERS ──────────────────────────────────────
    const CAT_COLORS = {
        Nature:     '#10b981',
        Beach:      '#0ea5e9',
        Historical: '#78716c',
        Festival:   '#f59e0b',
        Food:       '#ef4444'
    };

    function starsHTML(rating, size = 14) {
        let html = '<div class="stars-row" style="display:flex;align-items:center;gap:2px;">';
        for (let i = 1; i <= 5; i++) {
            if (rating >= i - 0.25) {
                html += `<img src="assets/Home Page/StarYellow.svg" width="${size}" height="${size}" alt="Star">`;
            } else if (rating >= i - 0.75) {
                html += `<img src="assets/Home Page/HalfStarYellow.svg" width="${size}" height="${size}" alt="Half Star">`;
            } else {
                html += `<svg width="${size}" height="${size}" viewBox="0 0 24 24"
                    fill="none"
                    stroke="#d1d5db"
                    stroke-width="1.5">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>`;
            }
        }
        html += '</div>';
        return html;
    }

    function cardHTML(spot) {
        return `
            <a href="details.html?id=${spot.id}" class="explore-card">
                <div class="explore-card-img" style="background-image:url('${spot.image}')">
                    <span class="explore-card-badge" style="background:${CAT_COLORS[spot.category] || '#6b7280'}">${spot.category}</span>
                </div>
                <div class="explore-card-body">
                    <div class="explore-card-title">${spot.title}</div>
                    <div class="explore-card-loc">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        ${spot.location}
                    </div>
                    <div class="explore-card-footer">
                        <div class="explore-card-rating">
                            ${starsHTML(spot.rating, 13)}
                            <span style="margin-left:3px;font-size:13px;">${spot.rating}</span>
                        </div>
                        <span class="explore-card-link">
                            View Details
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>`;
    }

    // ─── DATA FETCH ───────────────────────────────────────────
    let spotsData = [];

    fetch('data/spots.json')
        .then(r => r.json())
        .then(data => { spotsData = data; initPage(); })
        .catch(err => {
            console.error('Error loading spots:', err);
            const g = document.getElementById('exploreGrid') || document.getElementById('destinationsGrid');
            if (g) g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px;color:#6b7280;">Failed to load destinations.</div>';
        });

    function initPage() {
        if (document.getElementById('exploreGrid'))      initExplorePage();
        else if (document.getElementById('details-content')) initDetailsPage();
        else if (document.getElementById('destinationsGrid')) initHomePage();
    }

    // ─── HOME PAGE ────────────────────────────────────────────
    function initHomePage() {
        const grid    = document.getElementById('destinationsGrid');
        const filters = document.querySelectorAll('#categoryFilters .map-pill');
        let currentCat = 'All';

        function render() {
            const filtered = currentCat === 'All'
                ? spotsData
                : spotsData.filter(s => s.category === currentCat);

            grid.innerHTML = filtered.slice(0, 4).map(spot => `
                <a href="details.html?id=${spot.id}" class="category-card">
                    <div class="category-img" style="background-image:url('${spot.image}')"></div>
                    <div class="category-info">
                        <span class="category-name">${spot.title}</span>
                        <span class="category-text">${spot.location}</span>
                    </div>
                </a>`).join('');
        }

        filters.forEach(pill => {
            pill.addEventListener('click', () => {
                filters.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                currentCat = pill.dataset.category;
                render();
            });
        });

        render();
    }

    // ─── EXPLORE PAGE ─────────────────────────────────────────
    function initExplorePage() {
        const grid       = document.getElementById('exploreGrid');
        const searchEl   = document.getElementById('exploreSearch');
        const sortEl     = document.getElementById('exploreSort');
        const countEl    = document.getElementById('exploreCount');
        const paginEl    = document.getElementById('explorePagination');
        const catBtns    = document.querySelectorAll('#exploreCats .explore-cat-btn');
        const gridBtn    = document.getElementById('gridViewBtn');
        const listBtn    = document.getElementById('listViewBtn');

        const PER_PAGE = 8;
        let cat     = 'All';
        let query   = '';
        let sort    = 'popular';
        let page    = 1;
        let isGrid  = true;

        function filtered() {
            let d = spotsData.filter(s => {
                const matchCat  = cat === 'All' || s.category === cat;
                const matchQ    = !query || s.title.toLowerCase().includes(query) || s.location.toLowerCase().includes(query);
                return matchCat && matchQ;
            });
            if (sort === 'rating') d = [...d].sort((a,b) => b.rating - a.rating);
            if (sort === 'name')   d = [...d].sort((a,b) => a.title.localeCompare(b.title));
            return d;
        }

        function render() {
            const all   = filtered();
            const pages = Math.ceil(all.length / PER_PAGE) || 1;
            if (page > pages) page = 1;
            const slice = all.slice((page-1)*PER_PAGE, page*PER_PAGE);

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
            let html = `<button class="page-btn" id="pprev" ${page===1?'disabled':''}>&#8592;</button>`;
            for (let i=1; i<=total; i++)
                html += `<button class="page-btn${i===page?' active':''}" data-p="${i}">${i}</button>`;
            html += `<button class="page-btn" id="pnext" ${page===total?'disabled':''}>&#8594;</button>`;
            paginEl.innerHTML = html;

            paginEl.querySelectorAll('[data-p]').forEach(b =>
                b.addEventListener('click', () => { page = +b.dataset.p; render(); window.scrollTo({top:0,behavior:'smooth'}); }));
            document.getElementById('pprev').addEventListener('click', () => { if(page>1){page--;render();} });
            document.getElementById('pnext').addEventListener('click', () => { if(page<total){page++;render();} });
        }

        searchEl.addEventListener('input', e  => { query = e.target.value.toLowerCase(); page=1; render(); });
        sortEl.addEventListener('change',  e  => { sort  = e.target.value; page=1; render(); });
        catBtns.forEach(b => b.addEventListener('click', () => {
            catBtns.forEach(x => x.classList.remove('active'));
            b.classList.add('active');
            cat = b.dataset.cat; page=1; render();
        }));
        gridBtn.addEventListener('click', () => { isGrid=true;  gridBtn.classList.add('active'); listBtn.classList.remove('active'); render(); });
        listBtn.addEventListener('click', () => { isGrid=false; listBtn.classList.add('active'); gridBtn.classList.remove('active'); render(); });

        render();
    }

    // ─── DETAILS PAGE ─────────────────────────────────────────
    function initDetailsPage() {
        const id   = new URLSearchParams(window.location.search).get('id') || 'hundred-islands';
        const spot = spotsData.find(s => s.id === id);

        if (!spot) {
            document.getElementById('details-loading').textContent = 'Destination not found.';
            return;
        }

        document.getElementById('details-loading').style.display = 'none';
        document.getElementById('details-content').style.display = 'block';

        // Page title
        document.title = `${spot.title} | Tara Pangasinan`;

        // Hero
        document.getElementById('dh-hero').style.backgroundImage = `url('${spot.image}')`;
        const badge = document.getElementById('dh-category-badge');
        badge.textContent = spot.category;
        badge.style.background = CAT_COLORS[spot.category] || '#6b7280';
        document.getElementById('dh-title').textContent        = spot.title;
        document.getElementById('dh-location-text').textContent = `${spot.location}, Pangasinan · ${spot.category}`;
        document.getElementById('bc-spot-name').textContent    = spot.title;

        // Overview paragraphs
        document.getElementById('overview-text').innerHTML =
            spot.description.split('\n\n').map(p => `<p style="margin-bottom:1rem;">${p}</p>`).join('');

        // Stats
        const statsEl = document.getElementById('stats-grid');
        if (spot.stats) {
            statsEl.innerHTML = spot.stats.map(s => `
                <div class="stat-box">
                    <span class="stat-label">${s.label}</span>
                    <span class="stat-value">${s.value}</span>
                </div>`).join('');
        }

        // Activities
        const actEl = document.getElementById('activities-grid');
        if (spot.activities) {
            actEl.innerHTML = spot.activities.map(act => `
                <div class="activity-card">
                    <div class="activity-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div class="activity-info">
                        <h4>${act}</h4>
                        <p>Popular experience</p>
                    </div>
                </div>`).join('');
        }

        // Gallery
        const galleryEl = document.getElementById('gallery-grid');
        if (spot.gallery && spot.gallery.length) {
            galleryEl.innerHTML = spot.gallery.map((img, i) =>
                `<div class="gallery-item${i===0?' large':''}" style="background-image:url('${img}');"></div>`
            ).join('');
        }

        // Leaflet map
        if (spot.lat && spot.lng) {
            const dMap = L.map('details-map', { scrollWheelZoom: false })
                          .setView([spot.lat, spot.lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(dMap);
            L.marker([spot.lat, spot.lng]).addTo(dMap)
             .bindPopup(`<strong>${spot.title}</strong><br>${spot.location}, Pangasinan`)
             .openPopup();
            document.getElementById('map-caption').textContent =
                `📍 ${spot.location}, Pangasinan, Philippines`;
            const dirBtn = document.getElementById('directions-btn');
            if (dirBtn) dirBtn.href = `https://www.google.com/maps?q=${spot.lat},${spot.lng}`;
        }

        // Tips
        const tipsEl = document.getElementById('tips-list');
        if (spot.tips) {
            tipsEl.innerHTML = spot.tips.map(t => `<li>${t}</li>`).join('');
        }

        // Sidebar
        setText('sidebar-hours',    spot.hours    || 'Contact for hours');
        setText('sidebar-entrance', spot.entrance || 'Free');
        setText('sidebar-contact',  spot.contact  || 'N/A');
        setText('sidebar-address',  `${spot.location}, Pangasinan, Philippines`);
        if (spot.website) {
            const ws = document.getElementById('sidebar-website');
            ws.textContent = spot.website;
            ws.href = `https://${spot.website}`;
        }
        document.getElementById('weather-location').textContent = spot.location;

        // Rating
        setText('sidebar-rating', spot.rating);
        setText('sidebar-reviews', `(${spot.reviews} reviews)`);
        ['review-stars-row','r1-stars','r2-stars','r3-stars'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = starsHTML(spot.rating, id === 'review-stars-row' ? 16 : 14);
        });

        // Nearby destinations (other spots, max 3)
        const nearbyEl = document.getElementById('nearby-list');
        const others   = spotsData.filter(s => s.id !== spot.id).slice(0, 3);
        nearbyEl.innerHTML = others.map(s => `
            <li>
                <div class="nearby-img" style="background-image:url('${s.image}');"></div>
                <div class="nearby-info">
                    <h4>${s.title}</h4>
                    <span>${s.location} · ${s.category}</span>
                </div>
                <a href="details.html?id=${s.id}" style="margin-left:auto;color:#1D9E75;flex-shrink:0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </li>`).join('');

        // You Might Also Like (4 other spots)
        const likeEl   = document.getElementById('you-might-like-grid');
        const likeSpots = spotsData.filter(s => s.id !== spot.id).slice(0, 4);
        likeEl.innerHTML = likeSpots.map(cardHTML).join('');
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

});
