# Tara Pangasinan — Project Overview

**Course Final Project | Group 5 | Web Development**
**Deadline: June 18, 2026 (Presentation)**

---

## Group Members

| Name |
|---|
| Bacolor, James Clark |
| Caole, Stephanie |
| Guarin, Pauline |
| Soriano, Shouma King |
| Lituco, Jessica Jhoanne |

---

## Purpose

**Tara Pangasinan** is a tourism web application built to promote and showcase the tourist spots, cultural heritage, and natural wonders of the province of Pangasinan, Philippines. The website serves as a digital travel guide that helps both local and foreign visitors discover, explore, and plan their visit to Pangasinan.

The name *"Tara"* is a Filipino word meaning *"Let's go"* — reflecting the site's invitation for users to explore the beauty of the Pearl of the North.

---

## Goals

1. **Promote Pangasinan Tourism** — Present Pangasinan's top destinations in an engaging, visually appealing way that encourages visitors to plan a trip.
2. **Inform and Educate** — Provide accurate information about each tourist spot including location, category, activities, descriptions, and visitor tips.
3. **Improve Trip Planning** — Give users tools to browse, filter, save, and map out destinations before and during their visit.
4. **Demonstrate Web Development Skills** — Apply the full web development tech stack taught in class (HTML, CSS, JavaScript, JSON, AJAX, PHP, DBMS) in a real-world project.

---

## Tech Stack

| Technology | Role |
|---|---|
| **HTML** | Page structure and semantic markup |
| **CSS** | Styling, layout, animations, and responsive design |
| **JavaScript** | Dynamic rendering, filtering, search, and page interactions |
| **JSON** | Local data store for tourist spot information (`data/spots.json`) |
| **AJAX / Fetch API** | Asynchronously loading spot data without page reloads |
| **PHP** | Server-side logic — user authentication, form handling, backend routing |
| **DBMS** | Storing user accounts, saved spots, reviews, and itineraries |

---

## Pages

| File | Page | Description |
|---|---|---|
| `index.html` | **Home** | Landing page with hero, featured destination, spot previews, interactive map overview, category browse, and gallery |
| `explore.html` | **Explore** | Full list of all tourist spots with search, category filters, sort options, and grid/list view toggle |
| `details.html` | **Spot Details** | Individual page for each destination — overview, activities, photo gallery, location map, visitor tips, reviews, and nearby spots |
| `map.html` | **Map** | Interactive map of Pangasinan with pinned tourist spots for geographic browsing |
| `about.html` | **About** | Information about the project, the province of Pangasinan, and the development team |
| `contact.html` | **Contact** | Contact form for user inquiries and feedback |
| `plan.html` | **Plan Your Visit** | Trip planning tool for building and organizing a personal itinerary |
| `saved.html` | **Saved Spots** | User's saved/bookmarked tourist destinations |
| `login.html` | **Login** | User authentication page |
| `register.html` | **Register** | New user registration page |

---

## Core Features

### Destination Browsing
- View all 8 (expandable) tourist spots of Pangasinan presented as cards with images, names, locations, ratings, and categories.
- Each card links to a dedicated details page.

### Search & Filter
- **Text Search** — Filter destinations by name or municipality in real time.
- **Category Filter** — Browse by: Beach, Nature, Historical, Festival, Food.
- **Sort** — Sort destinations by popularity, rating, or name.

### Spot Details Page
- Full description and background of the destination.
- Quick stats: islands/area count, location, best time to visit, and rating.
- **Activities & Experiences** — List of things to do at the spot.
- **Photo Gallery** — Multi-image grid layout for the destination.
- **Embedded Map** — Location pinpointed on an interactive map.
- **Visitor Tips** — Practical advice for visitors.
- **Visitor Reviews** — Star ratings and text reviews from past visitors.
- **Nearby Destinations** — Sidebar suggestions for spots close by.
- **Plan Your Visit Sidebar** — Operating hours, entrance fees, contact info, and CTA buttons.
- **Current Weather Widget** — Live weather for the spot's municipality.

### Interactive Map
- Province-wide map with markers for each tourist spot.
- Click a marker to view the spot's name and navigate to its details page.
- Filter map pins by category.

### Trip Planning
- Users can save spots to a personal list.
- Plan and organize a custom itinerary for their Pangasinan visit.

### User Accounts (PHP + DBMS)
- Register and log in to a personal account.
- Saved spots and planned itineraries persist across sessions via a database.

### Responsive Design
- Fully responsive across desktop (1440px), tablet (≤992px), and mobile (≤768px).
- Mobile navigation collapses into a hamburger menu.

---

## Data Source

Tourist spot data is stored in `data/spots.json` and loaded via the Fetch API (AJAX pattern). Each spot record contains:

```json
{
  "id": "unique-slug",
  "title": "Spot Name",
  "location": "Municipality",
  "category": "Nature | Beach | Historical | Festival | Food",
  "rating": 4.8,
  "reviews": 124,
  "shortDesc": "One-line teaser",
  "description": "Full paragraph description",
  "image": "image-url",
  "activities": ["Activity 1", "Activity 2"]
}
```

---

## Current Tourist Spots (v1)

| # | Spot | Location | Category |
|---|---|---|---|
| 1 | Hundred Islands National Park | Alaminos City | Nature |
| 2 | Bolinao Falls | Bolinao | Nature |
| 3 | Enchanted Cave | Bolinao | Nature |
| 4 | Cape Bolinao Lighthouse | Bolinao | Historical |
| 5 | Lingayen Gulf | Lingayen | Beach |
| 6 | Manaoag Church | Manaoag | Historical |
| 7 | Patar Beach | Bolinao | Beach |
| 8 | Dagupan Bangus Festival | Dagupan | Festival |
