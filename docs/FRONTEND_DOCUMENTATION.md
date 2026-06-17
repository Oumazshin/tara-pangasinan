# Tara Pangasinan — Frontend Documentation & Finalization Updates

This document serves as the central reference for the frontend implementation, design system, and recent finalization updates for the Tara Pangasinan tourism web application.

---

## 1. Technical Stack Alignment

In strict adherence to the project rubric constraints, the frontend is built entirely with vanilla technologies. No external CSS frameworks (e.g., Tailwind, Bootstrap) or JavaScript frameworks (e.g., React, Vue) were used.

*   **HTML5:** Semantic structuring using `<header>`, `<nav>`, `<main>`, `<section>`, and `<footer>` to ensure accessibility and proper document flow.
*   **CSS3:** Responsive, mobile-first design system utilizing BEM-inspired naming conventions (`.explore-card`, `.explore-card__title`) and native CSS variables for theming.
*   **JavaScript (ES5/ES6):** Client-side logic for DOM manipulation, asynchronous data fetching via the Fetch API (AJAX), and interactive behaviors.

---

## 2. Design System & Visual Guidelines

The interface is designed to evoke a premium, engaging travel experience.

### Color Palette

*   **Brand Primary:** `#1D9E75` (Forest/Leaf Green) — Used for primary CTAs, active states, and highlights.
*   **Brand Hover:** `#168260` — Used for button interactions.
*   **Neutrals:**
    *   Dark Text/Headings: `#1f2937`
    *   Body Text: `#374151`
    *   Muted Text/Borders: `#6b7280` / `#e5e7eb`
    *   Background (Light): `#f8fafc`
    *   Surfaces/Cards: `#ffffff`

### Category Badges

Distinct colors help users immediately identify destination types:
*   **Beach:** `#0ea5e9`
*   **Nature:** `#10b981`
*   **Historical:** `#78716c`
*   **Festival:** `#f59e0b`
*   **Food:** `#ef4444`

### Layout & Responsiveness

*   **Container:** Max-width of `1200px` with horizontal padding.
*   **Breakpoints:**
    *   Mobile (`< 768px`): Single-column layouts, stacked inputs, hamburger navigation.
    *   Tablet (`768px - 1023px`): 2-column grids for cards.
    *   Desktop (`>= 1024px`): 3- to 4-column grids, sticky sidebars.

---

## 3. JavaScript Architecture

The client-side logic is driven by a modular, vanilla JavaScript approach:

*   **`components.js`:** Responsible for dynamically fetching and injecting the `navbar.html` and `footer.html` partials into placeholders (`<div id="navbar-placeholder"></div>`), reducing code duplication across the 12 HTML pages.
*   **`api.js`:** A custom wrapper around the native `fetch()` API that standardizes JSON request/response handling, HTTP method configuration, and error throwing.
*   **`main.js`:** The core controller that handles:
    *   **Routing Logic:** Detects the current page (e.g., Explore vs. Details) and initializes the corresponding views (`initExplorePage()`, `initDetailsPage()`).
    *   **Data Hydration:** Fetches destination data (`spots.json` or `/api/spots/list.php`) and renders HTML templates using string concatenation.
    *   **Interactivity:** Controls the lightbox gallery, interactive Leaflet maps, category filtering, search, and pagination.
    *   **Session Management:** Syncs the local `tara_session` state with the server to toggle login/logout UI states and hydrate the "Saved Spots" heart icons.

---

## 4. Finalization Updates & Quality Assurance

To ensure the project meets the highest academic and production standards, the following "finalization" cleanups were applied across the frontend:

### 4.1 Accessibility and Link Cleanup
*   Replaced placeholder links (`href="#"`) with `href="javascript:void(0)"` on several pages to prevent the browser from abruptly scrolling to the top of the window when clicked:
    *   **`register.html` & `plan.html`:** Addressed the "Terms & Conditions" and "Privacy Policy" links.
    *   **`login.html`:** Updated the "Forgot password?" link.
    *   **`explore.html`:** Refined the "Scroll to top" floating action button.
    *   **`details.html`:** Fixed initial states for dynamic links (Directions button, Website link) before JavaScript populates them.

### 4.2 Form Validation
*   Confirmed that client-side validation is robustly implemented. Required fields utilize HTML5 `required`, `type="email"`, and `minlength` attributes, providing immediate browser-level feedback before the JavaScript `fetch` submits the payload to the PHP backend.

### 4.3 Documentation Consolidation
*   Redundant markdown files (`PROJECT_OVERVIEW.md` and `DESIGN_GUIDELINES.md`) were removed to streamline the repository.
*   The `Project-Alignment-Checklist.md` is preserved as the single source of truth for rubric compliance.
*   The definitive formal documentation structure required by the rubric is maintained centrally in `docs/Backend/12-DOCUMENTATION.md`.
